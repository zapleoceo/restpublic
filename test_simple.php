<?php
$url = 'http://localhost:3002/api/poster/menu.getCategories?token=nr_api_2024_7f8a9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6';
$response = file_get_contents($url);
if ($response === false) {
    echo "❌ Запрос не выполнен\n";
} else {
    $data = json_decode($response, true);
    if (isset($data[0]['category_id'])) {
        echo "✅ Запрос выполнен успешно, получено категорий: " . count($data) . "\n";
    } else {
        echo "❌ Неожиданный ответ: " . substr($response, 0, 100) . "...\n";
    }
}
?>
