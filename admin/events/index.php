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

        .event-comment {
            color: #6c757d;
            font-size: 12px;
            font-style: italic;
            max-width: 200px;
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
            margin: 5% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
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
            padding: 20px 30px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
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
                <button class="btn btn-primary" onclick="openEventModal()">
                    ➕ Добавить событие
                </button>
            </div>

            <div class="admin-content">
                <div class="events-container">
                    <div class="events-header">
                        <h2>События (текущая и будущие недели)</h2>
                        <button class="load-past-btn" onclick="loadPastEvents()">
                            📅 Показать прошлые
                        </button>
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
                                            <?php echo date('d.m.Y', strtotime($event['date'])); ?>
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

    <script src="/admin/assets/js/admin.js"></script>
    <script>
        // Переменная для отслеживания загруженных прошлых недель
        let pastWeeksLoaded = 0;
        const allEvents = <?php echo json_encode($events); ?>;

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
            // Находим событие в таблице
            const eventRow = document.querySelector(`tr[data-event-id="${eventId}"]`);
            if (eventRow) {
                const eventDate = eventRow.querySelector('.event-date').textContent;
                const eventTime = eventRow.querySelector('.event-time').textContent;
                const eventTitle = eventRow.querySelector('.event-title').textContent;
                const eventConditions = eventRow.querySelector('.event-conditions').textContent;
                const eventComment = eventRow.querySelector('.event-comment').textContent;
                
                // Заполняем форму данными события
                document.getElementById('eventId').value = eventId;
                document.getElementById('eventTitle').value = eventTitle;
                
                // Конвертируем дату из формата dd.mm.yyyy в yyyy-mm-dd
                const dateParts = eventDate.split('.');
                const formattedDate = `${dateParts[2]}-${dateParts[1].padStart(2, '0')}-${dateParts[0].padStart(2, '0')}`;
                document.getElementById('eventDate').value = formattedDate;
                
                document.getElementById('eventTime').value = eventTime;
                document.getElementById('eventConditions').value = eventConditions;
                document.getElementById('eventComment').value = eventComment === '-' ? '' : eventComment;
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

        function loadPastEvents() {
            pastWeeksLoaded++;
            
            // Вычисляем дату начала для загрузки прошлых недель
            const today = new Date();
            const weekStart = new Date(today);
            weekStart.setDate(today.getDate() - (today.getDay() + 6) % 7); // Понедельник текущей недели
            
            // Вычитаем загруженные недели
            weekStart.setDate(weekStart.getDate() - (pastWeeksLoaded * 7));
            
            // Фильтруем события для показа
            const pastEvents = allEvents.filter(event => {
                const eventDate = new Date(event.date);
                return eventDate < weekStart;
            }).slice(0, 7); // Показываем максимум 7 событий за раз
            
            if (pastEvents.length === 0) {
                alert('Больше прошлых событий нет');
                return;
            }
            
            // Добавляем события в таблицу
            const tbody = document.getElementById('eventsTableBody');
            pastEvents.forEach(event => {
                const row = document.createElement('tr');
                row.setAttribute('data-event-id', event.id);
                row.innerHTML = `
                    <td class="event-date">${new Date(event.date).toLocaleDateString('ru-RU')}</td>
                    <td class="event-time">${event.time}</td>
                    <td class="event-title">${event.title}</td>
                    <td class="event-conditions">${event.conditions}</td>
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
