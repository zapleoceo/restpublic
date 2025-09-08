<?php
// Файл для логирования (алиас для db.php)
// Обеспечивает совместимость с существующим кодом

require_once 'db.php';

// Алиасы для функций логирования
function log_admin_action($telegram_id, $username, $action_type, $description, $details = []) {
    return logAdminAction($action_type, $description, $details);
}
?>
