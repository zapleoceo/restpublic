<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\UTCDateTime;

function loadEnv(string $path): void {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') === false || strpos($line, '#') === 0) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key) . '=' . trim($value));
    }
}

loadEnv(__DIR__ . '/../.env');

$mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
$dbName = $_ENV['MONGODB_DB_NAME'] ?? 'veranda';

$client = new Client($mongodbUrl);
$db = $client->$dbName;
$events = $db->events;

$date = '2025-10-12';
$time = '20:00';

$doc = [
    'title_ru' => 'Музыкальное лото',
    'title_en' => 'Music Lotto',
    'title_vi' => 'Xổ số âm nhạc',
    'description_ru' => "🎙Караоке игра для тех, кто любить петь и веселиться\n🎶Ведущий включает популярные песни, мы поем их все вместе, не забывая вычеркивать их из индивидуальных билетов.\n🍀Самые везучие участники получают призы и подарки.\n🔥 Никаких долгих вступлений или скучных куплетов, только самые качающие припевы и легендарные фрагменты хитов\n💃 В процессе игры можно (и нужно!) петь, танцевать, пить и отрываться по полной!",
    'description_en' => "🎙Karaoke game for those who love to sing and have fun.\n🎶The host plays popular songs; we sing them together while crossing them off our individual tickets.\n🍀The luckiest participants receive prizes and gifts.\n🔥 No long intros or boring verses — only the catchiest choruses and legendary hit fragments.\n💃 During the game you can (and should!) sing, dance, drink and go all out!",
    'description_vi' => "🎙Trò chơi karaoke dành cho những ai thích hát và vui chơi.\n🎶MC bật các bài hát nổi tiếng; chúng ta cùng hát và gạch chúng khỏi vé của mình.\n🍀Người may mắn nhất nhận quà và phần thưởng.\n🔥 Không có phần mở đầu dài dòng hay đoạn hát nhàm chán — chỉ những điệp khúc bùng nổ và đoạn hit huyền thoại.\n💃 Trong lúc chơi bạn có thể (và nên) hát, nhảy, uống và quẩy hết mình!",
    'conditions_ru' => '150 000 VND',
    'conditions_en' => '150,000 VND',
    'conditions_vi' => '150.000 VND',
    'date' => $date,
    'time' => $time,
    'image' => '',
    'link' => '',
    'category' => 'Музыкальное',
    'is_active' => true,
    'created_at' => new UTCDateTime(),
    'updated_at' => new UTCDateTime()
];

$existing = $events->findOne(['title_ru' => $doc['title_ru'], 'date' => $date, 'time' => $time]);
if ($existing) {
    echo "Event already exists: " . (string)$existing['_id'] . "\n";
    exit(0);
}

$res = $events->insertOne($doc);

echo "Inserted: " . (string)$res->getInsertedId() . "\n";
