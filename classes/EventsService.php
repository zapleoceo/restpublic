<?php
require_once __DIR__ . '/../vendor/autoload.php';

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
     * Получить события для виджета (активные события начиная с сегодня)
     */
    public function getEventsForWidget($limit = 8) {
        try {
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            
            // Получаем активные события начиная с сегодня
            $events = $this->eventsCollection->find(
                [
                    'is_active' => true,
                    'date' => ['$gte' => $today->format('Y-m-d')]
                ],
                [
                    'sort' => ['date' => 1, 'time' => 1],
                    'limit' => $limit
                ]
            )->toArray();
            
            // Конвертируем в нужный формат для виджета
            $formattedEvents = [];
            foreach ($events as $event) {
                $eventDate = new DateTime($event['date']);
                $day = $eventDate->format('j');
                $month = $this->getMonthShort($eventDate->format('n'));
                
                $formattedEvents[] = [
                    'day' => $day,
                    'month' => $month,
                    'title' => $event['title'] ?? 'Событие',
                    'description' => $event['conditions'] ?? 'Описание события',
                    'price' => $event['conditions'] ?? 'Уточняйте',
                    'image' => $event['image'] ?? 'template/images/gallery/gallery-01.jpg',
                    'link' => $event['description_link'] ?? '#',
                    'date' => $event['date'],
                    'time' => $event['time'] ?? '19:00'
                ];
            }
            
            return $formattedEvents;
            
        } catch (Exception $e) {
            error_log("Ошибка получения событий: " . $e->getMessage());
            return $this->getDefaultEvents();
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
    
    /**
     * Дефолтные события если нет данных в БД
     */
    private function getDefaultEvents() {
        $today = new DateTime();
        $events = [];
        
        for ($i = 0; $i < 8; $i++) {
            $eventDate = clone $today;
            $eventDate->add(new DateInterval('P' . $i . 'D'));
            
            $day = $eventDate->format('j');
            $month = $this->getMonthShort($eventDate->format('n'));
            
            $eventTitles = [
                'Стрельба из лука', 'Кино на свежем воздухе', 'Йога-класс', 'Кулинарный мастер-класс',
                'Живая музыка', 'Пикник с семьей', 'Дегустация вин', 'Фотосессия на природе'
            ];
            
            $eventDescriptions = [
                'Мастер-класс по стрельбе из лука на свежем воздухе. Подходит для всех уровней подготовки.',
                'Просмотр фильмов под звездным небом с попкорном и горячими напитками.',
                'Утренняя йога на природе с профессиональным инструктором. Начните день с гармонии.',
                'Учимся готовить традиционные блюда с шеф-поваром North Republic.',
                'Выступление местных музыкантов с акустической программой.',
                'Семейный пикник с играми, конкурсами и вкусной едой.',
                'Знакомство с лучшими винами региона в сопровождении сомелье.',
                'Профессиональная фотосессия в живописных местах North Republic.'
            ];
            
            $prices = ['от 150₽', 'от 300₽', 'от 200₽', 'от 500₽', 'от 400₽', 'от 250₽', 'от 800₽', 'от 1200₽'];
            $images = [
                'template/images/gallery/gallery-01.jpg', 'template/images/gallery/gallery-02.jpg',
                'template/images/gallery/gallery-03.jpg', 'template/images/gallery/gallery-04.jpg',
                'template/images/gallery/gallery-05.jpg', 'template/images/gallery/gallery-06.jpg',
                'template/images/gallery/gallery-07.jpg', 'template/images/gallery/gallery-08.jpg'
            ];
            
            $events[] = [
                'day' => $day,
                'month' => $month,
                'title' => $eventTitles[$i] ?? 'Событие',
                'description' => $eventDescriptions[$i] ?? 'Описание события',
                'price' => $prices[$i] ?? 'Уточняйте',
                'image' => $images[$i] ?? 'template/images/gallery/gallery-01.jpg',
                'link' => '/event/' . strtolower(str_replace([' ', '-'], ['-', '-'], $eventTitles[$i] ?? 'event')),
                'date' => $eventDate->format('Y-m-d'),
                'time' => '19:00'
            ];
        }
        
        return $events;
    }
}
?>
