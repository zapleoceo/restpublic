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
        $event['id'] = (string)$event['_id']; // Добавляем поле id для совместимости
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
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
                <button class="btn btn-primary" onclick="openEventModal()">
                    <i class="icon-plus"></i> Добавить событие
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
                                echo '➕ Создать';
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
                                    echo '<button class="btn btn-xs btn-edit" onclick="editEvent(\'' . $event['id'] . '\')" title="Редактировать">✏️</button>';
                                    echo '<button class="btn btn-xs btn-delete" onclick="deleteEvent(\'' . $event['id'] . '\')" title="Удалить">🗑️</button>';
                                    echo '</div>';
                                    if (!empty($event['comment'])) {
                                        echo '<div class="event-comment">' . htmlspecialchars($event['comment']) . '</div>';
                                    }
                                    echo '</div>';
                                }
                                echo '<button class="btn btn-sm btn-add-more" onclick="createEventForDate(\'' . $dateStr . '\')" title="Добавить еще событие">';
                                echo '➕ Добавить';
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
        <style>
            /* Стили для календарного вида */
            .calendar-view {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }

            .calendar-day {
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                padding: 15px;
                border: 2px solid transparent;
                transition: all 0.3s ease;
            }

            .calendar-day:hover {
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                transform: translateY(-2px);
            }

            .calendar-day.today {
                border-color: #007bff;
                background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
            }

            .calendar-day.past {
                opacity: 0.7;
                background: #f8f9fa;
            }

            .day-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 2px solid #e9ecef;
            }

            .day-date {
                font-size: 18px;
                font-weight: 600;
                color: #212529;
            }

            .day-weekday {
                font-size: 14px;
                color: #6c757d;
                text-transform: uppercase;
                font-weight: 500;
            }

            .no-events {
                text-align: center;
                padding: 20px 10px;
                color: #6c757d;
            }

            .no-events p {
                margin: 0 0 15px 0;
                font-size: 16px;
                font-weight: 500;
            }

            .day-events {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .event-item {
                background: #f8f9fa;
                border-radius: 6px;
                padding: 12px;
                border-left: 4px solid #007bff;
                transition: all 0.2s ease;
            }

            .event-item:hover {
                background: #e9ecef;
                transform: translateX(2px);
            }

            .event-time {
                font-size: 12px;
                color: #007bff;
                font-weight: 600;
                margin-bottom: 5px;
            }

            .event-title {
                font-size: 14px;
                font-weight: 600;
                color: #212529;
                margin-bottom: 5px;
            }

            .event-conditions {
                font-size: 12px;
                color: #6c757d;
                margin-bottom: 8px;
            }

            .event-comment {
                font-size: 11px;
                color: #6c757d;
                font-style: italic;
                margin-top: 5px;
                padding-top: 5px;
                border-top: 1px solid #dee2e6;
            }

            .event-actions {
                display: flex;
                gap: 5px;
                margin-top: 8px;
            }

            .btn {
                border: none;
                border-radius: 4px;
                cursor: pointer;
                transition: all 0.2s ease;
                font-size: 12px;
                padding: 4px 8px;
            }

            .btn-sm {
                padding: 6px 12px;
                font-size: 12px;
            }

            .btn-xs {
                padding: 3px 6px;
                font-size: 10px;
            }

            .btn-primary {
                background-color: #007bff;
                color: white;
            }

            .btn-primary:hover {
                background-color: #0056b3;
            }

            .btn-create, .btn-add-more {
                background-color: #28a745;
                color: white;
                width: 100%;
                margin-top: 10px;
            }

            .btn-create:hover, .btn-add-more:hover {
                background-color: #218838;
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

            /* Адаптивность */
            @media (max-width: 768px) {
                .calendar-view {
                    grid-template-columns: 1fr;
                    gap: 15px;
                    margin: 15px 0;
                }

                .calendar-day {
                    padding: 12px;
                }

                .day-date {
                    font-size: 16px;
                }

                .day-weekday {
                    font-size: 12px;
                }

                .event-item {
                    padding: 10px;
                }

                .event-title {
                    font-size: 13px;
                }

                .event-conditions {
                    font-size: 11px;
                }
            }

            @media (max-width: 480px) {
                .calendar-view {
                    gap: 10px;
                    margin: 10px 0;
                }

                .calendar-day {
                    padding: 10px;
                }

                .day-header {
                    margin-bottom: 10px;
                    padding-bottom: 8px;
                }

                .day-date {
                    font-size: 14px;
                }

                .event-item {
                    padding: 8px;
                }

                .btn {
                    font-size: 10px;
                    padding: 2px 4px;
                }

                .btn-sm {
                    padding: 4px 8px;
                    font-size: 10px;
                }
            }
        </style>
    <script>
        // Функции для работы с событиями
        function openEventModal(eventId = null, presetDate = null) {
            const modal = document.getElementById('eventModal');
            const form = document.getElementById('eventForm');
            const title = document.getElementById('modalTitle');

            if (eventId) {
                title.textContent = 'Редактировать событие';
                // Загружаем данные события для редактирования
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
                
                // По умолчанию событие активно (в календарном виде не показываем статус)
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
            .then(response => response.json())
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
                alert('Ошибка сохранения события');
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
    </script>
</body>
</html>
