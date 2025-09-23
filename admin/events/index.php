<?php
// Настройки страницы для layout
$page_title = 'Управление событиями - North Republic';
$page_header = 'Управление событиями';
$page_description = 'Администрирование событий ресторана';

// Breadcrumbs для навигации
$breadcrumb = [
    ['title' => 'Управление событиями']
];

require_once __DIR__ . '/../../vendor/autoload.php';

// Загружаем переменные окружения
$envFile = dirname(__DIR__, 2) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}


try {
    // Подключение к MongoDB
    $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
    $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
    
    $client = new MongoDB\Client($mongodbUrl);
    $db = $client->$dbName;
    $eventsCollection = $db->events;
    
    // Получаем все события, отсортированные по дате
    $events = $eventsCollection->find(
        [],
        ['sort' => ['date' => 1, 'time' => 1]]
    )->toArray();
    
    // Конвертируем ObjectId в строки
    foreach ($events as $index => $event) {
        $events[$index]['_id'] = (string)$event['_id'];
        $events[$index]['id'] = (string)$event['_id'];
    }
    
    // Создаем календарь начиная с последнего понедельника
    $calendarDays = [];
    $today = new DateTime();
    
    // Находим последний понедельник
    $lastMonday = clone $today;
    $dayOfWeek = (int)$today->format('N'); // 1 = понедельник, 7 = воскресенье
    if ($dayOfWeek == 1) {
        // Если сегодня понедельник, начинаем с него
        $lastMonday = clone $today;
    } else {
        // Иначе идем назад к понедельнику
        $daysBack = $dayOfWeek - 1;
        $lastMonday->sub(new DateInterval('P' . $daysBack . 'D'));
    }
    
    // Создаем календарь на 30 дней начиная с последнего понедельника
    for ($i = 0; $i < 30; $i++) {
        $currentDate = clone $lastMonday;
        $currentDate->add(new DateInterval('P' . $i . 'D'));
        $dateStr = $currentDate->format('Y-m-d');
        
        // Ищем события на эту дату
        $dayEvents = array_filter($events, function($event) use ($dateStr) {
            return $event['date'] === $dateStr;
        });
        
        $calendarDays[] = [
            'date' => $dateStr,
            'day' => $currentDate->format('d'),
            'month' => $currentDate->format('m'),
            'year' => $currentDate->format('Y'),
            'weekday' => $currentDate->format('l'),
            'weekday_short' => $currentDate->format('D'),
            'weekday_ru' => [
                'Monday' => 'Пн',
                'Tuesday' => 'Вт', 
                'Wednesday' => 'Ср',
                'Thursday' => 'Чт',
                'Friday' => 'Пт',
                'Saturday' => 'Сб',
                'Sunday' => 'Вс'
            ][$currentDate->format('l')],
            'events' => array_values($dayEvents)
        ];
    }
    
} catch (Exception $e) {
    error_log("Ошибка загрузки событий: " . $e->getMessage());
    $events = [];
    $calendarDays = [];
}

// Отладочная информация
error_log("Загружено событий: " . count($events));
if (count($events) > 0) {
    error_log("Первое событие: " . json_encode($events[0]));
}

// Генерируем контент
ob_start();
?>

<style>
        /* Стили для таблицы событий */
        .events-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin: 20px 0;
        }

        .events-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .events-header h2 {
            margin: 0;
            color: #495057;
            font-size: 18px;
        }

        .load-past-btn, .load-future-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s ease;
        }

        .load-past-btn:hover, .load-future-btn:hover {
            background: #5a6268;
        }

        .load-future-btn {
            background: #28a745;
        }

        .load-future-btn:hover {
            background: #218838;
        }

        .events-table {
            width: 100%;
            border-collapse: collapse;
        }

        .events-table th {
            background: #e9ecef;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            font-size: 14px;
        }

        .events-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }

        .events-table tr:hover {
            background-color: #f8f9fa;
        }

        .event-date {
            font-family: monospace;
            color: #495057;
            font-weight: 500;
            line-height: 1.2;
        }

        .date-line {
            font-size: 14px;
            font-weight: 500;
        }

        .weekday {
            color: #6c757d;
            font-size: 11px;
            font-weight: normal;
            margin-top: 2px;
        }

        .event-time {
            white-space: nowrap;
            font-family: monospace;
            color: #007bff;
            font-weight: 600;
        }

        .event-title {
            font-weight: 600;
            color: #212529;
            font-size: 15px;
        }

        .event-conditions {
            color: #6c757d;
            font-size: 14px;
            max-width: 300px;
            word-wrap: break-word;
        }


        .event-link {
            text-align: center;
        }

        .link-btn {
            background: #007bff;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
            transition: background-color 0.2s ease;
            min-width: 24px;
            text-align: center;
        }

        .link-btn:hover {
            background: #0056b3;
            color: white;
            text-decoration: none;
        }

        .no-link {
            color: #6c757d;
            font-style: italic;
        }

        .event-thumbnail {
            text-align: center;
            padding: 8px;
        }

        .thumbnail-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            cursor: pointer;
            transition: transform 0.2s ease;
            border: 2px solid #dee2e6;
        }

        .thumbnail-img:hover {
            transform: scale(1.1);
            border-color: #007bff;
        }

        .default-thumbnail {
            opacity: 0.7;
        }

        .event-status {
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-badge.active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-badge.inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .event-comment {
            color: #6c757d;
            font-size: 12px;
            font-style: italic;
            max-width: 150px;
            word-wrap: break-word;
        }

        .no-events-row {
            background-color: #f8f9fa;
            border-left: 4px solid #dee2e6;
        }

        .no-events-cell {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-style: italic;
        }

        .no-events-text {
            font-size: 14px;
            color: #adb5bd;
        }

        .form-group.error input,
        .form-group.error textarea {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .form-group.error label {
            color: #dc3545;
        }

        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .form-group.error .error-message {
            display: block;
        }

        .form-section {
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }

        .form-section h3 {
            margin: 0 0 1rem 0;
            color: #495057;
            font-size: 16px;
            font-weight: 600;
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #6c757d;
            font-size: 12px;
        }

        .image-preview, .current-image {
            margin-top: 15px;
            padding: 15px;
            background: white;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
        }

        .preview-container, .current-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .preview-container img, .current-container img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #dee2e6;
        }

        .preview-info, .current-info {
            flex: 1;
        }

        .preview-info p, .current-info p {
            margin: 0 0 5px 0;
            font-weight: 600;
            color: #495057;
        }

        .preview-info small, .current-info small {
            color: #6c757d;
            font-size: 12px;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }

        .event-actions {
            white-space: nowrap;
        }

        .btn {
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 12px;
            padding: 6px 12px;
            margin: 2px;
            font-weight: 500;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-edit {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-edit:hover {
            background-color: #e0a800;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        /* Стили для выбора существующих изображений */
        .existing-images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            background: #f8f9fa;
        }

        .image-option {
            position: relative;
            cursor: pointer;
            border: 2px solid #dee2e6;
            border-radius: 4px;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .image-option:hover {
            border-color: #007bff;
            transform: scale(1.05);
        }

        .image-option.selected {
            border-color: #28a745;
            box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.25);
        }

        .image-option img {
            width: 100%;
            height: 80px;
            object-fit: cover;
            display: block;
        }

        .image-option-info {
            padding: 5px;
            background: white;
            font-size: 10px;
            color: #6c757d;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }

        .image-option-count {
            position: absolute;
            top: 2px;
            right: 2px;
            background: rgba(0, 123, 255, 0.8);
            color: white;
            font-size: 10px;
            padding: 2px 4px;
            border-radius: 2px;
            font-weight: bold;
        }

        .loading-images {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 20px;
        }

        .no-images {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 20px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .empty-state p {
            margin-bottom: 20px;
            font-size: 16px;
        }

        /* Модальное окно */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            background: #007bff;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 18px;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 15px;
            overflow-y: auto;
            flex: 1;
        }

        .form-group {
            margin-bottom: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }

        .modal-footer {
            padding: 10px 15px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-shrink: 0;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        /* Модальное окно для изображений */
        .image-modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
        }

        .image-modal-header {
            background: #007bff;
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .image-modal-header h3 {
            margin: 0;
            font-size: 16px;
        }

        .image-modal-body {
            padding: 20px;
            text-align: center;
            overflow: auto;
            flex: 1;
        }

        .full-size-image {
            max-width: 100%;
            max-height: 70vh;
            object-fit: contain;
            border-radius: 4px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .events-container {
                margin: 10px 0;
            }
            
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }
            
            .events-table {
                min-width: 800px;
            }

            .events-table th,
            .events-table td {
                padding: 8px 6px;
                font-size: 12px;
                white-space: nowrap;
            }

            .event-conditions {
                max-width: 150px;
                font-size: 11px;
                white-space: normal;
                word-wrap: break-word;
            }

            .event-comment {
                max-width: 120px;
                font-size: 10px;
                white-space: normal;
                word-wrap: break-word;
            }
            
            .event-title {
                max-width: 150px;
                white-space: normal;
                word-wrap: break-word;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                margin: 5% auto;
                max-height: 90vh;
            }

            .modal-body {
                padding: 15px;
                max-height: 60vh;
                overflow-y: auto;
            }
            
            .events-header {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }
            
            .header-buttons {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .events-table th,
            .events-table td {
                padding: 8px 6px;
                font-size: 12px;
            }

            .btn {
                padding: 4px 8px;
                font-size: 11px;
                margin: 1px;
            }
        }
</style>

<div class="admin-content">
                <div class="events-container">
                    <div class="events-header">
                        <h2>События (начиная с понедельника)</h2>
                        <div class="header-buttons">
                            <button class="btn btn-primary" onclick="openEventModal()">
                                ➕ Добавить событие
                            </button>
                            <button class="load-past-btn" onclick="loadPastEvents()">
                                📅 Показать прошлые
                            </button>
                            <button class="load-future-btn" onclick="loadFutureEvents()">
                                📅 Показать еще +7 дней
                            </button>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="events-table">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Время</th>
                                <th>Название (RU)</th>
                                <th>Условия участия (RU)</th>
                                <th>Ссылка</th>
                                <th>Изображение</th>
                                <th>Описание (RU)</th>
                                <th>Статус</th>
                                <th>Создано</th>
                                <th>Обновлено</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody id="eventsTableBody">
                            <?php foreach ($calendarDays as $day): ?>
                                <?php if (empty($day['events'])): ?>
                                    <!-- День без событий -->
                                    <tr class="no-events-row">
                                        <td class="event-date">
                                            <div class="date-line"><?= $day['day'] ?>.<?= $day['month'] ?>.<?= $day['year'] ?></div>
                                            <div class="weekday"><?= $day['weekday_ru'] ?></div>
                                        </td>
                                        <td colspan="10" class="no-events-cell">
                                            <span class="no-events-text">Событий не запланировано</span>
                                            <button class="add-event-btn" onclick="openEventModal()" title="Добавить событие">+</button>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <!-- События на этот день -->
                                    <?php foreach ($day['events'] as $event): ?>
                                        <tr data-event-id="<?= htmlspecialchars($event['id']) ?>">
                                            <td class="event-date">
                                                <div class="date-line"><?= htmlspecialchars($event['date']) ?></div>
                                                <div class="weekday"><?= $day['weekday_ru'] ?></div>
                                            </td>
                                            <td class="event-time"><?= htmlspecialchars($event['time']) ?></td>
                                            <td class="event-title"><?= htmlspecialchars($event['title_ru'] ?? $event['title'] ?? '') ?></td>
                                            <td class="event-conditions"><?= htmlspecialchars($event['conditions_ru'] ?? $event['conditions'] ?? '') ?></td>
                                            <td class="event-link">
                                                <?php if (!empty($event['link'] ?? $event['description_link'])): ?>
                                                    <a href="<?= htmlspecialchars($event['link'] ?? $event['description_link']) ?>" target="_blank" class="link-btn">🔗</a>
                                                <?php else: ?>
                                                    <span class="no-link">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="event-thumbnail">
                                                <?php 
                                                $imageUrl = '/images/logo.png'; // Используем логотип как дефолтное изображение
                                                if (!empty($event['image'])) {
                                                    // Проверяем, является ли это GridFS file_id
                                                    if (preg_match('/^[a-f\d]{24}$/i', $event['image'])) {
                                                        $imageUrl = "/api/image.php?id=" . $event['image'];
                                                    } else {
                                                        // Если это не GridFS ID, используем логотип
                                                        $imageUrl = '/images/logo.png';
                                                    }
                                                }
                                                ?>
                                                <img src="<?= htmlspecialchars($imageUrl) ?>" 
                                                     alt="<?= htmlspecialchars($event['title_ru'] ?? $event['title'] ?? '') ?>" 
                                                     class="thumbnail-img <?= $imageUrl === '/images/logo.png' ? 'default-thumbnail' : '' ?>" 
                                                     onclick="showImageModal('<?= htmlspecialchars($imageUrl) ?>', '<?= htmlspecialchars($event['title_ru'] ?? $event['title'] ?? '') ?>')">
                                            </td>
                                            <td class="event-comment">
                                                <?php if (!empty($event['description_ru'])): ?>
                                                    <?php 
                                                    $description = htmlspecialchars($event['description_ru']);
                                                    echo strlen($description) > 50 ? substr($description, 0, 50) . '...' : $description;
                                                    ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td class="event-status">
                                                <span class="status-badge <?= $event['is_active'] ? 'active' : 'inactive' ?>">
                                                    <?= $event['is_active'] ? 'Активно' : 'Неактивно' ?>
                                                </span>
                                            </td>
                                            <td class="event-created">
                                                <?php 
                                                if (isset($event['created_at']) && $event['created_at'] instanceof MongoDB\BSON\UTCDateTime) {
                                                    echo $event['created_at']->toDateTime()->format('d.m.Y H:i');
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td class="event-updated">
                                                <?php 
                                                if (isset($event['updated_at']) && $event['updated_at'] instanceof MongoDB\BSON\UTCDateTime) {
                                                    echo $event['updated_at']->toDateTime()->format('d.m.Y H:i');
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td class="event-actions">
                                                <button class="btn btn-edit" onclick="editEvent('<?= $event['id'] ?>')" title="Редактировать">✏️</button>
                                                <button class="btn btn-primary" onclick="copyEvent('<?= $event['id'] ?>')" title="Копировать">📋</button>
                                                <button class="btn btn-danger" onclick="deleteEvent('<?= $event['id'] ?>')" title="Удалить">🗑️</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                        </table>
                    </div>
                </div>
            </div>
</div>

    <!-- Модальное окно для добавления/редактирования события -->
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Добавить событие</h2>
                <button class="modal-close" onclick="closeEventModal()">&times;</button>
            </div>

            <form id="eventForm" class="modal-body" enctype="multipart/form-data">
                <input type="hidden" id="eventId" name="event_id">

                <!-- Основная информация -->
                <div class="form-section">
                    <h3>Название события</h3>
                    
                    <div class="form-group">
                        <label for="eventTitleRu">Название (русский) *</label>
                        <input type="text" id="eventTitleRu" name="title_ru" required maxlength="200" placeholder="Введите название события на русском">
                        <div class="error-message">Название события на русском обязательно для заполнения</div>
                    </div>

                    <div class="form-group">
                        <label for="eventTitleEn">Название (английский)</label>
                        <input type="text" id="eventTitleEn" name="title_en" maxlength="200" placeholder="Введите название события на английском">
                        <small>Если не заполнено, будет использовано русское название</small>
                    </div>

                    <div class="form-group">
                        <label for="eventTitleVi">Название (вьетнамский)</label>
                        <input type="text" id="eventTitleVi" name="title_vi" maxlength="200" placeholder="Введите название события на вьетнамском">
                        <small>Если не заполнено, будет использовано русское название</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="eventDate">Дата проведения *</label>
                            <input type="date" id="eventDate" name="date" required>
                            <div class="error-message">Дата обязательна для заполнения</div>
                        </div>
                        <div class="form-group">
                            <label for="eventTime">Время начала *</label>
                            <input type="time" id="eventTime" name="time" required>
                            <div class="error-message">Время обязательно для заполнения</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="eventConditionsRu">Условия участия (русский) *</label>
                        <textarea id="eventConditionsRu" name="conditions_ru" required rows="3" maxlength="500" placeholder="Опишите условия участия на русском (цена, требования, ограничения)"></textarea>
                        <div class="error-message">Условия участия на русском обязательны для заполнения</div>
                    </div>

                    <div class="form-group">
                        <label for="eventConditionsEn">Условия участия (английский)</label>
                        <textarea id="eventConditionsEn" name="conditions_en" rows="3" maxlength="500" placeholder="Опишите условия участия на английском"></textarea>
                        <small>Если не заполнено, будет использован русский текст</small>
                    </div>

                    <div class="form-group">
                        <label for="eventConditionsVi">Условия участия (вьетнамский)</label>
                        <textarea id="eventConditionsVi" name="conditions_vi" rows="3" maxlength="500" placeholder="Опишите условия участия на вьетнамском"></textarea>
                        <small>Если не заполнено, будет использован русский текст</small>
                    </div>
                </div>

                <!-- Дополнительная информация -->
                <div class="form-section">
                    <h3>Описание события</h3>
                    
                    <div class="form-group">
                        <label for="eventDescriptionRu">Описание (русский)</label>
                        <textarea id="eventDescriptionRu" name="description_ru" rows="3" maxlength="1000" placeholder="Краткое описание события на русском"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="eventDescriptionEn">Описание (английский)</label>
                        <textarea id="eventDescriptionEn" name="description_en" rows="3" maxlength="1000" placeholder="Краткое описание события на английском"></textarea>
                        <small>Если не заполнено, будет использован русский текст</small>
                    </div>

                    <div class="form-group">
                        <label for="eventDescriptionVi">Описание (вьетнамский)</label>
                        <textarea id="eventDescriptionVi" name="description_vi" rows="3" maxlength="1000" placeholder="Краткое описание события на вьетнамском"></textarea>
                        <small>Если не заполнено, будет использован русский текст</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="eventLink">Ссылка на подробное описание</label>
                        <input type="url" id="eventLink" name="link" placeholder="https://t.me/gamezone_vietnam/2117" value="https://t.me/gamezone_vietnam/2117">
                        <small>Ссылка на страницу с подробным описанием события</small>
                        <div class="error-message">Неверный формат ссылки</div>
                    </div>

                    <div class="form-group">
                        <label for="eventCategory">Категория события</label>
                        <select id="eventCategory" name="category">
                            <option value="general">Общее</option>
                            <option value="entertainment">Развлечения</option>
                            <option value="food">Еда и напитки</option>
                            <option value="music">Музыка</option>
                            <option value="sports">Спорт</option>
                            <option value="cultural">Культурные мероприятия</option>
                        </select>
                        <small>Категория для группировки событий</small>
                    </div>

                    <div class="form-group">
                        <label for="eventDescriptionRu">Описание (RU)</label>
                        <textarea id="eventDescriptionRu" name="description_ru" rows="3" maxlength="1000" placeholder="Описание события на русском языке"></textarea>
                        <small>Описание события, которое будет отображаться на сайте</small>
                    </div>
                </div>

                <!-- Изображение -->
                <div class="form-section">
                    <h3>Выбор изображения</h3>
                    
                    <!-- Выбор существующих изображений -->
                    <div class="form-group">
                        <label>Использовать существующее изображение</label>
                        <div id="existingImages" class="existing-images-grid">
                            <div class="loading-images">Загрузка изображений...</div>
                        </div>
                        <small>Выберите одно из уже использованных изображений</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="useExistingImage" onchange="toggleImageUpload()">
                            <span class="checkmark"></span>
                            Использовать существующее изображение
                        </label>
                    </div>
                    
                    <!-- Загрузка нового изображения -->
                    <div id="newImageUpload" class="form-group">
                        <label for="eventImage">Загрузить новое изображение</label>
                        <input type="file" id="eventImage" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                        <small>Поддерживаемые форматы: JPEG, PNG, GIF, WebP. Максимальный размер: 5MB</small>
                        <div class="error-message">Поддерживаются только изображения: JPEG, PNG, GIF, WebP. Максимальный размер: 5MB</div>
                    </div>

                    <div id="imagePreview" class="image-preview" style="display: none;">
                        <div class="preview-container">
                            <img id="previewImage" src="" alt="Предварительный просмотр">
                            <div class="preview-info">
                                <p id="previewText">Новое изображение</p>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="clearImagePreview()">Удалить</button>
                            </div>
                        </div>
                    </div>

                    <div id="currentImage" class="current-image" style="display: none;">
                        <div class="current-container">
                            <img id="currentImageSrc" src="" alt="Текущее изображение">
                            <div class="current-info">
                                <p>Текущее изображение</p>
                                <small>Загрузите новое изображение, чтобы заменить</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Настройки -->
                <div class="form-section">
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="eventIsActive" name="is_active" checked>
                            <span class="checkmark"></span>
                            Активное событие
                        </label>
                        <small>Неактивные события не отображаются на сайте</small>
                    </div>
                </div>
            </form>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEventModal()">
                    Отмена
                </button>
                <button type="button" class="btn btn-primary" onclick="saveEvent()" id="saveButton">
                    <span id="saveButtonText">Сохранить</span>
                    <span id="saveButtonSpinner" style="display: none;">⏳ Сохранение...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Модальное окно для просмотра изображений -->
    <div id="imageModal" class="modal">
        <div class="image-modal-content">
            <div class="image-modal-header">
                <h3 id="imageModalTitle">Просмотр изображения</h3>
                <button class="modal-close" onclick="closeImageModal()">&times;</button>
            </div>
            <div class="image-modal-body">
                <img id="modalImage" src="" alt="" class="full-size-image">
            </div>
        </div>
    </div>

<script>
        // Версия скрипта для избежания кэширования
        console.log('Events script loaded, version:', <?php echo time(); ?>);
        // Переменные для отслеживания количества загруженных событий
        let pastEventsLoaded = 0;
        let futureEventsLoaded = 0;
        const allEvents = <?php echo json_encode($events); ?>;
        const loadedEventIds = new Set(); // Отслеживаем уже загруженные события
        const deletingEvents = new Set(); // Отслеживаем удаляемые события
        
        // Отладочная информация
        console.log('Всего событий загружено:', allEvents.length);
        if (allEvents.length > 0) {
            console.log('Первое событие:', allEvents[0]);
        }
        
        // Инициализируем Set с уже загруженными событиями (начиная с понедельника)
        const today = new Date();
        
        // Находим последний понедельник
        const lastMonday = new Date(today);
        const dayOfWeek = today.getDay(); // 0 = воскресенье, 1 = понедельник
        const daysBack = dayOfWeek === 0 ? 6 : dayOfWeek - 1; // Если воскресенье, то 6 дней назад
        lastMonday.setDate(today.getDate() - daysBack);
        
        // 30 дней начиная с понедельника
        const endDate = new Date(lastMonday);
        endDate.setDate(lastMonday.getDate() + 30);
        
        allEvents.forEach(event => {
            const eventDate = new Date(event.date);
            if (eventDate >= lastMonday && eventDate <= endDate) {
                loadedEventIds.add(event.id);
            }
        });
        
        console.log('Уже загружено событий:', loadedEventIds.size);

        // Переменные для работы с изображениями
        let existingImages = [];
        let selectedImageId = null;

        // Функции для работы с событиями
        function openEventModal(eventId = null) {
            const modal = document.getElementById('eventModal');
            const form = document.getElementById('eventForm');
            const title = document.getElementById('modalTitle');

            // Очищаем ошибки при открытии модального окна
            clearFormErrors();
            
            // Скрываем все превью изображений
            hideImagePreview();
            document.getElementById('currentImage').style.display = 'none';
            
            // Загружаем существующие изображения
            loadExistingImages();
            
            // Сбрасываем выбор изображения
            selectedImageId = null;
            document.getElementById('useExistingImage').checked = false;
            toggleImageUpload();

            if (eventId) {
                title.textContent = 'Редактировать событие';
                loadEventData(eventId);
            } else {
                title.textContent = 'Добавить событие';
                form.reset();
                document.getElementById('eventIsActive').checked = true;
                
                // Устанавливаем ссылку по умолчанию для новых событий
                document.getElementById('eventLink').value = 'https://t.me/gamezone_vietnam/2117';
                
                // Для нового события показываем логотип как дефолтное изображение
                showDefaultImage();
            }

            modal.style.display = 'block';
        }

        function showDefaultImage() {
            const currentImageDiv = document.getElementById('currentImage');
            const currentImageSrc = document.getElementById('currentImageSrc');
            
            currentImageSrc.src = "/images/logo.png";
            currentImageDiv.style.display = 'block';
        }
        
        function closeEventModal() {
            document.getElementById('eventModal').style.display = 'none';
        }
        
        function loadEventData(eventId) {
            // Загружаем данные события из API вместо DOM
            fetch('/admin/events/api.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const event = data.data.find(e => e.id === eventId);
                        if (event) {
                            // Заполняем форму данными события
                            document.getElementById('eventId').value = eventId;
                            console.log('Заполняем eventId в форме:', eventId);
                            console.log('Поле eventId после заполнения:', document.getElementById('eventId').value);
                            
                            // Заполняем поля названий
                            document.getElementById('eventTitleRu').value = event.title_ru || event.title || '';
                            document.getElementById('eventTitleEn').value = event.title_en || '';
                            document.getElementById('eventTitleVi').value = event.title_vi || '';
                            
                            // Заполняем поля описаний
                            document.getElementById('eventDescriptionRu').value = event.description_ru || '';
                            document.getElementById('eventDescriptionEn').value = event.description_en || '';
                            document.getElementById('eventDescriptionVi').value = event.description_vi || '';
                            
                            // Заполняем поля условий
                            document.getElementById('eventConditionsRu').value = event.conditions_ru || event.conditions || '';
                            document.getElementById('eventConditionsEn').value = event.conditions_en || '';
                            document.getElementById('eventConditionsVi').value = event.conditions_vi || '';
                            
                            // Заполняем остальные поля
                            document.getElementById('eventDate').value = event.date || '';
                            document.getElementById('eventTime').value = event.time || '';
                            document.getElementById('eventLink').value = event.link || event.description_link || '';
                            document.getElementById('eventCategory').value = event.category || 'general';
                            document.getElementById('eventDescriptionRu').value = event.description_ru || '';
                            document.getElementById('eventIsActive').checked = event.is_active !== false;
                            
                            // Обрабатываем изображение
                            showCurrentImage(event);
                            
                            // Очищаем поле выбора файла и превью
                            document.getElementById('eventImage').value = '';
                            hideImagePreview();
                        } else {
                            alert('Событие не найдено');
                        }
                    } else {
                        alert('Ошибка загрузки данных: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Ошибка загрузки события:', error);
                    alert('Ошибка загрузки события: ' + error.message);
                });
        }

        function showCurrentImage(event) {
            const currentImageDiv = document.getElementById('currentImage');
            const currentImageSrc = document.getElementById('currentImageSrc');
            
            if (event.image && /^[a-f\d]{24}$/i.test(event.image)) {
                // Есть изображение в GridFS
                currentImageSrc.src = "/api/image.php?id=" + event.image;
                currentImageDiv.style.display = 'block';
            } else {
                // Нет изображения, показываем логотип
                currentImageSrc.src = "/images/logo.png";
                currentImageDiv.style.display = 'block';
            }
        }

        function hideImagePreview() {
            document.getElementById('imagePreview').style.display = 'none';
        }

        function clearImagePreview() {
            document.getElementById('eventImage').value = '';
            hideImagePreview();
        }
        
        function saveEvent() {
            const form = document.getElementById('eventForm');
            const eventId = document.getElementById('eventId').value;
            const saveButton = document.getElementById('saveButton');
            const saveButtonText = document.getElementById('saveButtonText');
            const saveButtonSpinner = document.getElementById('saveButtonSpinner');

            console.log('=== saveEvent вызвана ===');
            console.log('eventId:', eventId);
            console.log('eventId type:', typeof eventId);
            console.log('eventId length:', eventId ? eventId.length : 'undefined');
            console.log('Форма:', form);
            console.log('Скрытое поле eventId:', document.getElementById('eventId'));

            // Валидация данных перед отправкой
            const validationResult = validateEventForm();
            console.log('Результат валидации:', validationResult);
            
            if (!validationResult.isValid) {
                console.log('Валидация не пройдена, ошибки:', validationResult.errors);
                alert('Ошибки в форме:\n' + validationResult.errors.join('\n'));
                return;
            }

            // Показываем индикатор загрузки
            saveButton.disabled = true;
            saveButtonText.style.display = 'none';
            saveButtonSpinner.style.display = 'inline';

            console.log('Валидация пройдена, отправляем данные...');

            // Проверяем, есть ли файл для загрузки или выбрано существующее изображение
            const imageInput = document.getElementById('eventImage');
            const hasImageFile = imageInput.files.length > 0;
            const useExistingImage = document.getElementById('useExistingImage').checked;
            const hasSelectedImage = selectedImageId !== null;
            
            console.log('Есть файл для загрузки:', hasImageFile);
            console.log('Использовать существующее изображение:', useExistingImage);
            console.log('Выбрано существующее изображение:', hasSelectedImage);
            console.log('ID выбранного изображения:', selectedImageId);
            
            if (hasImageFile) {
                console.log('Файл:', imageInput.files[0]);
            }

            // Определяем метод (POST для создания, POST для обновления с файлом, PUT для обновления без файла)
            let method;
            if (!eventId) {
                method = 'POST'; // Создание нового события
            } else if (hasImageFile || (useExistingImage && hasSelectedImage)) {
                method = 'POST'; // Обновление с файлом или существующим изображением - используем POST
            } else {
                method = 'PUT'; // Обновление без файла - используем PUT
            }
            console.log('Метод запроса:', method);

            let requestBody;
            let contentType;
            
            if (method === 'POST' || hasImageFile || (useExistingImage && hasSelectedImage)) {
                // Для создания или обновления с файлом/изображением используем FormData
                requestBody = new FormData(form);
                requestBody.set('is_active', document.getElementById('eventIsActive').checked);
                
                // Добавляем выбранное существующее изображение
                if (useExistingImage && hasSelectedImage) {
                    requestBody.set('existing_image_id', selectedImageId);
                    console.log('Добавляем existing_image_id в FormData:', selectedImageId);
                }
                
                // Для PUT запроса добавляем event_id (обязательно!)
                if (method === 'PUT') {
                    console.log('Добавляем event_id в FormData:', eventId);
                    console.log('Тип eventId:', typeof eventId);
                    console.log('eventId пустой?', !eventId);
                    
                    if (eventId) {
                        requestBody.set('event_id', eventId);
                        console.log('event_id добавлен в FormData');
                    } else {
                        console.error('eventId пустой! Нельзя добавить в FormData');
                        alert('Ошибка: ID события не найден. Попробуйте перезагрузить страницу.');
                        return;
                    }
                }
                
                contentType = undefined; // FormData сам установит Content-Type
            } else {
                // Для обновления без файла используем JSON
                requestBody = JSON.stringify({
                    event_id: eventId,
                    title_ru: document.getElementById('eventTitleRu').value,
                    title_en: document.getElementById('eventTitleEn').value,
                    title_vi: document.getElementById('eventTitleVi').value,
                    description_ru: document.getElementById('eventDescriptionRu').value,
                    description_en: document.getElementById('eventDescriptionEn').value,
                    description_vi: document.getElementById('eventDescriptionVi').value,
                    conditions_ru: document.getElementById('eventConditionsRu').value,
                    conditions_en: document.getElementById('eventConditionsEn').value,
                    conditions_vi: document.getElementById('eventConditionsVi').value,
                    date: document.getElementById('eventDate').value,
                    time: document.getElementById('eventTime').value,
                    link: document.getElementById('eventLink').value,
                    category: document.getElementById('eventCategory').value,
                    description_ru: document.getElementById('eventDescriptionRu').value,
                    is_active: document.getElementById('eventIsActive').checked
                });
                contentType = 'application/json';
            }

            const fetchOptions = {
                method: method,
                body: requestBody
            };

            if (contentType) {
                fetchOptions.headers = {
                    'Content-Type': contentType
                };
            }

            console.log('Отправляем запрос на:', '/admin/events/api.php');
            console.log('Метод:', method);
            console.log('Опции запроса:', fetchOptions);
            
            // Отладка FormData
            if (requestBody instanceof FormData) {
                console.log('=== FormData содержимое ===');
                for (let [key, value] of requestBody.entries()) {
                    console.log(key + ':', value);
                }
                console.log('=== Конец FormData ===');
            }
            
            fetch('/admin/events/api.php', fetchOptions)
            .then(response => {
                console.log('Получен ответ:', response.status, response.statusText);
                if (!response.ok) {
                    // Пытаемся получить текст ошибки
                    return response.text().then(text => {
                        console.log('Текст ошибки:', text);
                        throw new Error(`HTTP error! status: ${response.status}, text: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Данные ответа:', data);
                if (data.success) {
                    alert(data.message);
                    closeEventModal();
                    // Небольшая задержка перед перезагрузкой, чтобы изображение успело сохраниться
                    setTimeout(() => {
                        location.reload(); // Перезагружаем страницу для обновления списка
                    }, 500);
                } else {
                    alert('Ошибка: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Ошибка сохранения события:', error);
                alert('Ошибка сохранения события: ' + error.message);
            })
            .finally(() => {
                // Скрываем индикатор загрузки
                saveButton.disabled = false;
                saveButtonText.style.display = 'inline';
                saveButtonSpinner.style.display = 'none';
            });
        }

        function validateEventForm() {
            const errors = [];
            
            console.log('Начинаем валидацию формы...');
            
            // Очищаем предыдущие ошибки
            clearFormErrors();
            
            // Проверяем обязательные поля
            const titleRu = document.getElementById('eventTitleRu').value.trim();
            const date = document.getElementById('eventDate').value.trim();
            const time = document.getElementById('eventTime').value.trim();
            const conditionsRu = document.getElementById('eventConditionsRu').value.trim();
            
            console.log('Поля формы:', { titleRu, date, time, conditionsRu });
            
            if (!titleRu) {
                errors.push('• Название события на русском обязательно для заполнения');
                showFieldError('eventTitleRu');
            }
            
            if (!date) {
                errors.push('• Дата обязательна для заполнения');
                showFieldError('eventDate');
            } else {
                // Проверяем формат даты
                const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
                if (!dateRegex.test(date)) {
                    errors.push('• Неверный формат даты. Используйте YYYY-MM-DD');
                    showFieldError('eventDate');
                }
            }
            
            if (!time) {
                errors.push('• Время обязательно для заполнения');
                showFieldError('eventTime');
            } else {
                // Проверяем формат времени
                const timeRegex = /^\d{2}:\d{2}$/;
                if (!timeRegex.test(time)) {
                    errors.push('• Неверный формат времени. Используйте HH:MM');
                    showFieldError('eventTime');
                }
            }
            
            if (!conditionsRu) {
                errors.push('• Условия участия на русском обязательны для заполнения');
                showFieldError('eventConditionsRu');
            }
            
            // Проверяем ссылку (если заполнена)
            const link = document.getElementById('eventLink').value.trim();
            if (link) {
                try {
                    new URL(link);
                } catch (e) {
                    errors.push('• Неверный формат ссылки');
                    showFieldError('eventLink');
                }
            }
            
            // Проверяем размер файла (если загружен)
            const imageInput = document.getElementById('eventImage');
            if (imageInput.files.length > 0) {
                const file = imageInput.files[0];
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    errors.push('• Размер изображения не должен превышать 5MB');
                    showFieldError('eventImage');
                }
                
                // Проверяем тип файла
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    errors.push('• Поддерживаются только изображения: JPEG, PNG, GIF, WebP');
                    showFieldError('eventImage');
                }
            }
            
            return {
                isValid: errors.length === 0,
                errors: errors
            };
        }

        function showFieldError(fieldId) {
            const field = document.getElementById(fieldId);
            const formGroup = field.closest('.form-group');
            formGroup.classList.add('error');
        }

        function clearFormErrors() {
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach(group => {
                group.classList.remove('error');
            });
        }

        function editEvent(eventId) {
            openEventModal(eventId);
        }

        function copyEvent(eventId) {
            // Загружаем данные события из API
            fetch('/admin/events/api.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const event = data.data.find(e => e.id === eventId);
                        if (event) {
                            // Открываем модалку для создания нового события
                            openEventModal();
                            
                            // Заполняем форму данными копируемого события
                            document.getElementById('eventId').value = ''; // Очищаем ID для создания нового
                            
                            // Заполняем поля названий
                            document.getElementById('eventTitleRu').value = event.title_ru || event.title || '';
                            document.getElementById('eventTitleEn').value = event.title_en || '';
                            document.getElementById('eventTitleVi').value = event.title_vi || '';
                            
                            // Заполняем поля описаний
                            document.getElementById('eventDescriptionRu').value = event.description_ru || '';
                            document.getElementById('eventDescriptionEn').value = event.description_en || '';
                            document.getElementById('eventDescriptionVi').value = event.description_vi || '';
                            
                            // Заполняем поля условий
                            document.getElementById('eventConditionsRu').value = event.conditions_ru || event.conditions || '';
                            document.getElementById('eventConditionsEn').value = event.conditions_en || '';
                            document.getElementById('eventConditionsVi').value = event.conditions_vi || '';
                            
                            // Заполняем остальные поля
                            document.getElementById('eventDate').value = event.date || '';
                            document.getElementById('eventTime').value = event.time || '';
                            document.getElementById('eventLink').value = event.link || event.description_link || '';
                            document.getElementById('eventCategory').value = event.category || 'general';
                            document.getElementById('eventDescriptionRu').value = event.description_ru || '';
                            document.getElementById('eventIsActive').checked = event.is_active !== false;
                            
                            // Показываем текущее изображение
                            showCurrentImage(event);
                            
                            // Обновляем заголовок модалки
                            document.getElementById('modalTitle').textContent = 'Копировать событие';
                        } else {
                            alert('Событие не найдено');
                        }
                    } else {
                        alert('Ошибка загрузки данных: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Ошибка загрузки события для копирования:', error);
                    alert('Ошибка загрузки события: ' + error.message);
                });
        }

        function deleteEvent(eventId) {
            console.log('deleteEvent вызвана для ID:', eventId);
            console.log('Стек вызовов:', new Error().stack);
            
            // Проверяем, не удаляется ли уже это событие
            if (deletingEvents.has(eventId)) {
                console.log('Событие уже удаляется:', eventId);
                return;
            }
            
            const eventRow = document.querySelector(`tr[data-event-id="${eventId}"]`);
            if (!eventRow) {
                console.log('Строка события не найдена, возможно уже удалена');
                return;
            }
            
            const deleteButton = eventRow.querySelector('button.btn-danger');
            if (deleteButton && deleteButton.disabled) {
                console.log('Кнопка уже отключена, событие уже удаляется');
                return;
            }
            
            if (confirm('Вы уверены, что хотите удалить это событие?')) {
                console.log('Пользователь подтвердил удаление');
                
                // Добавляем событие в список удаляемых
                deletingEvents.add(eventId);
                console.log('Событие добавлено в deletingEvents:', eventId);
                console.log('Текущий список удаляемых:', Array.from(deletingEvents));
                
                // Отключаем кнопку удаления - ищем в строке таблицы
                const eventRow = document.querySelector(`tr[data-event-id="${eventId}"]`);
                const deleteButton = eventRow ? eventRow.querySelector('button.btn-danger') : null;
                if (deleteButton) {
                    deleteButton.disabled = true;
                    deleteButton.textContent = '⏳';
                }
                
                // Отправляем JSON вместо FormData
                const requestData = {
                    event_id: eventId
                };

                fetch('/admin/events/api.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload(); // Перезагружаем страницу для обновления списка
                    } else {
                        alert('Ошибка: ' + data.message);
                        // Восстанавливаем кнопку при ошибке
                        if (deleteButton) {
                            deleteButton.disabled = false;
                            deleteButton.textContent = '🗑️';
                        }
                    }
                })
                .catch(error => {
                    console.error('Ошибка удаления события:', error);
                    alert('Ошибка удаления события: ' + error.message);
                    // Восстанавливаем кнопку при ошибке
                    if (deleteButton) {
                        deleteButton.disabled = false;
                        deleteButton.textContent = '🗑️';
                    }
                })
                .finally(() => {
                    // Удаляем событие из списка удаляемых
                    deletingEvents.delete(eventId);
                });
            }
        }

        function loadPastEvents() {
            const today = new Date();
            
            // Получаем все прошлые события, отсортированные по дате (новые сначала)
            const allPastEvents = allEvents.filter(event => {
                const eventDate = new Date(event.date);
                return eventDate < today && !loadedEventIds.has(event.id);
            }).sort((a, b) => new Date(b.date) - new Date(a.date)); // Сортируем по убыванию даты
            
            console.log(`Всего прошлых событий доступно: ${allPastEvents.length}`);
            console.log(`Уже загружено прошлых событий: ${pastEventsLoaded}`);
            
            // Берем следующие 10 событий
            const nextBatch = allPastEvents.slice(pastEventsLoaded, pastEventsLoaded + 10);
            
            if (nextBatch.length === 0) {
                alert('Больше прошлых событий нет');
                return;
            }
            
            console.log(`Загружаем ${nextBatch.length} прошлых событий (пакет ${Math.floor(pastEventsLoaded / 10) + 1})`);
            
            // Обновляем счетчик
            pastEventsLoaded += nextBatch.length;
            
            // Добавляем события в таблицу
            const tbody = document.getElementById('eventsTableBody');
            nextBatch.forEach(event => {
                // Добавляем событие в список загруженных
                loadedEventIds.add(event.id);
                
                const row = document.createElement('tr');
                row.setAttribute('data-event-id', event.id);
                
                // Формируем ссылку
                const linkHtml = (event.link || event.description_link) ? 
                    `<a href="${event.link || event.description_link}" target="_blank" class="link-btn">🔗</a>` : 
                    '<span class="no-link">-</span>';
                
                // Формируем миниатюру - только из GridFS
                let imageSrc = '/images/logo.png'; // Используем логотип как дефолтное изображение
                if (event.image) {
                    // Проверяем, является ли это GridFS file_id
                    if (/^[a-f\d]{24}$/i.test(event.image)) {
                        imageSrc = "/api/image.php?id=" + event.image;
                    } else {
                        // Если это не GridFS ID, используем логотип
                        imageSrc = '/images/logo.png';
                    }
                }
                const imageAlt = event.image ? (event.title_ru || event.title || '') : 'Дефолтное изображение';
                const thumbnailClass = event.image ? 'thumbnail-img' : 'thumbnail-img default-thumbnail';
                const thumbnailHtml = `<img src="${imageSrc}" alt="${imageAlt}" class="${thumbnailClass}" onclick="showImageModal('${imageSrc}', '${imageAlt}')">`;
                
                // Формируем статус
                const statusClass = event.is_active ? 'active' : 'inactive';
                const statusText = event.is_active ? 'Активно' : 'Неактивно';
                const statusHtml = `<span class="status-badge ${statusClass}">${statusText}</span>`;
                
                // Обрезаем описание до 50 символов
                const description = event.description_ru || '-';
                const truncatedDescription = description.length > 50 ? description.substring(0, 50) + '...' : description;
                
                // Получаем день недели для события
                const eventDate = new Date(event.date + 'T00:00:00');
                const weekdays = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
                const weekday = weekdays[eventDate.getDay()];
                
                row.innerHTML = `
                    <td class="event-date">
                        <div class="date-line">${eventDate.toLocaleDateString('ru-RU')}</div>
                        <div class="weekday">${weekday}</div>
                    </td>
                    <td class="event-time">${event.time}</td>
                    <td class="event-title">${event.title_ru || event.title || ''}</td>
                    <td class="event-conditions">${event.conditions_ru || event.conditions || ''}</td>
                    <td class="event-link">${linkHtml}</td>
                    <td class="event-thumbnail">${thumbnailHtml}</td>
                    <td class="event-status">${statusHtml}</td>
                    <td class="event-comment">${truncatedDescription}</td>
                    <td class="event-actions">
                        <button class="btn btn-edit" onclick="editEvent('${event.id}')" title="Редактировать">✏️</button>
                        <button class="btn btn-primary" onclick="copyEvent('${event.id}')" title="Копировать">📋</button>
                        <button class="btn btn-danger" onclick="deleteEvent('${event.id}')" title="Удалить">🗑️</button>
                    </td>
                `;
                tbody.insertBefore(row, tbody.firstChild);
            });
            
            // Обновляем текст кнопки
            const loadBtn = document.querySelector('.load-past-btn');
            const remainingEvents = allEvents.filter(event => {
                const eventDate = new Date(event.date);
                return eventDate < today && !loadedEventIds.has(event.id);
            }).length;
            
            if (remainingEvents > 0) {
                loadBtn.textContent = `📅 Показать еще прошлые (осталось ${remainingEvents})`;
            } else {
                loadBtn.textContent = `📅 Все прошлые события загружены`;
                loadBtn.disabled = true;
            }
            
            console.log(`Всего загружено событий: ${loadedEventIds.size}`);
        }

        function loadFutureEvents() {
            const today = new Date();
            
            // Получаем все будущие события, отсортированные по дате (старые сначала)
            const allFutureEvents = allEvents.filter(event => {
                const eventDate = new Date(event.date);
                return eventDate > today && !loadedEventIds.has(event.id);
            }).sort((a, b) => new Date(a.date) - new Date(b.date)); // Сортируем по возрастанию даты
            
            console.log(`Всего будущих событий доступно: ${allFutureEvents.length}`);
            console.log(`Уже загружено будущих событий: ${futureEventsLoaded}`);
            
            // Берем следующие 7 дней событий (или все доступные, если меньше)
            const nextBatch = allFutureEvents.slice(futureEventsLoaded, futureEventsLoaded + 7);
            
            if (nextBatch.length === 0) {
                alert('Больше будущих событий нет');
                return;
            }
            
            console.log(`Загружаем ${nextBatch.length} будущих событий (пакет ${Math.floor(futureEventsLoaded / 7) + 1})`);
            
            // Обновляем счетчик
            futureEventsLoaded += nextBatch.length;
            
            // Добавляем события в таблицу
            const tbody = document.getElementById('eventsTableBody');
            nextBatch.forEach(event => {
                // Добавляем событие в список загруженных
                loadedEventIds.add(event.id);
                
                const row = document.createElement('tr');
                row.setAttribute('data-event-id', event.id);
                
                // Формируем ссылку
                const linkHtml = (event.link || event.description_link) ? 
                    `<a href="${event.link || event.description_link}" target="_blank" class="link-btn">🔗</a>` : 
                    '<span class="no-link">-</span>';
                
                // Формируем миниатюру - только из GridFS
                let imageSrc = '/images/logo.png'; // Используем логотип как дефолтное изображение
                if (event.image) {
                    // Проверяем, является ли это GridFS file_id
                    if (/^[a-f\d]{24}$/i.test(event.image)) {
                        imageSrc = "/api/image.php?id=" + event.image;
                    } else {
                        // Если это не GridFS ID, используем логотип
                        imageSrc = '/images/logo.png';
                    }
                }
                const imageAlt = event.image ? (event.title_ru || event.title || '') : 'Дефолтное изображение';
                const thumbnailClass = event.image ? 'thumbnail-img' : 'thumbnail-img default-thumbnail';
                const thumbnailHtml = `<img src="${imageSrc}" alt="${imageAlt}" class="${thumbnailClass}" onclick="showImageModal('${imageSrc}', '${imageAlt}')">`;
                
                // Формируем статус
                const statusClass = event.is_active ? 'active' : 'inactive';
                const statusText = event.is_active ? 'Активно' : 'Неактивно';
                const statusHtml = `<span class="status-badge ${statusClass}">${statusText}</span>`;
                
                // Обрезаем описание до 50 символов
                const description = event.description_ru || '-';
                const truncatedDescription = description.length > 50 ? description.substring(0, 50) + '...' : description;
                
                // Получаем день недели для события
                const eventDate = new Date(event.date + 'T00:00:00');
                const weekdays = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
                const weekday = weekdays[eventDate.getDay()];
                
                row.innerHTML = `
                    <td class="event-date">
                        <div class="date-line">${eventDate.toLocaleDateString('ru-RU')}</div>
                        <div class="weekday">${weekday}</div>
                    </td>
                    <td class="event-time">${event.time}</td>
                    <td class="event-title">${event.title_ru || event.title || ''}</td>
                    <td class="event-conditions">${event.conditions_ru || event.conditions || ''}</td>
                    <td class="event-link">${linkHtml}</td>
                    <td class="event-thumbnail">${thumbnailHtml}</td>
                    <td class="event-status">${statusHtml}</td>
                    <td class="event-comment">${truncatedDescription}</td>
                    <td class="event-actions">
                        <button class="btn btn-edit" onclick="editEvent('${event.id}')" title="Редактировать">✏️</button>
                        <button class="btn btn-primary" onclick="copyEvent('${event.id}')" title="Копировать">📋</button>
                        <button class="btn btn-danger" onclick="deleteEvent('${event.id}')" title="Удалить">🗑️</button>
                    </td>
                `;
                tbody.appendChild(row); // Добавляем в конец таблицы
            });
            
            // Обновляем текст кнопки
            const loadBtn = document.querySelector('.load-future-btn');
            const remainingEvents = allEvents.filter(event => {
                const eventDate = new Date(event.date);
                return eventDate > today && !loadedEventIds.has(event.id);
            }).length;
            
            if (remainingEvents > 0) {
                loadBtn.textContent = `📅 Показать еще +7 дней (осталось ${remainingEvents})`;
            } else {
                loadBtn.textContent = `📅 Все будущие события загружены`;
                loadBtn.disabled = true;
            }
            
            console.log(`Всего загружено событий: ${loadedEventIds.size}`);
        }

        // Закрытие модального окна при клике вне его отключено

        // Функции для работы с модальным окном изображений
        function showImageModal(imageSrc, imageTitle) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const modalTitle = document.getElementById('imageModalTitle');
            
            modalImage.src = imageSrc;
            modalImage.alt = imageTitle;
            modalTitle.textContent = imageTitle;
            
            modal.style.display = 'block';
        }
        
        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // Обработчик изменения изображения
        document.getElementById('eventImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const imagePreview = document.getElementById('imagePreview');
            const previewImage = document.getElementById('previewImage');
            const previewText = document.getElementById('previewText');
            
            if (file) {
                // Валидация файла
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                
                if (file.size > maxSize) {
                    alert('Размер изображения не должен превышать 5MB');
                    this.value = '';
                    return;
                }
                
                if (!allowedTypes.includes(file.type)) {
                    alert('Поддерживаются только изображения: JPEG, PNG, GIF, WebP');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewText.textContent = `Новое изображение (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.style.display = 'none';
            }
        });

        // Закрытие модального окна по Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEventModal();
                closeImageModal();
            }
        });

        // Функции для работы с существующими изображениями
        function loadExistingImages() {
            const container = document.getElementById('existingImages');
            container.innerHTML = '<div class="loading-images">Загрузка изображений...</div>';
            
            fetch('/admin/events/api.php?action=get_images')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        existingImages = data.data;
                        displayExistingImages();
                    } else {
                        container.innerHTML = '<div class="no-images">Ошибка загрузки изображений</div>';
                    }
                })
                .catch(error => {
                    console.error('Ошибка загрузки изображений:', error);
                    container.innerHTML = '<div class="no-images">Ошибка загрузки изображений</div>';
                });
        }

        function displayExistingImages() {
            const container = document.getElementById('existingImages');
            
            if (existingImages.length === 0) {
                container.innerHTML = '<div class="no-images">Нет доступных изображений</div>';
                return;
            }
            
            let html = '';
            existingImages.forEach(image => {
                const imageUrl = `/api/image.php?id=${image.image_id}`;
                html += `
                    <div class="image-option" onclick="selectExistingImage('${image.image_id}')" data-image-id="${image.image_id}">
                        <img src="${imageUrl}" alt="Изображение" onerror="this.src='/images/logo.png'">
                        <div class="image-option-count">${image.usage_count}</div>
                        <div class="image-option-info">
                            ${image.first_used_in}<br>
                            ${image.first_used_date}
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function selectExistingImage(imageId) {
            // Убираем выделение с предыдущего изображения
            document.querySelectorAll('.image-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Выделяем выбранное изображение
            const selectedOption = document.querySelector(`[data-image-id="${imageId}"]`);
            if (selectedOption) {
                selectedOption.classList.add('selected');
            }
            
            selectedImageId = imageId;
            console.log('Выбрано изображение:', imageId);
        }

        function toggleImageUpload() {
            const useExisting = document.getElementById('useExistingImage').checked;
            const newImageUpload = document.getElementById('newImageUpload');
            const existingImagesContainer = document.getElementById('existingImages');
            
            if (useExisting) {
                newImageUpload.style.display = 'none';
                existingImagesContainer.style.display = 'block';
                // Очищаем поле загрузки файла
                document.getElementById('eventImage').value = '';
                hideImagePreview();
            } else {
                newImageUpload.style.display = 'block';
                existingImagesContainer.style.display = 'none';
                // Сбрасываем выбор существующего изображения
                selectedImageId = null;
                document.querySelectorAll('.image-option').forEach(option => {
                    option.classList.remove('selected');
                });
            }
        }
        
        // Функции для мобильного меню
        function toggleSidebar() {
            console.log('toggleSidebar вызвана');
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('active');
                console.log('Меню переключено, классы:', sidebar.classList.toString());
            } else {
                console.error('Элементы меню не найдены');
            }
        }
        
        function closeSidebar() {
            console.log('closeSidebar вызвана');
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            if (sidebar && overlay) {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
                console.log('Меню закрыто');
            }
        }
        
        // Инициализация мобильного меню
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM загружен, инициализация мобильного меню');
            
            // Закрытие меню при клике на пункт меню
            const menuItems = document.querySelectorAll('.menu-item a');
            menuItems.forEach(item => {
                item.addEventListener('click', closeSidebar);
            });
            
            // Добавляем обработчики для touch устройств
            const menuBtn = document.querySelector('.mobile-menu-btn');
            if (menuBtn) {
                console.log('Кнопка меню найдена, добавляем обработчики');
                
                // Обработчик для клика
                menuBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Клик по кнопке меню');
                    toggleSidebar();
                });
                
                // Обработчик для touch
                menuBtn.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Touch по кнопке меню');
                    toggleSidebar();
                });
                
                // Обработчик для touchend
                menuBtn.addEventListener('touchend', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
            } else {
                console.error('Кнопка меню не найдена');
            }
            
            // Обработчик для оверлея
            const overlay = document.querySelector('.sidebar-overlay');
            if (overlay) {
                overlay.addEventListener('click', closeSidebar);
                overlay.addEventListener('touchstart', closeSidebar);
            }
        });
</script>

<?php
$content = ob_get_clean();

// Подключаем layout
require_once __DIR__ . '/../includes/layout.php';
?>
