<?php
$image = '/var/www/northrepubli_usr/data/www/northrepublic.me/images/intro-pic-primary.jpg';
echo "Проверяю изображение: $image\n";
$info = getimagesize($image);
if ($info) {
    echo "Размер: " . $info[0] . "x" . $info[1] . "\n";
    echo "Тип: " . $info['mime'] . "\n";
    echo "Размер файла: " . filesize($image) . " байт\n";
} else {
    echo "Ошибка чтения изображения\n";
}
?>
