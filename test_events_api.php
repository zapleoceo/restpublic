<?php
// Скрипт для тестирования API событий
require_once 'vendor/autoload.php';

// Загружаем переменные окружения
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

echo "=== ТЕСТИРОВАНИЕ API СОБЫТИЙ ===\n\n";

// Тестовые данные
$testEvent = [
    'title' => 'Тестовое событие',
    'date' => '2025-01-20',
    'time' => '19:00',
    'conditions' => 'Бесплатно',
    'description_link' => 'https://example.com/test',
    'comment' => 'Тестовый комментарий',
    'is_active' => true
];

// Функция для отправки HTTP запросов
function sendRequest($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => $response
    ];
}

// Тест 1: Получение всех событий
echo "1. Тест получения всех событий (GET)\n";
$response = sendRequest('http://localhost/admin/events/api.php');
echo "HTTP код: " . $response['code'] . "\n";
$data = json_decode($response['body'], true);
if ($data && isset($data['success'])) {
    echo "✅ Успешно: " . $data['message'] . "\n";
    echo "Количество событий: " . count($data['data']) . "\n";
} else {
    echo "❌ Ошибка: " . $response['body'] . "\n";
}
echo "\n";

// Тест 2: Создание нового события
echo "2. Тест создания события (POST)\n";
$response = sendRequest('http://localhost/admin/events/api.php', 'POST', $testEvent);
echo "HTTP код: " . $response['code'] . "\n";
$data = json_decode($response['body'], true);
if ($data && $data['success']) {
    echo "✅ Событие создано: " . $data['message'] . "\n";
    $createdEventId = $data['id'];
} else {
    echo "❌ Ошибка создания: " . ($data['message'] ?? $response['body']) . "\n";
    $createdEventId = null;
}
echo "\n";

// Тест 3: Обновление события
if ($createdEventId) {
    echo "3. Тест обновления события (PUT)\n";
    $updateData = $testEvent;
    $updateData['event_id'] = $createdEventId;
    $updateData['title'] = 'Обновленное тестовое событие';
    $updateData['conditions'] = '1000 руб.';
    
    $response = sendRequest('http://localhost/admin/events/api.php', 'PUT', $updateData);
    echo "HTTP код: " . $response['code'] . "\n";
    $data = json_decode($response['body'], true);
    if ($data && $data['success']) {
        echo "✅ Событие обновлено: " . $data['message'] . "\n";
    } else {
        echo "❌ Ошибка обновления: " . ($data['message'] ?? $response['body']) . "\n";
    }
    echo "\n";
}

// Тест 4: Удаление события
if ($createdEventId) {
    echo "4. Тест удаления события (DELETE)\n";
    $response = sendRequest('http://localhost/admin/events/api.php', 'DELETE', ['event_id' => $createdEventId]);
    echo "HTTP код: " . $response['code'] . "\n";
    $data = json_decode($response['body'], true);
    if ($data && $data['success']) {
        echo "✅ Событие удалено: " . $data['message'] . "\n";
    } else {
        echo "❌ Ошибка удаления: " . ($data['message'] ?? $response['body']) . "\n";
    }
    echo "\n";
}

// Тест 5: Валидация данных
echo "5. Тест валидации данных\n";

// Тест с пустыми полями
echo "5.1. Тест с пустыми полями\n";
$invalidEvent = [
    'title' => '',
    'date' => '',
    'time' => '',
    'conditions' => ''
];
$response = sendRequest('http://localhost/admin/events/api.php', 'POST', $invalidEvent);
echo "HTTP код: " . $response['code'] . "\n";
$data = json_decode($response['body'], true);
if ($data && !$data['success']) {
    echo "✅ Валидация работает: " . $data['message'] . "\n";
} else {
    echo "❌ Валидация не работает\n";
}
echo "\n";

// Тест с неверным форматом даты
echo "5.2. Тест с неверным форматом даты\n";
$invalidDateEvent = $testEvent;
$invalidDateEvent['date'] = '20-01-2025'; // Неверный формат
$response = sendRequest('http://localhost/admin/events/api.php', 'POST', $invalidDateEvent);
echo "HTTP код: " . $response['code'] . "\n";
$data = json_decode($response['body'], true);
if ($data && !$data['success']) {
    echo "✅ Валидация даты работает: " . $data['message'] . "\n";
} else {
    echo "❌ Валидация даты не работает\n";
}
echo "\n";

// Тест с неверным форматом времени
echo "5.3. Тест с неверным форматом времени\n";
$invalidTimeEvent = $testEvent;
$invalidTimeEvent['time'] = '7:00 PM'; // Неверный формат
$response = sendRequest('http://localhost/admin/events/api.php', 'POST', $invalidTimeEvent);
echo "HTTP код: " . $response['code'] . "\n";
$data = json_decode($response['body'], true);
if ($data && !$data['success']) {
    echo "✅ Валидация времени работает: " . $data['message'] . "\n";
} else {
    echo "❌ Валидация времени не работает\n";
}
echo "\n";

echo "=== ТЕСТИРОВАНИЕ ЗАВЕРШЕНО ===\n";
?>