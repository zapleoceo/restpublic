<?php
// Страница управления событиями в админке
session_start();
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../../vendor/autoload.php';

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
    foreach ($events as &$event) {
        $event['_id'] = (string)$event['_id'];
        $event['id'] = (string)$event['_id'];
    }
    
} catch (Exception $e) {
    error_log("Ошибка загрузки событий: " . $e->getMessage());
    $events = [];
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
                        <h2>События (текущая и будущие недели)</h2>
                        <div class="header-buttons">
                            <button class="btn btn-primary" onclick="openEventModal()">
                                ➕ Добавить событие
                            </button>
                            <button class="load-past-btn" onclick="loadPastEvents()">
                                📅 Показать прошлые
                            </button>
                        </div>
                    </div>

                    <?php if (empty($events)): ?>
                        <div class="empty-state">
                            <p>События не найдены</p>
                            <button class="btn btn-primary" onclick="openEventModal()">
                                Добавить первое событие
                            </button>
                        </div>
                    <?php else: ?>
                        <table class="events-table">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Время</th>
                                    <th>Название</th>
                                    <th>Условия</th>
                                    <th>Ссылка</th>
                                    <th>Миниатюра</th>
                                    <th>Статус</th>
                                    <th>Комментарий</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody id="eventsTableBody">
                                <?php
                                // Фильтруем события - показываем только текущую и будущие недели
                                $today = new DateTime();
                                $weekStart = clone $today;
                                $weekStart->modify('monday this week');
                                
                                $filteredEvents = array_filter($events, function($event) use ($weekStart) {
                                    $eventDate = new DateTime($event['date']);
                                    return $eventDate >= $weekStart;
                                });
                                
                                foreach ($filteredEvents as $event): 
                                ?>
                                    <tr data-event-id="<?php echo $event['id']; ?>">
                                        <td class="event-date">
                                            <?php 
                                            $date = new DateTime($event['date']);
                                            echo $date->format('d.m.Y'); 
                                            ?>
                                        </td>
                                        <td class="event-time">
                                            <?php echo htmlspecialchars($event['time']); ?>
                                        </td>
                                        <td class="event-title">
                                            <?php echo htmlspecialchars($event['title']); ?>
                                        </td>
                                        <td class="event-conditions">
                                            <?php echo htmlspecialchars($event['conditions']); ?>
                                        </td>
                                        <td class="event-link">
                                            <?php if (!empty($event['description_link'])): ?>
                                                <a href="<?php echo htmlspecialchars($event['description_link']); ?>" target="_blank" class="link-btn">
                                                    🔗 Открыть
                                                </a>
                                            <?php else: ?>
                                                <span class="no-link">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="event-thumbnail">
                                            <?php if (!empty($event['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($event['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($event['title']); ?>" 
                                                     class="thumbnail-img" 
                                                     onclick="showImageModal('<?php echo htmlspecialchars($event['image']); ?>', '<?php echo htmlspecialchars($event['title']); ?>')">
                                            <?php else: ?>
                                                <img src="/images/event-default.png" 
                                                     alt="Дефолтное изображение" 
                                                     class="thumbnail-img default-thumbnail"
                                                     onclick="showImageModal('/images/event-default.png', 'Дефолтное изображение')">
                                            <?php endif; ?>
                                        </td>
                                        <td class="event-status">
                                            <span class="status-badge <?php echo $event['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $event['is_active'] ? 'Активно' : 'Неактивно'; ?>
                                            </span>
                                        </td>
                                        <td class="event-comment">
                                            <?php echo !empty($event['comment']) ? htmlspecialchars($event['comment']) : '-'; ?>
                                        </td>
                                        <td class="event-actions">
                                            <button class="btn btn-edit" onclick="editEvent('<?php echo $event['id']; ?>')" title="Редактировать">
                                                ✏️
                                            </button>
                                            <button class="btn btn-delete" onclick="deleteEvent('<?php echo $event['id']; ?>')" title="Удалить">
                                                🗑️
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
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
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="eventDate">Дата *</label>
                        <input type="date" id="eventDate" name="date" required>
                    </div>
                    <div class="form-group">
                        <label for="eventTime">Время *</label>
                        <input type="time" id="eventTime" name="time" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="eventConditions">Условия участия *</label>
                    <input type="text" id="eventConditions" name="conditions" required>
                </div>

                <div class="form-group">
                    <label for="eventDescriptionLink">Ссылка на описание</label>
                    <input type="url" id="eventDescriptionLink" name="description_link">
                </div>

                <div class="form-group">
                    <label for="eventImage">Картинка</label>
                    <input type="file" id="eventImage" name="image" accept="image/*">
                    <small>Если не выбрана, будет использована дефолтная картинка</small>
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

    <script src="/admin/assets/js/admin.js"></script>
    <script>
        // Переменная для отслеживания загруженных прошлых недель
        let pastWeeksLoaded = 0;
        const allEvents = <?php echo json_encode($events); ?>;
        const loadedEventIds = new Set(); // Отслеживаем уже загруженные события
        
        // Отладочная информация
        console.log('Всего событий загружено:', allEvents.length);
        if (allEvents.length > 0) {
            console.log('Первое событие:', allEvents[0]);
        }
        
        // Инициализируем Set с уже загруженными событиями (текущая и будущие недели)
        const today = new Date();
        const weekStart = new Date(today);
        weekStart.setDate(today.getDate() - (today.getDay() + 6) % 7); // Понедельник текущей недели
        
        allEvents.forEach(event => {
            const eventDate = new Date(event.date);
            if (eventDate >= weekStart) {
                loadedEventIds.add(event.id);
            }
        });
        
        console.log('Уже загружено событий:', loadedEventIds.size);

        // Функции для работы с событиями
        function openEventModal(eventId = null) {
            const modal = document.getElementById('eventModal');
            const form = document.getElementById('eventForm');
            const title = document.getElementById('modalTitle');

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

            // Собираем данные формы
            const formData = new FormData(form);
            
            // Правильно обрабатываем checkbox is_active
            formData.set('is_active', document.getElementById('eventIsActive').checked);

            // Определяем метод (POST для создания, PUT для обновления)
            const method = eventId ? 'PUT' : 'POST';

            // Добавляем event_id для PUT запроса
            if (method === 'PUT') {
                formData.set('event_id', eventId);
            }

            fetch('/admin/events/api.php', {
                method: method,
                body: formData // Отправляем FormData для поддержки файлов
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
            pastWeeksLoaded++;
            
            // Вычисляем дату начала для загрузки прошлых недель
            const today = new Date();
            const weekStart = new Date(today);
            weekStart.setDate(today.getDate() - (today.getDay() + 6) % 7); // Понедельник текущей недели
            
            // Вычитаем загруженные недели
            weekStart.setDate(weekStart.getDate() - (pastWeeksLoaded * 7));
            
            // Фильтруем события для показа - только те, которые еще не загружены
            const pastEvents = allEvents.filter(event => {
                const eventDate = new Date(event.date);
                return eventDate < weekStart && !loadedEventIds.has(event.id);
            }).slice(0, 7); // Показываем максимум 7 событий за раз
            
            if (pastEvents.length === 0) {
                alert('Больше прошлых событий нет');
                return;
            }
            
            console.log(`Загружаем ${pastEvents.length} прошлых событий`);
            
            // Добавляем события в таблицу
            const tbody = document.getElementById('eventsTableBody');
            pastEvents.forEach(event => {
                // Добавляем событие в список загруженных
                loadedEventIds.add(event.id);
                
                const row = document.createElement('tr');
                row.setAttribute('data-event-id', event.id);
                
                // Формируем ссылку
                const linkHtml = event.description_link ? 
                    `<a href="${event.description_link}" target="_blank" class="link-btn">🔗 Открыть</a>` : 
                    '<span class="no-link">-</span>';
                
                // Формируем миниатюру
                const imageSrc = event.image || '/images/event-default.png';
                const imageAlt = event.image ? event.title : 'Дефолтное изображение';
                const thumbnailClass = event.image ? 'thumbnail-img' : 'thumbnail-img default-thumbnail';
                const thumbnailHtml = `<img src="${imageSrc}" alt="${imageAlt}" class="${thumbnailClass}" onclick="showImageModal('${imageSrc}', '${imageAlt}')">`;
                
                // Формируем статус
                const statusClass = event.is_active ? 'active' : 'inactive';
                const statusText = event.is_active ? 'Активно' : 'Неактивно';
                const statusHtml = `<span class="status-badge ${statusClass}">${statusText}</span>`;
                
                row.innerHTML = `
                    <td class="event-date">${new Date(event.date + 'T00:00:00').toLocaleDateString('ru-RU')}</td>
                    <td class="event-time">${event.time}</td>
                    <td class="event-title">${event.title}</td>
                    <td class="event-conditions">${event.conditions}</td>
                    <td class="event-link">${linkHtml}</td>
                    <td class="event-thumbnail">${thumbnailHtml}</td>
                    <td class="event-status">${statusHtml}</td>
                    <td class="event-comment">${event.comment || '-'}</td>
                    <td class="event-actions">
                        <button class="btn btn-edit" onclick="editEvent('${event.id}')" title="Редактировать">✏️</button>
                        <button class="btn btn-delete" onclick="deleteEvent('${event.id}')" title="Удалить">🗑️</button>
                    </td>
                `;
                tbody.insertBefore(row, tbody.firstChild);
            });
            
            // Обновляем текст кнопки
            const loadBtn = document.querySelector('.load-past-btn');
            loadBtn.textContent = `📅 Показать еще прошлые (${pastWeeksLoaded} недель назад)`;
            
            console.log(`Всего загружено событий: ${loadedEventIds.size}`);
        }

        // Закрытие модального окна при клике вне его
        window.onclick = function(event) {
            const modal = document.getElementById('eventModal');
            if (event.target === modal) {
                closeEventModal();
            }
        }

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
