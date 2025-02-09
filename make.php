<?php
include("elements/php/main/db.php");
include("elements/php/main/verify.php");
require_once 'elements/php/main/aws.php';

// Получаем список доступных тем
$sql = "SELECT id, name FROM themes";
$result = $conn->query($sql);
$themes = [];
while ($row = $result->fetch_assoc()) {
    $themes[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video']) && isset($_POST['description']) && isset($_POST['theme_id'])) {
    // Проверка: не превышено ли ограничение в 3 видео в сутки для пользователя
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM videos WHERE user_id = ? AND DATE(upload_time) = CURDATE()");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $resultCount = $stmt->get_result();
    $row = $resultCount->fetch_assoc();
    $stmt->close();

    if ($row['count'] >= 3) {
        echo "<script>
                alert('Мы не можем загрузить более 3 видео в сутки. Попробуйте позже.');
                window.location.href = 'myvideos.php';
              </script>";
        exit;
    }

    $video = $_FILES['video'];
    $description = trim($_POST['description']);
    $theme_id = intval($_POST['theme_id']);

    if (empty($description)) {
        die("Описание видео обязательно.");
    }

    if ($video['type'] !== 'video/mp4') {
        die("Неверный формат видео. Разрешён только MP4.");
    }

    if ($video['size'] > 50 * 1024 * 1024) {
        die("Размер файла видео превышает 50 МБ.");
    }

    // --- Сжатие видео с помощью ffmpeg ---
    // Создаём временный файл для сжатого видео
    $compressedVideoFile = tempnam(sys_get_temp_dir(), 'compressed_') . '.mp4';
    // Параметры: перекодировка видео с использованием кодека libx264, CRF 18 для минимальной потери качества,
    // preset slow для компромисса между скоростью и эффективностью сжатия, аудио копируется без изменений.
    $ffmpegCommand = "C:\\ffmpeg\\bin\\ffmpeg.exe -i " . escapeshellarg($video['tmp_name']) . " -c:v libx264 -crf 18 -preset slow -c:a copy " . escapeshellarg($compressedVideoFile) . " 2>&1";
    exec($ffmpegCommand, $ffmpegOutput, $ffmpegReturnVar);
    if ($ffmpegReturnVar !== 0) {
        die("Ошибка при сжатии видео: " . implode("\n", $ffmpegOutput));
    }
    // --- Конец сжатия видео ---

    // --- Обработка обложки (cover image) ---
    // Если обложка загружена пользователем, используем её; иначе генерируем превью из первого кадра видео.
    if (isset($_FILES['cover_image']) && !empty($_FILES['cover_image']['name'])) {
        $coverImage = $_FILES['cover_image'];
        if (!in_array($coverImage['type'], ['image/jpeg', 'image/png'])) {
            die("Неверный формат обложки. Разрешены JPG и PNG.");
        }
        if ($coverImage['size'] > 10 * 1024 * 1024) {
            die("Размер обложки не должен превышать 10 МБ.");
        }
        $coverImagePath = $coverImage['tmp_name'];
        $coverImageExtension = pathinfo($coverImage['name'], PATHINFO_EXTENSION);
        $coverImageContentType = $coverImage['type'];
    } else {
        // Генерация превью из первого кадра сжатого видео
        $coverImagePath = tempnam(sys_get_temp_dir(), 'preview_') . '.png';
        // Извлекаем первый кадр; можно задать смещение времени, например, "-ss 00:00:00.000"
        $ffmpegPreviewCommand = "C:\\ffmpeg\\bin\\ffmpeg.exe -i " . escapeshellarg($compressedVideoFile) . " -vframes 1 " . escapeshellarg($coverImagePath) . " 2>&1";
        exec($ffmpegPreviewCommand, $ffmpegPreviewOutput, $ffmpegPreviewReturnVar);
        if ($ffmpegPreviewReturnVar !== 0) {
            die("Ошибка при генерации превью: " . implode("\n", $ffmpegPreviewOutput));
        }
        $coverImageContentType = 'image/png';
        $coverImageExtension = 'png';
    }
    // --- Конец обработки обложки ---

    // Генерируем уникальное имя для видео в бакете S3
    $videoKey = 'videos/' . uniqid() . ".mp4";

    try {
        $result = $s3Client->putObject([
            'Bucket'      => S3_BUCKET,
            'Key'         => $videoKey,
            'SourceFile'  => $compressedVideoFile,
            'ACL'         => 'public-read', // файл будет общедоступным
            'ContentType' => 'video/mp4',
        ]);
    } catch (AwsException $e) {
        die("Ошибка загрузки видео в S3: " . $e->getMessage());
    }

    // Получаем URL загруженного видео
    $videoUrl = $result->get('ObjectURL');

    // Генерируем уникальное имя для обложки в бакете S3
    $coverImageKey = 'covers/' . uniqid() . '.' . $coverImageExtension;

    try {
        $result = $s3Client->putObject([
            'Bucket'      => S3_BUCKET,
            'Key'         => $coverImageKey,
            'SourceFile'  => $coverImagePath,
            'ACL'         => 'public-read',
            'ContentType' => $coverImageContentType,
        ]);
    } catch (AwsException $e) {
        die("Ошибка загрузки обложки в S3: " . $e->getMessage());
    }

    // Получаем URL загруженной обложки
    $coverImageUrl = $result->get('ObjectURL');

    // Сохраняем данные о видео в базе данных
    $sql = "INSERT INTO videos (user_id, path, description, upload_time, cover_image_path, theme_id) VALUES (?, ?, ?, NOW(), ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isssi', $user_id, $videoUrl, $description, $coverImageUrl, $theme_id);
    $stmt->execute();
    $uploadedVideoId = $conn->insert_id;
    $stmt->close();

    // Удаляем временные файлы
    if (file_exists($compressedVideoFile)) {
        unlink($compressedVideoFile);
    }
    // Если обложка была сгенерирована автоматически, удаляем её временный файл
    if ((!isset($_FILES['cover_image']) || empty($_FILES['cover_image']['name'])) && file_exists($coverImagePath)) {
        unlink($coverImagePath);
    }

    header("Location: feed.php?id=" . $uploadedVideoId);
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload video on <?php echo htmlentities($project_name); ?></title>
    <link rel="stylesheet" href="elements/css/make.css">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
    <div class="header-container">
        <?php 
        include("elements/php/blocks/header_old.php"); 
        ?>
    </div>
    <noscript>
        <meta http-equiv="refresh" content="0; url=/javascript.html">
    </noscript>
    <div class="sidebar">
        <a href="make.php"><i></i></a> 
        <a href="make.php"><i>🎥</i>Upload video</a>
        <a href="myvideos.php"><i>🎞</i>Manage videos</a>
        <a href="settings.php"><i>⚙</i>Settings</a> 
    </div>
    <div class="content">
        <h1>^</h1>
        <h1>Upload Video</h1>
        <!-- Обратите внимание: теперь загрузка обложки является опциональной -->
        <form method="post" enctype="multipart/form-data">
            <label>Select video (MP4, max 50 MB):</label>
            <input type="file" name="video" accept="video/mp4" required><br>
            <label>Video description:</label>
            <textarea name="description" rows="4" style="text-align: center" required></textarea><br>
            <label>Upload cover image (JPG/PNG, max 10 MB) - опционально:</label>
            <input type="file" name="cover_image" accept="image/jpeg, image/png"><br>
            <label>Select theme:</label>
            <select style="margin-bottom: 5vh;" name="theme_id" required>
                <?php foreach ($themes as $theme): ?>
                    <option value="<?php echo $theme['id']; ?>"><?php echo htmlentities($theme['name']); ?></option>
                <?php endforeach; ?>
            </select><br>
            <button type="submit">Upload</button>
        </form>
    </div>
    <script src="elements/js/safe.js"></script>
</body>
</html>
