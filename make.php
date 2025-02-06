<?php
include("elements/php/main/db.php");
include("elements/php/main/verify.php");

// Получаем список доступных тем
$sql = "SELECT id, name FROM themes";
$result = $conn->query($sql);
$themes = [];
while ($row = $result->fetch_assoc()) {
    $themes[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video']) && isset($_FILES['cover_image']) && isset($_POST['description']) && isset($_POST['theme_id'])) {
    // Проверка: не превышено ли ограничение в 3 видео в сутки для пользователя
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM videos WHERE user_id = ? AND DATE(upload_time) = CURDATE()");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $resultCount = $stmt->get_result();
    $row = $resultCount->fetch_assoc();
    $stmt->close();

    if ($row['count'] >= 3) {
        echo "<script>
                alert('We cannot upload more than 3 videos in one day. Please try again later.');
                window.location.href = 'myvideos.php';
              </script>";
        exit;
    }    

    $video = $_FILES['video'];
    $description = trim($_POST['description']);
    $coverImage = $_FILES['cover_image'];
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

    if (empty($coverImage['name'])) {
        die("Обложка обязательна.");
    }

    if (!in_array($coverImage['type'], ['image/jpeg', 'image/png'])) {
        die("Неверный формат обложки. Разрешены JPG и PNG.");
    }

    if ($coverImage['size'] > 10 * 1024 * 1024) {
        die("Размер обложки не должен превышать 10 МБ.");
    }

    $uploadDir = 'uploads/videos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $videoPath = $uploadDir . uniqid() . ".mp4";
    if (!move_uploaded_file($video['tmp_name'], $videoPath)) {
        die("Не удалось загрузить видео.");
    }

    $coverImageDir = 'uploads/covers/';
    if (!is_dir($coverImageDir)) {
        mkdir($coverImageDir, 0777, true);
    }

    $coverImageExtension = pathinfo($coverImage['name'], PATHINFO_EXTENSION);
    $coverImageName = uniqid() . '.' . $coverImageExtension;
    $coverImagePath = $coverImageDir . $coverImageName;

    if (!move_uploaded_file($coverImage['tmp_name'], $coverImagePath)) {
        die("Не удалось загрузить обложку.");
    }

    $sql = "INSERT INTO videos (user_id, path, description, upload_time, cover_image_path, theme_id) VALUES (?, ?, ?, NOW(), ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isssi', $user_id, $videoPath, $description, $coverImagePath, $theme_id);
    $stmt->execute();

    // Получаем ID загруженного видео
    $uploadedVideoId = $conn->insert_id;
    $stmt->close();

    header("Location: feed.php?id=" . $uploadedVideoId);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload video on <?php echo htmlentities($project_name); ?></title>
    <link rel="stylesheet" href="elements/css/make.css">
    <meta name="robots" content="noindex, nofollow">
    <style>
        .content input {
          width: 98%;
          justify-content: center;
        }
        .content textarea {
          width: 98%;
          justify-content: center;
        }
        .content button {
          width: 98%;
          justify-content: center;
        }
    </style>
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
        <form method="post" enctype="multipart/form-data">
            <label>Select video (MP4, max 50 MB):</label>
            <input type="file" name="video" accept="video/mp4" required><br>
            <label>Video description:</label>
            <textarea name="description" rows="4" style="text-align: center" required></textarea><br>
            <label>Upload cover image (JPG/PNG, max 10 MB):</label>
            <input type="file" name="cover_image" accept="image/jpeg, image/png" required><br>
            <label>Select theme:</label>
            <select style="margin-bottom: 5vh;" name="theme_id" required>
                <?php foreach ($themes as $theme): ?>
                    <option value="<?php echo $theme['id']; ?>"><?php echo htmlentities($theme['name']); ?></option>
                <?php endforeach; ?>
            </select><br>
            <button type="submit">Upload</button>
        </form>
    </div>
</body>
</html>
