<?php
/**
 * Admin Pages Test Script
 * Tests all admin pages content and functionality
 */

// Include auth check to set up session
require_once __DIR__ . '/includes/auth-check.php';

// Define all admin pages to test
$admin_pages = [
    'dashboard' => [
        'url' => '/admin/',
        'file' => '/admin/index.php',
        'title' => 'Панель управления'
    ],
    'pages' => [
        'url' => '/admin/pages/',
        'file' => '/admin/pages/index.php',
        'title' => 'Управление страницами'
    ],
    'users' => [
        'url' => '/admin/users/',
        'file' => '/admin/users/index.php',
        'title' => 'Управление пользователями'
    ],
    'guests' => [
        'url' => '/admin/guests/',
        'file' => '/admin/guests/index.php',
        'title' => 'Управление гостями'
    ],
    'database' => [
        'url' => '/admin/database/',
        'file' => '/admin/database/index.php',
        'title' => 'База данных'
    ],
    'events' => [
        'url' => '/admin/events/',
        'file' => '/admin/events/index.php',
        'title' => 'Управление событиями'
    ],
    'sepay' => [
        'url' => '/admin/sepay/',
        'file' => '/admin/sepay/index.php',
        'title' => 'SePay транзакции'
    ],
    'settings' => [
        'url' => '/admin/settings/',
        'file' => '/admin/settings/index.php',
        'title' => 'Настройки системы'
    ],
    'logs' => [
        'url' => '/admin/logs/',
        'file' => '/admin/logs/index.php',
        'title' => 'Системные логи'
    ],
    'health' => [
        'url' => '/admin/health/',
        'file' => '/admin/health/index.php',
        'title' => 'Здоровье системы'
    ]
];

// Test results
$test_results = [];

echo "<h1>🧪 Тестирование админ-панели</h1>";
echo "<p><strong>Время тестирования:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Пользователь:</strong> " . htmlspecialchars($_SESSION['admin_username'] ?? 'TestUser') . "</p>";
echo "<hr>";

// Test each page
foreach ($admin_pages as $key => $page) {
    echo "<h2>📄 Тестирование: {$page['title']}</h2>";

    $page_result = [
        'name' => $page['title'],
        'url' => $page['url'],
        'file_exists' => false,
        'file_readable' => false,
        'content_loaded' => false,
        'layout_working' => false,
        'errors' => [],
        'warnings' => []
    ];

    // Check if file exists
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $page['file'])) {
        $page_result['file_exists'] = true;

        // Check if file is readable
        if (is_readable($_SERVER['DOCUMENT_ROOT'] . $page['file'])) {
            $page_result['file_readable'] = true;

            // Try to load the page content
            try {
                ob_start();
                include $_SERVER['DOCUMENT_ROOT'] . $page['file'];
                $content = ob_get_clean();

                if (strlen($content) > 0) {
                    $page_result['content_loaded'] = true;

                    // Check if layout is working (look for layout includes)
                    if (strpos($content, 'layout.php') !== false ||
                        strpos($content, 'header.php') !== false ||
                        strpos($content, 'sidebar.php') !== false) {
                        $page_result['layout_working'] = true;
                    } else {
                        $page_result['warnings'][] = 'Layout includes not found in content';
                    }

                    // Check for common elements
                    if (strpos($content, 'admin-header') !== false) {
                        $page_result['layout_working'] = true;
                    }

                    // Check for required page variables
                    if (isset($page_title) || isset($page_header)) {
                        $page_result['warnings'][] = 'Page variables not properly defined';
                    }

                } else {
                    $page_result['errors'][] = 'Page content is empty';
                }

            } catch (Exception $e) {
                $page_result['errors'][] = 'Exception: ' . $e->getMessage();
            }

        } else {
            $page_result['errors'][] = 'File is not readable';
        }

    } else {
        $page_result['errors'][] = 'File does not exist: ' . $_SERVER['DOCUMENT_ROOT'] . $page['file'];
    }

    $test_results[$key] = $page_result;

    // Display results for this page
    echo "<div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px;'>";
    echo "<h3>{$page['title']} - <span style='color: " . ($page_result['content_loaded'] ? 'green' : 'red') . "'>" .
         ($page_result['content_loaded'] ? '✅ Пройдено' : '❌ Ошибка') . "</span></h3>";

    if (!empty($page_result['errors'])) {
        echo "<div style='color: red; margin: 10px 0;'>";
        echo "<strong>Ошибки:</strong><ul>";
        foreach ($page_result['errors'] as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul></div>";
    }

    if (!empty($page_result['warnings'])) {
        echo "<div style='color: orange; margin: 10px 0;'>";
        echo "<strong>Предупреждения:</strong><ul>";
        foreach ($page_result['warnings'] as $warning) {
            echo "<li>$warning</li>";
        }
        echo "</ul></div>";
    }

    echo "<div><strong>Файл:</strong> {$page['file']}</div>";
    echo "<div><strong>URL:</strong> <a href='{$page['url']}' target='_blank'>{$page['url']}</a></div>";

    echo "<div style='margin-top: 10px;'>";
    echo "<strong>Статус:</strong> ";
    echo "Файл существует: <span style='color: " . ($page_result['file_exists'] ? 'green">✅' : 'red">❌') . "</span> | ";
    echo "Файл читаемый: <span style='color: " . ($page_result['file_readable'] ? 'green">✅' : 'red">❌') . "</span> | ";
    echo "Контент загружен: <span style='color: " . ($page_result['content_loaded'] ? 'green">✅' : 'red">❌') . "</span> | ";
    echo "Layout работает: <span style='color: " . ($page_result['layout_working'] ? 'green">✅' : 'red">❌') . "</span>";
    echo "</div>";

    echo "</div>";
}

// Summary
echo "<h2>📊 Сводка результатов</h2>";
$total_pages = count($admin_pages);
$successful_pages = count(array_filter($test_results, function($result) {
    return $result['content_loaded'] && $result['layout_working'];
}));
$error_pages = count(array_filter($test_results, function($result) {
    return !empty($result['errors']);
}));
$warning_pages = count(array_filter($test_results, function($result) {
    return empty($result['errors']) && !empty($result['warnings']);
}));

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 20px 0;'>";

echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; text-align: center;'>";
echo "<h3 style='margin: 0 0 10px 0; color: #2d5a2d;'>✅ Успешно</h3>";
echo "<div style='font-size: 2rem; font-weight: bold; color: #2d5a2d;'>$successful_pages</div>";
echo "<div style='font-size: 0.9rem; color: #2d5a2d;'>из $total_pages</div>";
echo "</div>";

echo "<div style='background: #ffe8e8; padding: 15px; border-radius: 8px; text-align: center;'>";
echo "<h3 style='margin: 0 0 10px 0; color: #d32f2f;'>❌ Ошибки</h3>";
echo "<div style='font-size: 2rem; font-weight: bold; color: #d32f2f;'>$error_pages</div>";
echo "<div style='font-size: 0.9rem; color: #d32f2f;'>страниц</div>";
echo "</div>";

echo "<div style='background: #fff3e0; padding: 15px; border-radius: 8px; text-align: center;'>";
echo "<h3 style='margin: 0 0 10px 0; color: #e65100;'>⚠️ Предупреждения</h3>";
echo "<div style='font-size: 2rem; font-weight: bold; color: #e65100;'>$warning_pages</div>";
echo "<div style='font-size: 0.9rem; color: #e65100;'>страниц</div>";
echo "</div>";

echo "</div>";

// Detailed results table
echo "<h3>📋 Детальные результаты</h3>";
echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
echo "<thead>";
echo "<tr style='background: #f5f5f5;'>";
echo "<th style='padding: 12px; border: 1px solid #ddd; text-align: left;'>Страница</th>";
echo "<th style='padding: 12px; border: 1px solid #ddd; text-align: left;'>Статус</th>";
echo "<th style='padding: 12px; border: 1px solid #ddd; text-align: left;'>Файл</th>";
echo "<th style='padding: 12px; border: 1px solid #ddd; text-align: left;'>Проблемы</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

foreach ($test_results as $key => $result) {
    $status_icon = $result['content_loaded'] ? '✅' : '❌';
    $status_color = $result['content_loaded'] ? 'green' : 'red';

    $problems = array_merge($result['errors'], $result['warnings']);
    $problems_text = empty($problems) ? 'Нет' : implode('; ', $problems);

    echo "<tr>";
    echo "<td style='padding: 12px; border: 1px solid #ddd;'>{$result['name']}</td>";
    echo "<td style='padding: 12px; border: 1px solid #ddd; color: $status_color;'>$status_icon " .
         ($result['content_loaded'] ? 'Работает' : 'Ошибка') . "</td>";
    echo "<td style='padding: 12px; border: 1px solid #ddd;'>" .
         ($result['file_exists'] ? '✅' : '❌') . " " .
         ($result['file_readable'] ? '✅' : '❌') . "</td>";
    echo "<td style='padding: 12px; border: 1px solid #ddd;'>$problems_text</td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";

echo "<div style='margin: 30px 0; padding: 20px; background: #e3f2fd; border-radius: 8px;'>";
echo "<h3>🔗 Ссылки для ручного тестирования</h3>";
echo "<ul style='list-style: none; padding: 0;'>";
foreach ($admin_pages as $page) {
    echo "<li style='margin: 8px 0;'>
        <a href='{$page['url']}' target='_blank' style='color: #1976d2; text-decoration: none;'>
            ▶ {$page['title']} ({$page['url']})
        </a>
    </li>";
}
echo "</ul>";
echo "</div>";

echo "<div style='margin: 30px 0; padding: 20px; background: #f3e5f5; border-radius: 8px;'>";
echo "<h3>🔧 Инструкции по тестированию</h3>";
echo "<ol style='margin: 10px 0; padding-left: 20px;'>";
echo "<li>Перейдите по каждой ссылке выше в новой вкладке</li>";
echo "<li>Проверьте, что страница загружается без ошибок</li>";
echo "<li>Убедитесь, что header, sidebar и footer отображаются корректно</li>";
echo "<li>Протестируйте все кнопки и ссылки на странице</li>";
echo "<li>Проверьте адаптивность на мобильных устройствах</li>";
echo "<li>Убедитесь, что все формы работают (если есть)</li>";
echo "</ol>";
echo "</div>";

echo "<hr style='margin: 40px 0; border: none; border-top: 2px solid #eee;'>";
echo "<p style='text-align: center; color: #666; font-size: 0.9rem;'>";
echo "Тестирование завершено: " . date('Y-m-d H:i:s') . " | ";
echo "Общее время: " . (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) . " сек";
echo "</p>";
?>
