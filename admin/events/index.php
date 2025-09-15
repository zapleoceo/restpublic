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
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Админка</title>
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
    <style>
        /* Стили для календарного вида событий */
        .calendar-view {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            margin: 20px 0;
            padding: 0 10px;
        }

        .calendar-day {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            position: relative;
        }

        .calendar-day:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transform: translateY(-3px);
        }

        .calendar-day.today {
            border-color: #007bff;
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
            box-shadow: 0 4px 20px rgba(0, 123, 255, 0.2);
        }

        .calendar-day.today::before {
            content: "СЕГОДНЯ";
            position: absolute;
            top: -10px;
            right: 15px;
            background: #007bff;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .calendar-day.past {
            opacity: 0.6;
            background: #f8f9fa;
        }

        .calendar-day.past::after {
            content: "ПРОШЛО";
            position: absolute;
            top: -10px;
            left: 15px;
            background: #6c757d;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .day-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }

        .day-date {
            font-size: 20px;
            font-weight: 700;
            color: #212529;
        }

        .day-weekday {
            font-size: 14px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .no-events {
            text-align: center;
            padding: 30px 20px;
            color: #6c757d;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #dee2e6;
        }

        .no-events p {
            margin: 0 0 20px 0;
            font-size: 18px;
            font-weight: 600;
            color: #495057;
        }

        .day-events {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .event-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .event-item:hover {
            background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .event-time {
            font-size: 13px;
            color: #007bff;
            font-weight: 700;
            margin-bottom: 8px;
            background: rgba(0, 123, 255, 0.1);
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .event-title {
            font-size: 16px;
            font-weight: 700;
            color: #212529;
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .event-conditions {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .event-comment {
            font-size: 12px;
            color: #6c757d;
            font-style: italic;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #dee2e6;
            background: rgba(108, 117, 125, 0.05);
            padding: 8px;
            border-radius: 4px;
        }

        .event-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .btn {
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
        }

        .btn-xs {
            padding: 6px 12px;
            font-size: 11px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4);
        }

        .btn-create, .btn-add-more {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
            width: 100%;
            margin-top: 15px;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }

        .btn-create:hover, .btn-add-more:hover {
            background: linear-gradient(135deg, #1e7e34 0%, #155724 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
        }

        .btn-edit {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
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
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
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
            transition: background-color 0.2s ease;
        }

        .modal-close:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
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
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s ease;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
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
            padding: 20px 30px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.2s ease;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .calendar-view {
                grid-template-columns: 1fr;
                gap: 15px;
                margin: 15px 0;
                padding: 0 5px;
            }

            .calendar-day {
                padding: 15px;
            }

            .day-date {
                font-size: 18px;
            }

            .day-weekday {
                font-size: 12px;
            }

            .event-item {
                padding: 12px;
            }

            .event-title {
                font-size: 15px;
            }

            .event-conditions {
                font-size: 12px;
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
            .calendar-view {
                gap: 10px;
                margin: 10px 0;
            }

            .calendar-day {
                padding: 12px;
            }

            .day-header {
                margin-bottom: 15px;
                padding-bottom: 10px;
            }

            .day-date {
                font-size: 16px;
            }

            .event-item {
                padding: 10px;
            }

            .btn {
                font-size: 10px;
                padding: 4px 8px;
            }

            .btn-sm {
                padding: 6px 12px;
                font-size: 11px;
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
                <button class="btn btn-primary" onclick="openEventModal()">
                    ➕ Добавить событие
                </button>
            </div>

            <div class="admin-content">
                <div class="calendar-view">
                    <?php
                    // Создаем массив событий по датам для удобного поиска
                    $eventsByDate = [];
                    foreach ($events as $event) {
                        $date = $event['date'];
                        if (!isset($eventsByDate[$date])) {
                            $eventsByDate[$date] = [];
                        }
                        $eventsByDate[$date][] = $event;
                    }
                    
                    // Получаем диапазон дат (текущий месяц + следующий)
                    $currentDate = new DateTime();
                    $startDate = clone $currentDate;
                    $startDate->modify('first day of this month');
                    $endDate = clone $currentDate;
                    $endDate->modify('last day of next month');
                    
                    // Генерируем календарь
                    $date = clone $startDate;
                    while ($date <= $endDate) {
                        $dateStr = $date->format('Y-m-d');
                        $dayEvents = $eventsByDate[$dateStr] ?? [];
                        $isToday = $date->format('Y-m-d') === date('Y-m-d');
                        $isPast = $date < new DateTime('today');
                        
                        echo '<div class="calendar-day ' . ($isToday ? 'today' : '') . ($isPast ? ' past' : '') . '">';
                        echo '<div class="day-header">';
                        echo '<span class="day-date">' . $date->format('d.m.Y') . '</span>';
                        echo '<span class="day-weekday">' . $date->format('D') . '</span>';
                        echo '</div>';
                        
                        if (empty($dayEvents)) {
                            echo '<div class="no-events">';
                            echo '<p>НЕТ ИВЕНТОВ</p>';
                            echo '<button class="btn btn-sm btn-create" onclick="createEventForDate(\'' . $dateStr . '\')" title="Создать событие">';
                            echo '➕ Создать событие';
                            echo '</button>';
                            echo '</div>';
                        } else {
                            echo '<div class="day-events">';
                            foreach ($dayEvents as $event) {
                                echo '<div class="event-item" data-event-id="' . $event['id'] . '">';
                                echo '<div class="event-time">' . htmlspecialchars($event['time']) . '</div>';
                                echo '<div class="event-title">' . htmlspecialchars($event['title']) . '</div>';
                                echo '<div class="event-conditions">' . htmlspecialchars($event['conditions']) . '</div>';
                                echo '<div class="event-actions">';
                                echo '<button class="btn btn-xs btn-edit" onclick="editEvent(\'' . $event['id'] . '\')" title="Редактировать">✏️ Редактировать</button>';
                                echo '<button class="btn btn-xs btn-delete" onclick="deleteEvent(\'' . $event['id'] . '\')" title="Удалить">🗑️ Удалить</button>';
                                echo '</div>';
                                if (!empty($event['comment'])) {
                                    echo '<div class="event-comment">' . htmlspecialchars($event['comment']) . '</div>';
                                }
                                echo '</div>';
                            }
                            echo '<button class="btn btn-sm btn-add-more" onclick="createEventForDate(\'' . $dateStr . '\')" title="Добавить еще событие">';
                            echo '➕ Добавить событие';
                            echo '</button>';
                            echo '</div>';
                        }
                        
                        echo '</div>';
                        $date->modify('+1 day');
                    }
                    ?>
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

    <script src="/admin/assets/js/admin.js"></script>
    <script>
        // Функции для работы с событиями
        function openEventModal(eventId = null, presetDate = null) {
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
                
                // Устанавливаем предустановленную дату, если передана
                if (presetDate) {
                    document.getElementById('eventDate').value = presetDate;
                }
            }

            modal.style.display = 'block';
        }
        
        function createEventForDate(dateStr) {
            openEventModal(null, dateStr);
        }
        
        function closeEventModal() {
            document.getElementById('eventModal').style.display = 'none';
        }
        
        function loadEventData(eventId) {
            // Находим событие в календарном виде
            const eventItem = document.querySelector(`.event-item[data-event-id="${eventId}"]`);
            if (eventItem) {
                const eventTitle = eventItem.querySelector('.event-title').textContent;
                const eventTime = eventItem.querySelector('.event-time').textContent;
                const eventConditions = eventItem.querySelector('.event-conditions').textContent;
                
                // Получаем дату из родительского элемента календаря
                const calendarDay = eventItem.closest('.calendar-day');
                const dayDate = calendarDay.querySelector('.day-date').textContent;
                
                // Заполняем форму данными события
                document.getElementById('eventId').value = eventId;
                document.getElementById('eventTitle').value = eventTitle;
                
                // Конвертируем дату из формата dd.mm.yyyy в yyyy-mm-dd
                const dateParts = dayDate.split('.');
                const formattedDate = `${dateParts[2]}-${dateParts[1].padStart(2, '0')}-${dateParts[0].padStart(2, '0')}`;
                document.getElementById('eventDate').value = formattedDate;
                
                document.getElementById('eventTime').value = eventTime;
                document.getElementById('eventConditions').value = eventConditions;
                
                // Получаем комментарий, если есть
                const eventComment = eventItem.querySelector('.event-comment');
                document.getElementById('eventComment').value = eventComment ? eventComment.textContent : '';
                
                // По умолчанию событие активно
                document.getElementById('eventIsActive').checked = true;
                document.getElementById('eventDescriptionLink').value = '';
            }
        }
        
        function saveEvent() {
            const form = document.getElementById('eventForm');
            const formData = new FormData(form);
            const eventId = document.getElementById('eventId').value;

            // Определяем метод (POST для создания, PUT для обновления)
            const method = eventId ? 'PUT' : 'POST';

            // Добавляем event_id для PUT запроса
            if (method === 'PUT') {
                formData.append('event_id', eventId);
            }

            fetch('/admin/events/api.php', {
                method: method,
                body: formData
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

        let isDeleting = false; // Флаг для предотвращения двойного удаления
        
        function deleteEvent(eventId) {
            if (isDeleting) return; // Предотвращаем повторные вызовы
            
            if (confirm('Вы уверены, что хотите удалить это событие?')) {
                isDeleting = true; // Устанавливаем флаг
                
                const formData = new FormData();
                formData.append('event_id', eventId);

                fetch('/admin/events/api.php', {
                    method: 'DELETE',
                    body: formData
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
                    }
                })
                .catch(error => {
                    console.error('Ошибка удаления события:', error);
                    alert('Ошибка удаления события: ' + error.message);
                })
                .finally(() => {
                    isDeleting = false; // Сбрасываем флаг
                });
            }
        }

        // Закрытие модального окна при клике вне его
        window.onclick = function(event) {
            const modal = document.getElementById('eventModal');
            if (event.target === modal) {
                closeEventModal();
            }
        }

        // Закрытие модального окна по Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEventModal();
            }
        });
    </script>
</body>
</html>
