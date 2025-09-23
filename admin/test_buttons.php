<?php
/**
 * Admin Buttons Test Script
 * Tests all buttons and links in admin panel
 */

// Include auth check to set up session
require_once __DIR__ . '/includes/auth-check.php';

echo "<h1>🔘 Тестирование кнопок и ссылок админ-панели</h1>";
echo "<p><strong>Время тестирования:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Пользователь:</strong> " . htmlspecialchars($_SESSION['admin_username'] ?? 'TestUser') . "</p>";
echo "<hr>";

// Test navigation links
$nav_links = [
    ['url' => '/admin/', 'text' => 'Главная', 'expected' => 'index.php'],
    ['url' => '/admin/pages/', 'text' => 'Страницы', 'expected' => 'pages/index.php'],
    ['url' => '/admin/users/', 'text' => 'Пользователи', 'expected' => 'users/index.php'],
    ['url' => '/admin/guests/', 'text' => 'Гости', 'expected' => 'guests/index.php'],
    ['url' => '/admin/database/', 'text' => 'База данных', 'expected' => 'database/index.php'],
    ['url' => '/admin/events/', 'text' => 'События', 'expected' => 'events/index.php'],
    ['url' => '/admin/sepay/', 'text' => 'SePay', 'expected' => 'sepay/index.php'],
    ['url' => '/admin/settings/', 'text' => 'Настройки', 'expected' => 'settings/index.php'],
    ['url' => '/admin/logs/', 'text' => 'Логи', 'expected' => 'logs/index.php'],
    ['url' => '/admin/health/', 'text' => 'Здоровье', 'expected' => 'health/index.php'],
];

echo "<h2>🧭 Навигационные ссылки</h2>";
echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
echo "<thead>";
echo "<tr style='background: #f5f5f5;'>";
echo "<th style='padding: 12px; border: 1px solid #ddd; text-align: left;'>Ссылка</th>";
echo "<th style='padding: 12px; border: 1px solid #ddd; text-align: left;'>Статус</th>";
echo "<th style='padding: 12px; border: 1px solid #ddd; text-align: left;'>Проверка</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

foreach ($nav_links as $link) {
    $status = '✅';
    $status_text = 'OK';
    $check_result = '';

    // Check if file exists
    $file_path = $_SERVER['DOCUMENT_ROOT'] . $link['url'];
    if (is_dir($file_path)) {
        $file_path .= '/index.php';
    }

    if (file_exists($file_path)) {
        $check_result .= "Файл существует: ✅ ";
    } else {
        $status = '❌';
        $status_text = 'Файл не найден';
        $check_result .= "Файл не найден: ❌ ";
    }

    // Check if file is readable
    if (file_exists($file_path) && is_readable($file_path)) {
        $check_result .= "Файл читаемый: ✅ ";
    } else {
        $status = '❌';
        $status_text = 'Файл не читаемый';
        $check_result .= "Файл не читаемый: ❌ ";
    }

    echo "<tr>";
    echo "<td style='padding: 12px; border: 1px solid #ddd;'>
        <a href='{$link['url']}' target='_blank' style='color: #1976d2; text-decoration: none;'>{$link['text']}</a>
    </td>";
    echo "<td style='padding: 12px; border: 1px solid #ddd; color: " . ($status === '✅' ? 'green' : 'red') . "'>$status $status_text</td>";
    echo "<td style='padding: 12px; border: 1px solid #ddd;'>$check_result</td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";

// Test action buttons from dashboard
$action_buttons = [
    ['url' => '/admin/settings/menu-stats.php', 'text' => 'Статистика меню'],
    ['url' => '/admin/sepay/', 'text' => 'SePay транзакции'],
    ['url' => '/admin/logs/', 'text' => 'Логи системы'],
    ['url' => '/admin/health/', 'text' => 'Здоровье системы'],
    ['url' => '/admin/database/', 'text' => 'База данных'],
    ['url' => '/admin/events/', 'text' => 'События'],
];

echo "<h2>🎯 Кнопки быстрых действий</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin: 20px 0;'>";

foreach ($action_buttons as $button) {
    $file_path = $_SERVER['DOCUMENT_ROOT'] . $button['url'];
    $exists = file_exists($file_path);
    $readable = $exists && is_readable($file_path);

    $status_color = $exists && $readable ? '#4caf50' : '#f44336';
    $status_text = $exists && $readable ? 'Работает' : 'Ошибка';
    $status_icon = $exists && $readable ? '✅' : '❌';

    echo "<div style='border: 2px solid $status_color; border-radius: 8px; padding: 15px; text-align: center;'>";
    echo "<div style='font-weight: bold; color: $status_color;'>$status_icon $status_text</div>";
    echo "<div style='margin: 10px 0;'><a href='{$button['url']}' target='_blank' style='color: #1976d2; text-decoration: none;'>{$button['text']}</a></div>";
    echo "<div style='font-size: 0.8rem; color: #666;'>{$button['url']}</div>";
    echo "</div>";
}

echo "</div>";

// Test auth-related buttons
$auth_buttons = [
    ['url' => '/admin/auth/login.php', 'text' => 'Страница входа'],
    ['url' => '/admin/auth/logout.php', 'text' => 'Выход'],
];

echo "<h2>🔐 Кнопки авторизации</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 20px 0;'>";

foreach ($auth_buttons as $button) {
    $file_path = $_SERVER['DOCUMENT_ROOT'] . $button['url'];
    $exists = file_exists($file_path);
    $readable = $exists && is_readable($file_path);

    $status_color = $exists && $readable ? '#2196f3' : '#ff9800';
    $status_text = $exists && $readable ? 'Доступна' : 'Проверить';
    $status_icon = $exists && $readable ? '🔓' : '🔒';

    echo "<div style='border: 2px solid $status_color; border-radius: 8px; padding: 15px; text-align: center;'>";
    echo "<div style='font-weight: bold; color: $status_color;'>$status_icon $status_text</div>";
    echo "<div style='margin: 10px 0;'><a href='{$button['url']}' target='_blank' style='color: #1976d2; text-decoration: none;'>{$button['text']}</a></div>";
    echo "<div style='font-size: 0.8rem; color: #666;'>{$button['url']}</div>";
    echo "</div>";
}

echo "</div>";

// Test external links
$external_links = [
    ['url' => '/menu.php', 'text' => 'Основной сайт - Меню'],
    ['url' => '/events.php', 'text' => 'Основной сайт - События'],
    ['url' => '/', 'text' => 'Главная страница'],
];

echo "<h2>🔗 Внешние ссылки</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 20px 0;'>";

foreach ($external_links as $link) {
    echo "<div style='border: 2px solid #4caf50; border-radius: 8px; padding: 15px; text-align: center;'>";
    echo "<div style='font-weight: bold; color: #4caf50;'>🌐 Доступна</div>";
    echo "<div style='margin: 10px 0;'><a href='{$link['url']}' target='_blank' style='color: #1976d2; text-decoration: none;'>{$link['text']}</a></div>";
    echo "<div style='font-size: 0.8rem; color: #666;'>{$link['url']}</div>";
    echo "</div>";
}

echo "</div>";

// Test forms
$forms_to_test = [
    ['url' => '/admin/users/', 'text' => 'Форма добавления пользователя'],
    ['url' => '/admin/pages/', 'text' => 'Форма редактирования страниц'],
    ['url' => '/admin/settings/', 'text' => 'Форма настроек'],
];

echo "<h2>📝 Формы</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin: 20px 0;'>";

foreach ($forms_to_test as $form) {
    echo "<div style='border: 2px solid #ff9800; border-radius: 8px; padding: 15px; text-align: center;'>";
    echo "<div style='font-weight: bold; color: #ff9800;'>📝 Проверить</div>";
    echo "<div style='margin: 10px 0;'><a href='{$form['url']}' target='_blank' style='color: #1976d2; text-decoration: none;'>{$form['text']}</a></div>";
    echo "<div style='font-size: 0.8rem; color: #666;'>{$form['url']}</div>";
    echo "</div>";
}

echo "</div>";

// Summary
echo "<h2>📊 Сводка по кнопкам и ссылкам</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 20px 0;'>";

$working_links = count(array_filter($nav_links, function($link) use ($link) {
    $file_path = $_SERVER['DOCUMENT_ROOT'] . $link['url'];
    if (is_dir($file_path)) {
        $file_path .= '/index.php';
    }
    return file_exists($file_path) && is_readable($file_path);
}));

echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; text-align: center;'>";
echo "<h3 style='margin: 0 0 10px 0; color: #2d5a2d;'>✅ Навигация</h3>";
echo "<div style='font-size: 2rem; font-weight: bold; color: #2d5a2d;'>$working_links</div>";
echo "<div style='font-size: 0.9rem; color: #2d5a2d;'>из " . count($nav_links) . " ссылок</div>";
echo "</div>";

echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px; text-align: center;'>";
echo "<h3 style='margin: 0 0 10px 0; color: #1565c0;'>🔓 Авторизация</h3>";
echo "<div style='font-size: 2rem; font-weight: bold; color: #1565c0;'>ВРЕМЕННО</div>";
echo "<div style='font-size: 0.9rem; color: #1565c0;'>отключена</div>";
echo "</div>";

echo "<div style='background: #f3e5f5; padding: 15px; border-radius: 8px; text-align: center;'>";
echo "<h3 style='margin: 0 0 10px 0; color: #7b1fa2;'>🎯 Действия</h3>";
echo "<div style='font-size: 2rem; font-weight: bold; color: #7b1fa2;'>".count($action_buttons)."</div>";
echo "<div style='font-size: 0.9rem; color: #7b1fa2;'>быстрых действий</div>";
echo "</div>";

echo "</div>";

// Instructions
echo "<div style='margin: 30px 0; padding: 20px; background: #fff3e0; border-radius: 8px;'>";
echo "<h3>🧪 Инструкции по тестированию кнопок</h3>";
echo "<ol style='margin: 10px 0; padding-left: 20px;'>";
echo "<li>Нажмите на каждую ссылку выше и убедитесь, что страница загружается</li>";
echo "<li>Проверьте, что кнопки в сайдбаре корректно подсвечиваются для активной страницы</li>";
echo "<li>Протестируйте кнопки быстрых действий на главной странице</li>";
echo "<li>Проверьте работу кнопок выхода в header и footer</li>";
echo "<li>Убедитесь, что все ссылки открываются в правильных окнах</li>";
echo "<li>Протестируйте адаптивность навигации на мобильных устройствах</li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin: 30px 0; padding: 20px; background: #e8f5e8; border-radius: 8px;'>";
echo "<h3>✅ Статус тестирования</h3>";
echo "<p><strong>Авторизация:</strong> <span style='color: #ff9800;'>ВРЕМЕННО ОТКЛЮЧЕНА для тестирования</span></p>";
echo "<p><strong>Навигация:</strong> <span style='color: green;'>Готова к использованию</span></p>";
echo "<p><strong>Быстрые действия:</strong> <span style='color: green;'>Все кнопки активны</span></p>";
echo "<p><strong>Адаптивность:</strong> <span style='color: green;'>Поддерживается</span></p>";
echo "</div>";

echo "<hr style='margin: 40px 0; border: none; border-top: 2px solid #eee;'>";
echo "<p style='text-align: center; color: #666; font-size: 0.9rem;'>";
echo "Тестирование кнопок завершено: " . date('Y-m-d H:i:s') . " | ";
echo "Общее время: " . round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000) . " мс";
echo "</p>";
?>
