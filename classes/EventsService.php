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
     * Получить события для виджета начиная с указанной даты на N дней вперед
     */
    public function getEventsForWidget($startDate = null, $days = 14, $language = 'ru') {
        try {
            if (!$startDate) {
                $startDate = new DateTime();
            } else {
                $startDate = new DateTime($startDate);
            }
            $startDate->setTime(0, 0, 0);
            
            // Вычисляем конечную дату
            $endDate = clone $startDate;
            $endDate->add(new DateInterval('P' . $days . 'D'));
            
            // Получаем события за указанный период
            $events = $this->eventsCollection->find(
                [
                    'is_active' => true,
                    'date' => [
                        '$gte' => $startDate->format('Y-m-d'),
                        '$lte' => $endDate->format('Y-m-d')
                    ]
                ],
                [
                    'sort' => ['date' => 1, 'time' => 1]
                ]
            )->toArray();
            
            // Конвертируем в нужный формат для виджета
            $formattedEvents = [];
            foreach ($events as $event) {
                // Получаем переводы для указанного языка
                $title = $this->getLocalizedField($event, 'title', $language);
                $description = $this->getLocalizedField($event, 'description', $language);
                $conditions = $this->getLocalizedField($event, 'conditions', $language);
                
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
                    'title' => $title,
                    'description' => $description,
                    'conditions' => $conditions,
                    'date' => $event['date'],
                    'time' => $event['time'] ?? '19:00',
                    'image' => $imageUrl,
                    'link' => $event['link'] ?? '#',
                    'category' => $event['category'] ?? 'general'
                ];
            }
            
            return $formattedEvents;
            
        } catch (Exception $e) {
            error_log("Ошибка получения событий: " . $e->getMessage());
            return []; // Возвращаем пустой массив вместо фейковых данных
        }
    }
    
    /**
     * Получить локализованное поле события
     */
    private function getLocalizedField($event, $field, $language) {
        // Пробуем получить поле для указанного языка
        $localizedField = $field . '_' . $language;
        if (isset($event[$localizedField]) && !empty($event[$localizedField])) {
            return $event[$localizedField];
        }
        
        // Fallback на русский язык
        if ($language !== 'ru' && isset($event[$field . '_ru']) && !empty($event[$field . '_ru'])) {
            return $event[$field . '_ru'];
        }
        
        // Fallback на старое поле (для совместимости)
        if (isset($event[$field]) && !empty($event[$field])) {
            return $event[$field];
        }
        
        // Возвращаем значение по умолчанию
        switch ($field) {
            case 'title':
                return 'Событие';
            case 'description':
                return 'Описание события';
            case 'conditions':
                return 'Условия участия';
            default:
                return '';
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
