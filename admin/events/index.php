<?php
// Страница управления событиями в админке
session_start();
require_once __DIR__ . '/../includes/auth-check.php';

$pageTitle = 'Управление событиями';
$pageDescription = 'Администрирование событий ресторана';

try {
    // Загружаем события из JSON файла
    $eventsFile = __DIR__ . '/../../data/events.json';
    if (file_exists($eventsFile)) {
        $events = json_decode(file_get_contents($eventsFile), true);
        
        // Сортируем по дате и времени
        usort($events, function($a, $b) {
            $dateA = strtotime($a['date'] . ' ' . $a['time']);
            $dateB = strtotime($b['date'] . ' ' . $b['time']);
            return $dateA - $dateB;
        });
    } else {
        $events = [];
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
                <?php if (empty($events)): ?>
                    <div class="empty-state">
                        <p>События не найдены</p>
                        <button class="btn btn-primary" onclick="openEventModal()">
                            Добавить первое событие
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="events-table">
                            <thead>
                                <tr>
                                    <th>Название</th>
                                    <th>Дата</th>
                                    <th>Время</th>
                                    <th>Условия</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                    <tr data-event-id="<?php echo $event['id']; ?>">
                                        <td class="event-title">
                                            <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                            <?php if (!empty($event['description_link'])): ?>
                                                <br><a href="<?php echo htmlspecialchars($event['description_link']); ?>" target="_blank" class="event-link">Подробнее</a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="event-date">
                                            <?php echo date('d.m.Y', strtotime($event['date'])); ?>
                                        </td>
                                        <td class="event-time">
                                            <?php echo htmlspecialchars($event['time']); ?>
                                        </td>
                                        <td class="event-conditions">
                                            <?php echo htmlspecialchars($event['conditions']); ?>
                                        </td>
                                        <td class="event-status">
                                            <span class="status-badge <?php echo $event['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $event['is_active'] ? 'Активно' : 'Неактивно'; ?>
                                            </span>
                                        </td>
                                        <td class="event-actions">
                                            <button class="btn btn-sm btn-edit" onclick="editEvent('<?php echo $event['id']; ?>')" title="Редактировать">
                                                ✏️ Редактировать
                                            </button>
                                            <button class="btn btn-sm btn-delete" onclick="deleteEvent('<?php echo $event['id']; ?>')" title="Удалить">
                                                🗑️ Удалить
                                            </button>
                                        </td>
                                    </tr>
                                    <?php if (!empty($event['comment'])): ?>
                                        <tr class="event-comment-row">
                                            <td colspan="6" class="event-comment">
                                                <strong>Комментарий:</strong> <?php echo htmlspecialchars($event['comment']); ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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
        /* Стили для таблицы событий */
        .table-container {
            overflow-x: auto;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .events-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 800px;
        }
        
        .events-table th {
            background: #f8f9fa;
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
        
        .event-title {
            font-weight: 500;
            min-width: 200px;
        }
        
        .event-title strong {
            color: #212529;
            font-size: 16px;
        }
        
        .event-link {
            color: #007bff;
            text-decoration: none;
            font-size: 12px;
        }
        
        .event-link:hover {
            text-decoration: underline;
        }
        
        .event-date, .event-time {
            white-space: nowrap;
            font-family: monospace;
            color: #6c757d;
        }
        
        .event-conditions {
            max-width: 250px;
            word-wrap: break-word;
            font-size: 14px;
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
        
        .event-actions {
            white-space: nowrap;
        }
        
        .event-actions .btn {
            margin: 2px;
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
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
        
        .event-comment-row {
            background-color: #f8f9fa;
        }
        
        .event-comment {
            font-size: 13px;
            color: #6c757d;
            font-style: italic;
            padding: 10px 12px;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .table-container {
                margin: 10px 0;
                border-radius: 4px;
            }
            
            .events-table th,
            .events-table td {
                padding: 10px 8px;
                font-size: 13px;
            }
            
            .event-title {
                min-width: 150px;
            }
            
            .event-conditions {
                max-width: 150px;
                font-size: 12px;
            }
            
            .event-actions .btn {
                padding: 4px 8px;
                font-size: 11px;
                margin: 1px;
            }
        }
        
        @media (max-width: 480px) {
            .events-table {
                min-width: 600px;
            }
            
            .events-table th,
            .events-table td {
                padding: 8px 6px;
                font-size: 12px;
            }
            
            .event-actions .btn {
                padding: 3px 6px;
                font-size: 10px;
            }
        }
        
        /* Кнопка добавления */
        .btn-primary {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s ease;
        }
        
        .btn-primary:hover {
            background-color: #0056b3;
        }
        
        /* Пустое состояние */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state p {
            margin-bottom: 20px;
            font-size: 16px;
        }
    </style>
    <script>
        // Функции для работы с событиями
        function openEventModal(eventId = null) {
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
            }
            
            modal.style.display = 'block';
        }
        
        function closeEventModal() {
            document.getElementById('eventModal').style.display = 'none';
        }
        
        function loadEventData(eventId) {
            // Здесь будет загрузка данных события для редактирования
            console.log('Loading event data for:', eventId);
        }
        
        function saveEvent() {
            const form = document.getElementById('eventForm');
            const formData = new FormData(form);
            
            // Здесь будет отправка данных на сервер
            console.log('Saving event:', Object.fromEntries(formData));
        }
        
        function editEvent(eventId) {
            openEventModal(eventId);
        }
        
        function deleteEvent(eventId) {
            if (confirm('Вы уверены, что хотите удалить это событие?')) {
                // Здесь будет удаление события
                console.log('Deleting event:', eventId);
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
