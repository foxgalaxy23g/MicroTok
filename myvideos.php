<?php
include("elements/php/main/db.php");
include("elements/php/main/verify.php");

// Если включён AWS, подключаем AWS S3 (конфигурация и инициализация клиента)
if ($aws_s3_enabled == 1) {
    require_once 'elements/php/main/aws.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video']) && isset($_POST['description'])) {
    $video = $_FILES['video'];
    $description = trim($_POST['description']);

    if (empty($description)) {
        die("Video description is required.");
    }

    if ($video['type'] !== 'video/mp4') {
        die("Invalid file format. Only MP4 is allowed.");
    }

    if ($video['size'] > 50 * 1024 * 1024) {
        die("File size exceeds 50 MB.");
    }

    // Проверяем, что пользователь не превысил дневной лимит (5 видео в сутки)
    $sql = "SELECT COUNT(*) FROM videos WHERE user_id = ? AND DATE(upload_time) = CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count >= 5) {
        die("You can upload up to 5 videos per day.");
    }

    // Загрузка видео: если AWS включён – загружаем в S3, иначе сохраняем локально
    if ($aws_s3_enabled == 1) {
        // Генерируем уникальный ключ в бакете S3 (например, videos/uniqid.mp4)
        $s3Key = 'videos/' . uniqid() . ".mp4";
        try {
            $result = $s3Client->putObject([
                'Bucket'      => S3_BUCKET,
                'Key'         => $s3Key,
                'SourceFile'  => $video['tmp_name'],
                'ACL'         => 'public-read',
                'ContentType' => $video['type'],
            ]);
            // Получаем публичный URL загруженного видео
            $videoUrl = $result->get('ObjectURL');
        } catch (Aws\Exception\AwsException $e) {
            die("Error uploading video to S3: " . $e->getMessage());
        }
    } else {
        // Локальное хранение
        $uploadDir = 'uploads/videos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $videoUrl = $uploadDir . uniqid() . ".mp4";
        if (!move_uploaded_file($video['tmp_name'], $videoUrl)) {
            die("File upload error.");
        }
    }

    // Сохраняем информацию о видео в базе данных (столбец path хранит URL или путь к файлу)
    $sql = "INSERT INTO videos (user_id, path, description, upload_time) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iss', $user_id, $videoUrl, $description);
    $stmt->execute();
    $stmt->close();

    echo "Video uploaded successfully.";
}

// Обновление описания видео
if (isset($_POST['update_description']) && isset($_POST['video_id']) && isset($_POST['new_description'])) {
    $video_id = $_POST['video_id'];
    $new_description = trim($_POST['new_description']);

    $sql = "UPDATE videos SET description = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sii', $new_description, $video_id, $user_id);
    $stmt->execute();
    $stmt->close();

    echo "Description updated successfully.";
}

// Удаление видео
if (isset($_GET['delete_video_id'])) {
    $video_id = $_GET['delete_video_id'];

    $sql = "SELECT path FROM videos WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $video_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($video_path);
    $stmt->fetch();
    $stmt->close();

    if ($video_path) {
        if ($aws_s3_enabled == 1) {
            // При загрузке в S3 мы получили URL вида "https://bucket-name.s3.amazonaws.com/videos/unique.mp4".
            // Извлекаем S3-ключ из URL.
            $parsedUrl = parse_url($video_path, PHP_URL_PATH); // Например, "/videos/unique.mp4"
            $s3Key = ltrim($parsedUrl, '/'); // Убираем начальный слеш
            try {
                $s3Client->deleteObject([
                    'Bucket' => S3_BUCKET,
                    'Key'    => $s3Key,
                ]);
            } catch (Aws\Exception\AwsException $e) {
                echo "Error deleting video from S3: " . $e->getMessage();
            }
        } else {
            if (!unlink($video_path)) {
                echo "Error deleting video file.";
            }
        }

        // Удаляем запись из базы данных
        $sql = "DELETE FROM videos WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $video_id, $user_id);
        $stmt->execute();
        $stmt->close();

        echo "Video deleted successfully.";
    } else {
        echo "Error deleting video.";
    }
}

// Получение списка видео для отображения
$sql = "SELECT v.id, v.cover_image_path, v.description,
           (SELECT COUNT(*) FROM video_views WHERE video_id = v.id) AS views
        FROM videos v
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlentities($project_name); ?> Studio</title>
    <link rel="stylesheet" href="elements/css/myvideos.css">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
    <div class="header-container">
        <?php include("elements/php/blocks/headers/header_no_finder.php");  ?>
    </div>
    <noscript>
        <meta http-equiv="refresh" content="0; url=/javascript.php">
    </noscript>
    <div class="sidebar">
        <a href="make.php"><i></i></a> 
        <a href="make.php"><i>🎥</i>Upload video</a> 
        <a href="myvideos.php"><i>🎞</i>Manage videos</a> 
        <a href="settings.php"><i>⚙</i>Settings</a> 
    </div>

    <div class="content">
        <h1 style="color: rgba(255, 255, 255, 0);">^</h1>
        <h1>My Videos</h1>
        <table style="border-radius: 15px;">
            <tr>
                <th>Video Preview</th>
                <th>Description</th>
                <th>Просмотры</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()) : ?>
                <tr>
                    <td>
                        <a href="feed.php?id=<?php echo $row['id']; ?>">
                            <img src="<?php echo htmlspecialchars($row['cover_image_path']); ?>" alt="Preview" width="200" style="border-radius: 15px; vertical-align: middle;">
                        </a>
                    </td>
                    <td>
                        <form method="post" style="display:inline;">
                            <textarea name="new_description" rows="23" cols="30" style="resize: none; border-radius: 15px; vertical-align: middle;" required><?php echo htmlspecialchars($row['description']); ?></textarea>
                            <input type="hidden" name="video_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="update_description" style="border-radius: 15px; vertical-align: middle;">Update Description</button>
                        </form>
                    </td>
                    <td>
                        <?php echo $row['views']; ?>
                    </td>
                    <td>
                        <a href="?delete_video_id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this video?')">
                            <button style="border-radius: 15px; vertical-align: middle;">Delete</button>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
        <!-- Дополнительные отступы (при необходимости) -->
    </div>
    <!-- Пустые заголовки для отступов -->
    <h1 style="color: rgba(255, 255, 255, 0);">^</h1>
    <h1 style="color: rgba(255, 255, 255, 0);">^</h1>
    <h1 style="color: rgba(255, 255, 255, 0);">^</h1>
    <h1 style="color: rgba(255, 255, 255, 0);">^</h1>
    <h1 style="color: rgba(255, 255, 255, 0);">^</h1>
    <h1 style="color: rgba(255, 255, 255, 0);">^</h1>
    <h1 style="color: rgba(255, 255, 255, 0);">^</h1>
</body>
</html>
