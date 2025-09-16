<?php
// Простой тест для отладки API
header('Content-Type: application/json');

echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
    'post_data' => $_POST,
    'files_data' => $_FILES,
    'raw_input' => file_get_contents('php://input'),
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
