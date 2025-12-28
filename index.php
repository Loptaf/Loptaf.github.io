<?php
// Загружаем список видео
$videos = json_decode(file_get_contents('videos.json'), true) ?? [];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Видео платформа</title>
    <style>
        /* Стили такие же как в предыдущем примере */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f9f9f9; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #ddd; }
        .upload-btn { background: #ff0000; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .upload-btn:hover { background: #cc0000; }
        .videos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .video-card { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.3s; cursor: pointer; }
        .video-card:hover { transform: translateY(-5px); }
        .thumbnail-container { position: relative; width: 100%; padding-top: 56.25%; overflow: hidden; }
        .thumbnail { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
        .video-info { padding: 15px; }
        .video-title { font-size: 16px; font-weight: bold; margin-bottom: 10px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .video-meta { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
        .author-avatar { width: 30px; height: 30px; border-radius: 50%; }
        .author-name { font-size: 14px; color: #606060; }
        .video-stats { font-size: 14px; color: #606060; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; width: 90%; max-width: 500px; }
        .close-btn { position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .submit-btn { background: #ff0000; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; width: 100%; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Видео платформа</h1>
        <button class="upload-btn" onclick="showUploadModal()">Загрузить видео</button>
    </div>

    <div id="videosContainer" class="videos-grid">
        <?php foreach ($videos as $video): ?>
        <div class="video-card" onclick="window.location.href='video.php?id=<?= $video['id'] ?>'">
            <div class="thumbnail-container">
                <img src="<?= $video['thumbnail'] ? 'uploads/thumbs/' . $video['thumbnail'] : $video['authorAvatar'] ?>" 
                     alt="<?= htmlspecialchars($video['title']) ?>" 
                     class="thumbnail">
            </div>
            <div class="video-info">
                <div class="video-title"><?= htmlspecialchars($video['title']) ?></div>
                <div class="video-meta">
                    <img src="<?= $video['authorAvatar'] ?>" alt="<?= $video['author'] ?>" class="author-avatar">
                    <span class="author-name"><?= $video['author'] ?></span>
                </div>
                <div class="video-stats">
                    <?= $video['views'] ?> просмотров • <?= getTimeAgo($video['uploadDate']) ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Модальное окно загрузки -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="hideUploadModal()">&times;</span>
            <h2>Загрузить новое видео</h2>
            <form id="uploadForm" action="upload.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Название видео:</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Описание:</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Автор:</label>
                    <input type="text" name="author" value="thcpatriot">
                </div>
                <div class="form-group">
                    <label>Видео файл (макс. 500MB):</label>
                    <input type="file" name="video" accept="video/*" required>
                </div>
                <div class="form-group">
                    <label>Превью (миниатюра):</label>
                    <input type="file" name="thumbnail" accept="image/*">
                </div>
                <button type="submit" class="submit-btn">Загрузить</button>
            </form>
        </div>
    </div>

    <script>
        function showUploadModal() {
            document.getElementById('uploadModal').style.display = 'block';
        }
        
        function hideUploadModal() {
            document.getElementById('uploadModal').style.display = 'none';
            document.getElementById('uploadForm').reset();
        }
        
        // Закрытие модального окна при клике вне его
        window.onclick = function(event) {
            const modal = document.getElementById('uploadModal');
            if (event.target == modal) {
                hideUploadModal();
            }
        }
    </script>
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
