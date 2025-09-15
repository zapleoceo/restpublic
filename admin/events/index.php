<?php
// Страница управления событиями в админке
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
                    <div class="events-grid">
                        <?php foreach ($events as $event): ?>
                            <div class="event-card" data-event-id="<?php echo $event['id']; ?>">
                                <div class="event-header">
                                    <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                    <div class="event-actions">
                                        <button class="btn btn-sm btn-edit" onclick="editEvent('<?php echo $event['id']; ?>')">
                                            <i class="icon-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-delete" onclick="deleteEvent('<?php echo $event['id']; ?>')">
                                            <i class="icon-delete"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="event-details">
                                    <div class="event-date">
                                        <i class="icon-calendar"></i>
                                        <?php echo date('d.m.Y', strtotime($event['date'])); ?>
                                    </div>
                                    <div class="event-time">
                                        <i class="icon-clock"></i>
                                        <?php echo htmlspecialchars($event['time']); ?>
                                    </div>
                                    <div class="event-conditions">
                                        <i class="icon-info"></i>
                                        <?php echo htmlspecialchars($event['conditions']); ?>
                                    </div>
                                    <?php if (!empty($event['description_link'])): ?>
                                        <div class="event-link">
                                            <i class="icon-link"></i>
                                            <a href="<?php echo htmlspecialchars($event['description_link']); ?>" target="_blank">
                                                Подробнее
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="event-status">
                                    <span class="status-badge <?php echo $event['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $event['is_active'] ? 'Активно' : 'Неактивно'; ?>
                                    </span>
                                </div>
                                
                                <?php if (!empty($event['comment'])): ?>
                                    <div class="event-comment">
                                        <strong>Комментарий:</strong>
                                        <?php echo htmlspecialchars($event['comment']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
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
