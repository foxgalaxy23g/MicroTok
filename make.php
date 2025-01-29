<?php
include("db.php");

function authenticate($conn) {
    if (!isset($_COOKIE['auth_token'])) {
        header('Location: login.php');
        exit();
    }

    $token = $_COOKIE['auth_token'];
    $sql = "SELECT id FROM users WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();

    if (!$user_id) {
        header('Location: index.php');
        exit();
    }

    return $user_id;
}

$user_id = authenticate($conn);

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
    
    if ($width != 1080 || $height != 1920) {
        die("Cover image must have a resolution of 1080x1920 pixels.");
    }

    if ($width / $height != 9 / 16) {
        die("Cover image must have an aspect ratio of 9:16.");
    }

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
    <title>Video Upload</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: row;
        }

        h1 {
            color: rgb(98, 0, 255);
        }

        form {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: inline-block;
            padding: 20px;
            width: 90%;
            max-width: 500px;
            margin-top: 20px;
        }

        label {
            display: block;
            text-align: left;
            margin-bottom: 8px;
            font-weight: bold;
            color: #4a4a4a;
        }

        input[type="file"], textarea, button {
            width: 100%;
            margin-bottom: 16px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        button {
            background-color: rgb(98, 0, 255);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }

        textarea {
            resize: none;
        }

        .sidebar {
            width: 200px;
            background-color: #fff; 
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding-top: 20px;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
        }

        .sidebar a {
            text-decoration: none;
            color: #333; 
            margin: 20px 0;
            display: flex;
            align-items: center;
            font-size: 18px;
            padding: 10px;
            transition: background-color 0.3s ease;
        }

        .sidebar a i {
            margin-right: 10px;
        }

        .sidebar a:hover {
            background-color: #f0f0f0; 
        }

        .content {
            margin-left: 200px;
            padding: 20px;
            flex-grow: 1;
            width: calc(100% - 200px); 
        }

        .header-container {
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);

            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000; 
        }

        .header-container nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <?php include("header.php"); ?>
    </div>

    <div class="sidebar">
        <h1>^</h1>
        <a href="make.php"><i>🎥</i>Upload video</a>
        <a href="myvideos.php"><i>🎞</i>Manage videos</a>
    </div>

    <div class="content">
        <h1>^</h1>
        <h1>Video Upload</h1>
        <form method="post" enctype="multipart/form-data">
            <label>Select video (MP4, max 50 MB):</label>
            <input type="file" name="video" accept="video/mp4" required><br>
            <label>Video description:</label>
            <textarea name="description" rows="4" placeholder="Enter video description" required></textarea><br>
            <label>Upload cover image (JPG/PNG, min 10 MB, 9:16 aspect ratio):</label>
            <input type="file" name="cover_image" accept="image/jpeg, image/png" required><br>
            <button type="submit">Upload</button>
        </form>
    </div>
</body>
</html>