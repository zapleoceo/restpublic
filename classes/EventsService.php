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
     * Получить локализованное поле события с автоматическим переводом
     */
    private function getLocalizedField($event, $field, $language) {
        // Пробуем получить поле для указанного языка
        $localizedField = $field . '_' . $language;
        if (isset($event[$localizedField]) && !empty($event[$localizedField])) {
            return $event[$localizedField];
        }
        
        // Fallback на русский язык
        if ($language !== 'ru' && isset($event[$field . '_ru']) && !empty($event[$field . '_ru'])) {
            $russianText = $event[$field . '_ru'];
            
            // Автоматический перевод для английского и вьетнамского
            if ($language === 'en' || $language === 'vi') {
                $translatedText = $this->autoTranslate($russianText, $language);
                if ($translatedText && $translatedText !== $russianText) {
                    // Сохраняем перевод в базу данных для будущего использования
                    $this->saveTranslation($event['_id'], $field, $language, $translatedText);
                    return $translatedText;
                }
            }
            
            return $russianText;
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
     * Автоматический перевод текста
     */
    private function autoTranslate($text, $targetLanguage) {
        // Простой словарь для базовых переводов
        $translations = [
            'en' => [
                'Мафия' => 'Mafia',
                'Квест' => 'Quest',
                'Барбекю' => 'Barbecue',
                'Лазертаг' => 'Laser Tag',
                'Арчеритаг' => 'Archery Tag',
                'Вечеринка' => 'Party',
                'Концерт' => 'Concert',
                'Фестиваль' => 'Festival',
                'Турнир' => 'Tournament',
                'Мастер-класс' => 'Master Class',
                'Дегустация' => 'Tasting',
                'Презентация' => 'Presentation',
                'Встреча' => 'Meeting',
                'Семинар' => 'Seminar',
                'Тренинг' => 'Training'
            ],
            'vi' => [
                'Мафия' => 'Mafia',
                'Квест' => 'Quest',
                'Барбекю' => 'Thịt nướng',
                'Лазертаг' => 'Laser Tag',
                'Арчеритаг' => 'Bắn cung',
                'Вечеринка' => 'Tiệc tùng',
                'Концерт' => 'Buổi hòa nhạc',
                'Фестиваль' => 'Lễ hội',
                'Турнир' => 'Giải đấu',
                'Мастер-класс' => 'Lớp học chuyên sâu',
                'Дегустация' => 'Nếm thử',
                'Презентация' => 'Thuyết trình',
                'Встреча' => 'Cuộc gặp',
                'Семинар' => 'Hội thảo',
                'Тренинг' => 'Đào tạo'
            ]
        ];
        
        // Простая замена ключевых слов
        $translatedText = $text;
        if (isset($translations[$targetLanguage])) {
            foreach ($translations[$targetLanguage] as $ru => $translated) {
                $translatedText = str_replace($ru, $translated, $translatedText);
            }
        }
        
        return $translatedText;
    }
    
    /**
     * Сохранить перевод в базу данных
     */
    private function saveTranslation($eventId, $field, $language, $translatedText) {
        try {
            $updateData = [
                $field . '_' . $language => $translatedText,
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ];
            
            $this->eventsCollection->updateOne(
                ['_id' => $eventId],
                ['$set' => $updateData]
            );
        } catch (Exception $e) {
            error_log("Ошибка сохранения перевода: " . $e->getMessage());
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
