<?php
include("elements/php/db.php");
include("elements/php/closed.php");

include("elements/php/verify.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video']) && isset($_FILES['cover_image']) && isset($_POST['description'])) {
    $video = $_FILES['video'];
    $description = trim($_POST['description']);
    $coverImage = $_FILES['cover_image'];

    if (empty($description)) {
        die("Video description is required.");
    }

    if ($video['type'] !== 'video/mp4') {
        die("Invalid video format. Only MP4 is allowed.");
    }

    if ($video['size'] > 50 * 1024 * 1024) {
        die("Video file size exceeds 50 MB.");
    }

    if (empty($coverImage['name'])) {
        die("Cover image is required.");
    }

    if (!in_array($coverImage['type'], ['image/jpeg', 'image/png'])) {
        die("Invalid cover image format. Only JPG and PNG are allowed.");
    }

    if ($coverImage['size'] > 10 * 1024 * 1024) {
        die("Cover image size must be at least 10 MB.");
    }

    list($width, $height) = getimagesize($coverImage['tmp_name']);
    
    //if ($width != 1080 || $height != 1920) {
    //    die("Cover image must have a resolution of 1080x1920 pixels.");
    //}
//
    //if ($width / $height != 9 / 16) {
    //    die("Cover image must have an aspect ratio of 9:16.");
    //}

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

    $uploadDir = 'uploads/videos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $videoPath = $uploadDir . uniqid() . ".mp4";
    if (!move_uploaded_file($video['tmp_name'], $videoPath)) {
        die("Video upload failed.");
    }

    $coverImageDir = 'uploads/covers/';
    if (!is_dir($coverImageDir)) {
        mkdir($coverImageDir, 0777, true);
    }

    $coverImageExtension = pathinfo($coverImage['name'], PATHINFO_EXTENSION);
    $coverImageName = uniqid() . '.' . $coverImageExtension;
    $coverImagePath = $coverImageDir . $coverImageName;

    if (!move_uploaded_file($coverImage['tmp_name'], $coverImagePath)) {
        die("Cover image upload failed.");
    }

    $sql = "INSERT INTO videos (user_id, path, cover_image_path, description, upload_time) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isss', $user_id, $videoPath, $coverImagePath, $description);
    $stmt->execute();
    $stmt->close();

    echo "Video uploaded successfully with cover image.";
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
</head>
<body>
    <div class="header-container">
        <?php 
        include("header.php"); 
        include("elements/php/closed.php");
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
            <textarea name="description" rows="4" placeholder="Enter video description" required></textarea><br>
            <label>Upload cover image (JPG/PNG, max 10 MB, auto-resize to 1080x1920):</label>
            <input type="file" name="cover_image" accept="image/jpeg, image/png" required><br>
            <button type="submit">Upload</button>
        </form>
    </div>
</body>
</html>
