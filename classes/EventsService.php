<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/ImageService.php';

class EventsService {
    private $client;
    private $db;
    private $eventsCollection;
    
    public function __construct() {
        try {
            $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
            $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
            
            $this->client = new MongoDB\Client($mongodbUrl);
            $this->db = $this->client->$dbName;
            $this->eventsCollection = $this->db->events;
        } catch (Exception $e) {
            error_log("Ошибка подключения к MongoDB для событий: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Получить календарь на 7 дней начиная с указанной даты
     */
    public function getCalendarDays($startDate = null, $daysCount = 7) {
        if (!$startDate) {
            $startDate = new DateTime();
        } else {
            $startDate = new DateTime($startDate);
        }
        
        $calendarDays = [];
        for ($i = 0; $i < $daysCount; $i++) {
            $currentDate = clone $startDate;
            $currentDate->add(new DateInterval('P' . $i . 'D'));
            
            $calendarDays[] = [
                'date' => $currentDate->format('Y-m-d'),
                'day' => $currentDate->format('d'),
                'month' => $this->getMonthShort($currentDate->format('m')),
                'full_date' => $currentDate->format('Y-m-d')
            ];
        }
        
        return $calendarDays;
    }
    
    /**
     * Получить события для виджета начиная с указанной даты (7 событий)
     */
    public function getEventsForWidget($startDate = null, $limit = 7) {
        try {
            if (!$startDate) {
                $startDate = new DateTime();
            } else {
                $startDate = new DateTime($startDate);
            }
            $startDate->setTime(0, 0, 0);
            
            // Получаем события начиная с указанной даты
            $events = $this->eventsCollection->find(
                [
                    'is_active' => true,
                    'date' => ['$gte' => $startDate->format('Y-m-d')]
                ],
                [
                    'sort' => ['date' => 1, 'time' => 1],
                    'limit' => $limit
                ]
            )->toArray();
            
            // Если событий мало, добавляем события следующих дней
            if (count($events) < $limit) {
                $remaining = $limit - count($events);
                $nextWeek = clone $startDate;
                $nextWeek->add(new DateInterval('P7D'));
                
                $additionalEvents = $this->eventsCollection->find(
                    [
                        'is_active' => true,
                        'date' => ['$gte' => $nextWeek->format('Y-m-d')]
                    ],
                    [
                        'sort' => ['date' => 1, 'time' => 1],
                        'limit' => $remaining
                    ]
                )->toArray();
                
                $events = array_merge($events, $additionalEvents);
            }
            
            // Конвертируем в нужный формат для виджета
            $formattedEvents = [];
            foreach ($events as $event) {
                $eventDate = new DateTime($event['date']);
                $day = $eventDate->format('j');
                $month = $this->getMonthShort($eventDate->format('n'));
                
                // Обработка условий участия
                $conditions = $event['conditions'] ?? '';
                if ($conditions && strpos($conditions, 'Условия участия:') !== 0) {
                    $conditions = 'Условия участия: ' . $conditions;
                }
                
                // Определяем URL изображения
                $imageUrl = '/images/event-default.png'; // По умолчанию
                if (!empty($event['image'])) {
                    // Если image содержит file_id (GridFS), создаем URL
                    if (preg_match('/^[a-f\d]{24}$/i', $event['image'])) {
                        $imageUrl = "/api/image.php?id=" . $event['image'];
                    } else {
                        // Если это старый путь к файлу
                        $imageUrl = $event['image'];
                    }
                }
                
                $formattedEvents[] = [
                    'id' => (string)$event['_id'],
                    'day' => $day,
                    'month' => $month,
                    'title' => $event['title'] ?? 'Событие',
                    'description' => $event['conditions'] ?? 'Описание события',
                    'price' => 0, // Всегда 0, так как у нас нет цены
                    'image' => $imageUrl,
                    'link' => $event['description_link'] ?? '#',
                    'date' => $event['date'],
                    'time' => $event['time'] ?? '19:00',
                    'conditions' => $conditions
                ];
            }
            
            return $formattedEvents;
            
        } catch (Exception $e) {
            error_log("Ошибка получения событий: " . $e->getMessage());
            return []; // Возвращаем пустой массив вместо фейковых данных
        }
    }
    
    /**
     * Получить сокращенное название месяца
     */
    private function getMonthShort($monthNumber) {
        $months = [
            1 => 'янв', 2 => 'фев', 3 => 'мар', 4 => 'апр',
            5 => 'май', 6 => 'июн', 7 => 'июл', 8 => 'авг',
            9 => 'сен', 10 => 'окт', 11 => 'ноя', 12 => 'дек'
        ];
        
        return $months[$monthNumber] ?? 'янв';
    }
    
}
?>
