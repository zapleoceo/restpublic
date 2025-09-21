<?php
// Тест для проверки PageContentService на сервере

echo "=== ТЕСТ PAGECONTENTSERVICE НА СЕРВЕРЕ ===\n\n";

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
        
        // Проверяем, содержит ли контент ошибку
        if (strpos($pageContent['content'], 'Ошибка загрузки контента') !== false) {
            echo "ОШИБКА: Контент содержит сообщение об ошибке!\n";
        } else {
            echo "Контент загружен успешно.\n";
        }
    } else {
        echo "Контент пустой!\n";
    }
    
    echo "\n=== ТЕСТ ЗАВЕРШЕН ===\n";
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
    echo "Стек вызовов:\n" . $e->getTraceAsString() . "\n";
}
?>
