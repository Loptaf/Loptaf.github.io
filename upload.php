<?php
header('Content-Type: application/json');

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешен']);
    exit;
}

// Настройки загрузки
$maxFileSize = 500 * 1024 * 1024; // 500MB
$allowedVideoTypes = ['video/mp4', 'video/webm', 'video/ogg'];
$allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

// Создаем папки если их нет
if (!file_exists('uploads')) mkdir('uploads', 0777, true);
if (!file_exists('uploads/thumbs')) mkdir('uploads/thumbs', 0777, true);

// Проверяем видео файл
if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Ошибка загрузки видео файла']);
    exit;
}

$videoFile = $_FILES['video'];

// Проверяем размер видео
if ($videoFile['size'] > $maxFileSize) {
    echo json_encode(['error' => 'Файл слишком большой. Максимум 500MB']);
    exit;
}

// Проверяем тип видео
$videoType = mime_content_type($videoFile['tmp_name']);
if (!in_array($videoType, $allowedVideoTypes)) {
    echo json_encode(['error' => 'Недопустимый формат видео. Разрешены: MP4, WebM, OGG']);
    exit;
}

// Обрабатываем миниатюру
$thumbnailName = null;
if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
    $thumbnailFile = $_FILES['thumbnail'];
    $thumbnailType = mime_content_type($thumbnailFile['tmp_name']);
    
    if (in_array($thumbnailType, $allowedImageTypes)) {
        $thumbnailExt = pathinfo($thumbnailFile['name'], PATHINFO_EXTENSION);
        $thumbnailName = uniqid('thumb_') . '.' . $thumbnailExt;
        move_uploaded_file($thumbnailFile['tmp_name'], 'uploads/thumbs/' . $thumbnailName);
    }
}

// Генерируем уникальные имена файлов
$videoExt = pathinfo($videoFile['name'], PATHINFO_EXTENSION);
$videoFileName = uniqid('video_') . '.' . $videoExt;

// Перемещаем видео файл
move_uploaded_file($videoFile['tmp_name'], 'uploads/' . $videoFileName);

// Читаем существующие видео
$videos = json_decode(file_get_contents('videos.json'), true) ?? [];

// Создаем новую запись о видео
$newVideo = [
    'id' => uniqid(),
    'title' => $_POST['title'] ?? 'Без названия',
    'description' => $_POST['description'] ?? '',
    'videoFile' => $videoFileName,
    'thumbnail' => $thumbnailName,
    'author' => $_POST['author'] ?? 'Anonymous',
    'authorAvatar' => 'https://yt3.ggpht.com/SzQ7y98PMEoYeLYjBP2e9mC_eItlh1wOdP5H0n0QtZViTJ8OKDlnAOdUv2TiXTmfmkLFj3o2=s600-c-k-c0x00ffffff-no-rj-rp-mo',
    'views' => 0,
    'uploadDate' => date('c')
];

// Добавляем видео в начало массива
array_unshift($videos, $newVideo);

// Сохраняем в файл
file_put_contents('videos.json', json_encode($videos, JSON_PRETTY_PRINT));

// Возвращаем успешный ответ
echo json_encode([
    'success' => true,
    'video' => $newVideo,
    'redirect' => 'video.php?id=' . $newVideo['id']
]);
?>
