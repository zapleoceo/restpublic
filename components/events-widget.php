<?php
// Виджет событий для отображения на сайте
require_once __DIR__ . '/../vendor/autoload.php';

// Загружаем переменные окружения
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

try {
    // Загружаем события из JSON файла
    $eventsFile = __DIR__ . '/../data/events.json';
    if (file_exists($eventsFile)) {
        $eventsData = json_decode(file_get_contents($eventsFile), true);
        
        // Фильтруем только активные события и сортируем по дате
        $events = array_filter($eventsData, function($event) {
            return $event['is_active'] === true;
        });
        
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

<!-- Events Widget -->
<div class="events-widget">
    <div class="events-header">
        <h2 class="events-title">Предстоящие события</h2>
    </div>
    
    <?php if (empty($events)): ?>
        <div class="events-empty">
            <p>Скоро здесь появятся интересные события!</p>
        </div>
    <?php else: ?>
        <div class="events-list">
            <?php foreach ($events as $event): ?>
                <div class="event-item">
                    <div class="event-image">
                        <?php if (!empty($event['image'])): ?>
                            <img src="<?php echo htmlspecialchars($event['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($event['title']); ?>">
                        <?php else: ?>
                            <div class="event-image-default">
                                <img src="images/logo.png" alt="Событие" class="blurred">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="event-content">
                        <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                        
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
                        </div>
                        
                        <?php if (!empty($event['description_link'])): ?>
                            <div class="event-link">
                                <a href="<?php echo htmlspecialchars($event['description_link']); ?>" 
                                   target="_blank" class="btn btn-outline">
                                    Подробнее
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* Events Widget Styles */
.events-widget {
    background: var(--color-bg, #1e1e1e);
    padding: var(--vspace-2) 0;
    margin: var(--vspace-2) 0;
}

.events-header {
    text-align: center;
    margin-bottom: var(--vspace-2);
}

.events-title {
    font-family: var(--type-headings);
    font-size: var(--text-2xl);
    color: var(--color-text-light, #fff);
    margin: 0;
}

.events-empty {
    text-align: center;
    padding: var(--vspace-2);
    color: var(--color-text-muted, #999);
}

.events-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--vspace-1_5);
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 var(--vspace-1);
}

.event-item {
    background: var(--color-bg-light, #2a2a2a);
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.event-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.event-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.event-image-default {
    width: 100%;
    height: 100%;
    background: var(--color-bg-primary, #d4af37);
    display: flex;
    align-items: center;
    justify-content: center;
}

.event-image-default img.blurred {
    width: 80px;
    height: 80px;
    filter: blur(2px);
    opacity: 0.7;
}

.event-content {
    padding: var(--vspace-1_25);
}

.event-title {
    font-family: var(--type-headings);
    font-size: var(--text-lg);
    color: var(--color-text-light, #fff);
    margin: 0 0 var(--vspace-0_75) 0;
    line-height: 1.3;
}

.event-details {
    margin-bottom: var(--vspace-1);
}

.event-details > div {
    display: flex;
    align-items: center;
    margin-bottom: var(--vspace-0_5);
    color: var(--color-text-muted, #ccc);
    font-size: var(--text-sm);
}

.event-details i {
    margin-right: var(--vspace-0_5);
    color: var(--color-bg-primary, #d4af37);
    width: 16px;
}

.event-conditions {
    font-weight: 500;
    color: var(--color-text-light, #fff) !important;
}

.event-link {
    text-align: center;
}

.btn-outline {
    display: inline-block;
    padding: var(--vspace-0_5) var(--vspace-1);
    background: transparent;
    border: 2px solid var(--color-bg-primary, #d4af37);
    color: var(--color-bg-primary, #d4af37);
    text-decoration: none;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-outline:hover {
    background: var(--color-bg-primary, #d4af37);
    color: var(--color-text-dark, #333);
}

/* Mobile responsive */
@media (max-width: 768px) {
    .events-list {
        grid-template-columns: 1fr;
        gap: var(--vspace-1);
        padding: 0 var(--vspace-0_75);
    }
    
    .event-image {
        height: 150px;
    }
    
    .event-content {
        padding: var(--vspace-1);
    }
    
    .event-title {
        font-size: var(--text-base);
    }
}
</style>
