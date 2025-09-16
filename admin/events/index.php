<?php
// Страница управления событиями в админке
session_start();
// require_once __DIR__ . '/../includes/auth-check.php'; // Временно отключено для тестирования
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

$pageTitle = 'Управление событиями';
$pageDescription = 'Администрирование событий ресторана';

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
    
    // Создаем календарь на 30 дней вперед
    $calendarDays = [];
    $startDate = new DateTime();
    for ($i = 0; $i < 30; $i++) {
        $currentDate = clone $startDate;
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
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Админка</title>
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
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

        .load-past-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s ease;
        }

        .load-past-btn:hover {
            background: #5a6268;
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
            white-space: nowrap;
            font-family: monospace;
            color: #495057;
            font-weight: 500;
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
            padding: 4px 8px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
            transition: background-color 0.2s ease;
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

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background-color: #c82333;
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
            max-height: 90vh;
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
            padding: 20px;
            overflow-y: auto;
            flex: 1;
        }

        .form-group {
            margin-bottom: 15px;
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
            padding: 15px 20px;
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

            .events-table th,
            .events-table td {
                padding: 10px 8px;
                font-size: 13px;
            }

            .event-conditions {
                max-width: 200px;
                font-size: 12px;
            }

            .event-comment {
                max-width: 150px;
                font-size: 11px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }

            .modal-body {
                padding: 20px;
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
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="admin-container">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="admin-main">
            <div class="admin-header">
                <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
            </div>

            <div class="admin-content">
                <div class="events-container">
                    <div class="events-header">
                        <h2>События (14 дней вперед)</h2>
                        <div class="header-buttons">
                            <button class="btn btn-primary" onclick="openEventModal()">
                                ➕ Добавить событие
                            </button>
                            <button class="load-past-btn" onclick="loadPastEvents()">
                                📅 Показать прошлые
                            </button>
                        </div>
                    </div>

                    <table class="events-table">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Время</th>
                                <th>Название</th>
                                <th>Условия участия</th>
                                <th>Ссылка</th>
                                <th>Изображение</th>
                                <th>Комментарий</th>
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
                                        <td class="event-date"><?= $day['day'] ?>.<?= $day['month'] ?>.<?= $day['year'] ?></td>
                                        <td colspan="10" class="no-events-cell">
                                            <span class="no-events-text">Событий не запланировано</span>
                                            <button class="add-event-btn" onclick="openEventModal()" title="Добавить событие">+</button>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <!-- События на этот день -->
                                    <?php foreach ($day['events'] as $event): ?>
                                        <tr data-event-id="<?= htmlspecialchars($event['id']) ?>">
                                            <td class="event-date"><?= htmlspecialchars($event['date']) ?></td>
                                            <td class="event-time"><?= htmlspecialchars($event['time']) ?></td>
                                            <td class="event-title"><?= htmlspecialchars($event['title']) ?></td>
                                            <td class="event-conditions"><?= htmlspecialchars($event['conditions']) ?></td>
                                            <td class="event-link">
                                                <?php if (!empty($event['description_link'])): ?>
                                                    <a href="<?= htmlspecialchars($event['description_link']) ?>" target="_blank" class="link-btn">🔗 Открыть</a>
                                                <?php else: ?>
                                                    <span class="no-link">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="event-thumbnail">
                                                <?php 
                                                $imageUrl = '/images/event-default.png';
                                                if (!empty($event['image'])) {
                                                    // Проверяем, является ли это GridFS file_id
                                                    if (preg_match('/^[a-f\d]{24}$/i', $event['image'])) {
                                                        $imageUrl = "/api/image.php?id=" . $event['image'];
                                                    } else {
                                                        // Если это не GridFS ID, используем изображение по умолчанию
                                                        $imageUrl = '/images/event-default.png';
                                                    }
                                                }
                                                ?>
                                                <img src="<?= htmlspecialchars($imageUrl) ?>" 
                                                     alt="<?= htmlspecialchars($event['title']) ?>" 
                                                     class="thumbnail-img <?= $imageUrl === '/images/event-default.png' ? 'default-thumbnail' : '' ?>" 
                                                     onclick="showImageModal('<?= htmlspecialchars($imageUrl) ?>', '<?= htmlspecialchars($event['title']) ?>')">
                                            </td>
                                            <td class="event-comment">
                                                <?php if (!empty($event['comment'])): ?>
                                                    <?php 
                                                    $comment = htmlspecialchars($event['comment']);
                                                    echo strlen($comment) > 50 ? substr($comment, 0, 50) . '...' : $comment;
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
                                                <button class="btn btn-delete" onclick="deleteEvent('<?= $event['id'] ?>')" title="Удалить">🗑️</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Модальное окно для добавления/редактирования события -->
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Добавить событие</h2>
                <button class="modal-close" onclick="closeEventModal()">&times;</button>
            </div>

            <form id="eventForm" class="modal-body">
                <input type="hidden" id="eventId" name="event_id">

                <div class="form-group">
                    <label for="eventTitle">Название события *</label>
                    <input type="text" id="eventTitle" name="title" required>
                    <div class="error-message">Название события обязательно для заполнения</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="eventDate">Дата *</label>
                        <input type="date" id="eventDate" name="date" required>
                        <div class="error-message">Дата обязательна для заполнения</div>
                    </div>
                    <div class="form-group">
                        <label for="eventTime">Время *</label>
                        <input type="time" id="eventTime" name="time" required>
                        <div class="error-message">Время обязательно для заполнения</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="eventConditions">Условия участия *</label>
                    <input type="text" id="eventConditions" name="conditions" required>
                    <div class="error-message">Условия участия обязательны для заполнения</div>
                </div>


                <div class="form-group">
                    <label for="eventDescriptionLink">Ссылка на описание</label>
                    <input type="url" id="eventDescriptionLink" name="description_link">
                    <div class="error-message">Неверный формат ссылки</div>
                </div>

                <div class="form-group">
                    <label for="eventImage">Картинка</label>
                    <input type="file" id="eventImage" name="image" accept="image/*">
                    <small>Если не выбрана, будет использована дефолтная картинка</small>
                    <div class="error-message">Поддерживаются только изображения: JPEG, PNG, GIF, WebP. Максимальный размер: 5MB</div>
                    <div id="imagePreview" style="display: none; margin-top: 10px;"></div>
                </div>

                <div class="form-group">
                    <label for="eventComment">Комментарий (только для админов)</label>
                    <textarea id="eventComment" name="comment" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="eventIsActive" name="is_active" checked>
                        <span class="checkmark"></span>
                        Активное событие
                    </label>
                </div>
            </form>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEventModal()">
                    Отмена
                </button>
                <button type="button" class="btn btn-primary" onclick="saveEvent()">
                    Сохранить
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

    <script src="/admin/assets/js/admin.js?v=<?php echo time(); ?>"></script>
    <script>
        // Переменная для отслеживания количества загруженных прошлых событий
        let pastEventsLoaded = 0;
        const allEvents = <?php echo json_encode($events); ?>;
        const loadedEventIds = new Set(); // Отслеживаем уже загруженные события
        
        // Отладочная информация
        console.log('Всего событий загружено:', allEvents.length);
        if (allEvents.length > 0) {
            console.log('Первое событие:', allEvents[0]);
        }
        
        // Инициализируем Set с уже загруженными событиями (14 дней вперед)
        const today = new Date();
        const futureDate = new Date(today);
        futureDate.setDate(today.getDate() + 14); // 14 дней вперед
        
        allEvents.forEach(event => {
            const eventDate = new Date(event.date);
            if (eventDate >= today && eventDate <= futureDate) {
                loadedEventIds.add(event.id);
            }
        });
        
        console.log('Уже загружено событий:', loadedEventIds.size);

        // Функции для работы с событиями
        function openEventModal(eventId = null) {
            const modal = document.getElementById('eventModal');
            const form = document.getElementById('eventForm');
            const title = document.getElementById('modalTitle');
            const imagePreview = document.getElementById('imagePreview');

            // Очищаем ошибки при открытии модального окна
            clearFormErrors();
            
            // Очищаем превью изображения
            imagePreview.style.display = 'none';
            imagePreview.innerHTML = '';

            if (eventId) {
                title.textContent = 'Редактировать событие';
                loadEventData(eventId);
            } else {
                title.textContent = 'Добавить событие';
                form.reset();
                document.getElementById('eventIsActive').checked = true;
            }

            modal.style.display = 'block';
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
                            document.getElementById('eventTitle').value = event.title || '';
                            document.getElementById('eventDate').value = event.date || '';
                            document.getElementById('eventTime').value = event.time || '';
                            document.getElementById('eventConditions').value = event.conditions || '';
                            document.getElementById('eventComment').value = event.comment || '';
                            document.getElementById('eventDescriptionLink').value = event.description_link || '';
                            document.getElementById('eventIsActive').checked = event.is_active !== false;
                            
                            // Обрабатываем изображение
                            const imagePreview = document.getElementById('imagePreview');
                            const imageInput = document.getElementById('eventImage');
                            
                            if (event.image) {
                                // Определяем URL изображения - только из GridFS
                                let imageUrl = '/images/event-default.png';
                                if (event.image) {
                                    // Проверяем, является ли это GridFS file_id
                                    if (/^[a-f\d]{24}$/i.test(event.image)) {
                                        imageUrl = "/api/image.php?id=" + event.image;
                                    } else {
                                        // Если это не GridFS ID, используем изображение по умолчанию
                                        imageUrl = '/images/event-default.png';
                                    }
                                }
                                
                                imagePreview.innerHTML = `
                                    <img src="${imageUrl}" alt="Текущее изображение" style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 4px;">
                                    <p style="margin-top: 10px; font-size: 12px; color: #666;">Текущее изображение</p>
                                `;
                                imagePreview.style.display = 'block';
                            } else {
                                imagePreview.innerHTML = '<p style="color: #666;">Нет изображения</p>';
                                imagePreview.style.display = 'block';
                            }
                            
                            // Очищаем поле выбора файла
                            imageInput.value = '';
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
        
        function saveEvent() {
            const form = document.getElementById('eventForm');
            const eventId = document.getElementById('eventId').value;

            console.log('saveEvent вызвана, eventId:', eventId);
            console.log('Форма:', form);

            // Валидация данных перед отправкой
            const validationResult = validateEventForm();
            console.log('Результат валидации:', validationResult);
            
            if (!validationResult.isValid) {
                console.log('Валидация не пройдена, ошибки:', validationResult.errors);
                alert('Ошибки в форме:\n' + validationResult.errors.join('\n'));
                return;
            }

            console.log('Валидация пройдена, отправляем данные...');

            // Определяем метод (POST для создания, PUT для обновления)
            const method = eventId ? 'PUT' : 'POST';

            let requestBody;
            let contentType;

            // Проверяем, есть ли файл для загрузки
            const imageInput = document.getElementById('eventImage');
            const hasImageFile = imageInput.files.length > 0;
            
            if (method === 'POST' || hasImageFile) {
                // Для создания или обновления с файлом используем FormData
                requestBody = new FormData(form);
                requestBody.set('is_active', document.getElementById('eventIsActive').checked);
                
                // Для PUT запроса добавляем event_id
                if (method === 'PUT') {
                    requestBody.set('event_id', eventId);
                }
                
                contentType = undefined; // FormData сам установит Content-Type
            } else {
                // Для обновления без файла используем JSON
                requestBody = JSON.stringify({
                    event_id: eventId,
                    title: document.getElementById('eventTitle').value,
                    date: document.getElementById('eventDate').value,
                    time: document.getElementById('eventTime').value,
                    conditions: document.getElementById('eventConditions').value,
                    description_link: document.getElementById('eventDescriptionLink').value,
                    comment: document.getElementById('eventComment').value,
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

            fetch('/admin/events/api.php', fetchOptions)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeEventModal();
                    location.reload(); // Перезагружаем страницу для обновления списка
                } else {
                    alert('Ошибка: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Ошибка сохранения события:', error);
                alert('Ошибка сохранения события: ' + error.message);
            });
        }

        function validateEventForm() {
            const errors = [];
            
            console.log('Начинаем валидацию формы...');
            
            // Очищаем предыдущие ошибки
            clearFormErrors();
            
            // Проверяем обязательные поля
            const title = document.getElementById('eventTitle').value.trim();
            const date = document.getElementById('eventDate').value.trim();
            const time = document.getElementById('eventTime').value.trim();
            const conditions = document.getElementById('eventConditions').value.trim();
            
            console.log('Поля формы:', { title, date, time, conditions });
            
            if (!title) {
                errors.push('• Название события обязательно для заполнения');
                showFieldError('eventTitle');
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
            
            if (!conditions) {
                errors.push('• Условия участия обязательны для заполнения');
                showFieldError('eventConditions');
            }
            
            // Проверяем ссылку (если заполнена)
            const descriptionLink = document.getElementById('eventDescriptionLink').value.trim();
            if (descriptionLink) {
                try {
                    new URL(descriptionLink);
                } catch (e) {
                    errors.push('• Неверный формат ссылки');
                    showFieldError('eventDescriptionLink');
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

        // Хранилище для отслеживания удаляемых событий
        const deletingEvents = new Set();
        
        function deleteEvent(eventId) {
            // Проверяем, не удаляется ли уже это событие
            if (deletingEvents.has(eventId)) {
                console.log('Событие уже удаляется:', eventId);
                return;
            }
            
            if (confirm('Вы уверены, что хотите удалить это событие?')) {
                // Добавляем событие в список удаляемых
                deletingEvents.add(eventId);
                
                // Отключаем кнопку удаления
                const deleteButton = document.querySelector(`button[onclick="deleteEvent('${eventId}')"]`);
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
                const linkHtml = event.description_link ? 
                    `<a href="${event.description_link}" target="_blank" class="link-btn">🔗 Открыть</a>` : 
                    '<span class="no-link">-</span>';
                
                // Формируем миниатюру - только из GridFS
                let imageSrc = '/images/event-default.png';
                if (event.image) {
                    // Проверяем, является ли это GridFS file_id
                    if (/^[a-f\d]{24}$/i.test(event.image)) {
                        imageSrc = "/api/image.php?id=" + event.image;
                    } else {
                        // Если это не GridFS ID, используем изображение по умолчанию
                        imageSrc = '/images/event-default.png';
                    }
                }
                const imageAlt = event.image ? event.title : 'Дефолтное изображение';
                const thumbnailClass = event.image ? 'thumbnail-img' : 'thumbnail-img default-thumbnail';
                const thumbnailHtml = `<img src="${imageSrc}" alt="${imageAlt}" class="${thumbnailClass}" onclick="showImageModal('${imageSrc}', '${imageAlt}')">`;
                
                // Формируем статус
                const statusClass = event.is_active ? 'active' : 'inactive';
                const statusText = event.is_active ? 'Активно' : 'Неактивно';
                const statusHtml = `<span class="status-badge ${statusClass}">${statusText}</span>`;
                
                // Обрезаем комментарий до 50 символов
                const comment = event.comment || '-';
                const truncatedComment = comment.length > 50 ? comment.substring(0, 50) + '...' : comment;
                
                row.innerHTML = `
                    <td class="event-date">${new Date(event.date + 'T00:00:00').toLocaleDateString('ru-RU')}</td>
                    <td class="event-time">${event.time}</td>
                    <td class="event-title">${event.title}</td>
                    <td class="event-conditions">${event.conditions}</td>
                    <td class="event-link">${linkHtml}</td>
                    <td class="event-thumbnail">${thumbnailHtml}</td>
                    <td class="event-status">${statusHtml}</td>
                    <td class="event-comment">${truncatedComment}</td>
                    <td class="event-actions">
                        <button class="btn btn-edit" onclick="editEvent('${event.id}')" title="Редактировать">✏️</button>
                        <button class="btn btn-delete" onclick="deleteEvent('${event.id}')" title="Удалить">🗑️</button>
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
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.innerHTML = `
                        <img src="${e.target.result}" alt="Новое изображение" style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 4px;">
                        <p style="margin-top: 10px; font-size: 12px; color: #666;">Новое изображение</p>
                    `;
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
    </script>
</body>
</html>
