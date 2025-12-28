<?php
$videoId = $_GET['id'] ?? '';
$videos = json_decode(file_get_contents('videos.json'), true) ?? [];
$video = null;

foreach ($videos as $v) {
    if ($v['id'] == $videoId) {
        $video = $v;
        break;
    }
}

if (!$video) {
    header('Location: index.php');
    exit;
}

// Увеличиваем счетчик просмотров
$video['views']++;
foreach ($videos as &$v) {
    if ($v['id'] == $videoId) {
        $v['views'] = $video['views'];
        break;
    }
}
file_put_contents('videos.json', json_encode($videos, JSON_PRETTY_PRINT));

// Рекомендации (исключая текущее видео)
$recommendations = array_filter($videos, function($v) use ($videoId) {
    return $v['id'] != $videoId;
});
$recommendations = array_slice($recommendations, 0, 5);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($video['title']) ?></title>
    <style>
        /* Стили такие же как в предыдущем примере для видео страницы */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f9f9f9; padding: 20px; max-width: 1200px; margin: 0 auto; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #ff0000; text-decoration: none; font-size: 16px; }
        .back-link:hover { text-decoration: underline; }
        .video-container { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .main-video-section { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .video-player { width: 100%; }
        .video-player video { width: 100%; aspect-ratio: 16/9; background: black; display: block; }
        .video-info { padding: 20px; }
        .video-title { font-size: 24px; margin-bottom: 15px; }
        .video-stats { display: flex; align-items: center; gap: 20px; margin-bottom: 20px; color: #606060; }
        .video-description { background: #f5f5f5; padding: 15px; border-radius: 5px; margin-top: 20px; }
        .author-info { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
        .author-avatar { width: 50px; height: 50px; border-radius: 50%; }
        .author-details h3 { margin-bottom: 5px; }
        .recommendations { display: flex; flex-direction: column; gap: 15px; }
        .recommended-video { display: flex; gap: 10px; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.3s; }
        .recommended-video:hover { transform: translateX(-5px); }
        .recommended-thumb { width: 120px; height: 68px; object-fit: cover; flex-shrink: 0; }
        .recommended-info { padding: 10px 10px 10px 0; flex-grow: 1; }
        .recommended-title { font-size: 14px; font-weight: bold; margin-bottom: 5px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .recommended-meta { font-size: 12px; color: #606060; }
    </style>
</head>
<body>
    <a href="index.php" class="back-link">← Назад к видео</a>
    
    <div class="video-container">
        <div class="main-video-section">
            <div class="video-player">
                <video controls>
                    <source src="uploads/<?= $video['videoFile'] ?>" type="video/mp4">
                    Ваш браузер не поддерживает видео.
                </video>
            </div>
            <div class="video-info">
                <h1 class="video-title"><?= htmlspecialchars($video['title']) ?></h1>
                <div class="video-stats">
                    <span id="viewCount"><?= $video['views'] ?> просмотров</span>
                    <span id="uploadTime"><?= getTimeAgo($video['uploadDate']) ?></span>
                </div>
                <div class="author-info">
                    <img src="<?= $video['authorAvatar'] ?>" alt="<?= $video['author'] ?>" class="author-avatar">
                    <div class="author-details">
                        <h3><?= $video['author'] ?></h3>
                        <span>Подписчики: 100K</span>
                    </div>
                </div>
                <div class="video-description">
                    <?= nl2br(htmlspecialchars($video['description'] ?? 'Нет описания')) ?>
                </div>
            </div>
        </div>
        
        <div class="recommendations">
            <h3 style="margin-bottom: 15px;">Рекомендуем посмотреть</h3>
            <?php foreach ($recommendations as $rec): ?>
            <div class="recommended-video" onclick="window.location.href='video.php?id=<?= $rec['id'] ?>'">
                <img src="<?= $rec['thumbnail'] ? 'uploads/thumbs/' . $rec['thumbnail'] : $rec['authorAvatar'] ?>" 
                     alt="<?= htmlspecialchars($rec['title']) ?>" 
                     class="recommended-thumb">
                <div class="recommended-info">
                    <div class="recommended-title"><?= htmlspecialchars($rec['title']) ?></div>
                    <div class="recommended-meta">
                        <?= $rec['author'] ?> • <?= $rec['views'] ?> просмотров • <?= getTimeAgo($rec['uploadDate']) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
<?php
function getTimeAgo($dateString) {
    $uploadDate = new DateTime($dateString);
    $now = new DateTime();
    $diff = $now->diff($uploadDate);
    
    if ($diff->y > 0) {
        return $diff->y . ' ' . getNoun($diff->y, ['год', 'года', 'лет']) . ' назад';
    } elseif ($diff->m > 0) {
        return $diff->m . ' ' . getNoun($diff->m, ['месяц', 'месяца', 'месяцев']) . ' назад';
    } elseif ($diff->d > 0) {
        return $diff->d . ' ' . getNoun($diff->d, ['день', 'дня', 'дней']) . ' назад';
    } elseif ($diff->h > 0) {
        return $diff->h . ' ' . getNoun($diff->h, ['час', 'часа', 'часов']) . ' назад';
    } elseif ($diff->i > 0) {
        return $diff->i . ' ' . getNoun($diff->i, ['минута', 'минуты', 'минут']) . ' назад';
    } else {
        return 'только что';
    }
}

function getNoun($number, $titles) {
    $cases = [2, 0, 1, 1, 1, 2];
    return $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
}
?>
