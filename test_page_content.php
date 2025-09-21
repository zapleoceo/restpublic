<?php
// Тест для проверки PageContentService

// Загружаем переменные окружения вручную
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

echo "=== ТЕСТ PAGECONTENTSERVICE ===\n\n";

try {
    require_once __DIR__ . '/classes/PageContentService.php';
    
    echo "Создаем PageContentService...\n";
    $pageContentService = new PageContentService();
    
    echo "Получаем текущий язык...\n";
    $currentLanguage = $pageContentService->getLanguage();
    echo "Текущий язык: " . $currentLanguage . "\n\n";
    
    echo "Получаем контент для главной страницы...\n";
    $pageContent = $pageContentService->getPageContent('index', $currentLanguage);
    
    echo "Результат:\n";
    echo "Есть контент: " . (!empty($pageContent['content']) ? 'да' : 'нет') . "\n";
    echo "Длина контента: " . strlen($pageContent['content']) . " символов\n";
    echo "Есть мета: " . (!empty($pageContent['meta']) ? 'да' : 'нет') . "\n";
    
    if (!empty($pageContent['content'])) {
        echo "Начало контента: " . substr($pageContent['content'], 0, 100) . "...\n";
    } else {
        echo "Контент пустой или содержит ошибку!\n";
        echo "Полный контент: " . $pageContent['content'] . "\n";
    }
    
    echo "\n=== ТЕСТ ЗАВЕРШЕН ===\n";
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
    echo "Стек вызовов:\n" . $e->getTraceAsString() . "\n";
}
?>
