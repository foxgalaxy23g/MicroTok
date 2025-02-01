<?php
include("db.php");

function authenticate($conn) {
    if (!isset($_COOKIE['auth_token'])) {
        header('Location: index.php');
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

    $filePath = $uploadDir . uniqid() . ".mp4";
    if (move_uploaded_file($video['tmp_name'], $filePath)) {
        $sql = "INSERT INTO videos (user_id, path, description, upload_time) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iss', $user_id, $filePath, $description);
        $stmt->execute();
        $stmt->close();
        echo "Video uploaded successfully.";
    } else {
        echo "File upload error.";
    }
}

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

if (isset($_GET['delete_video_id'])) {
    $video_id = $_GET['delete_video_id'];

    $sql = "SELECT path FROM videos WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $video_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($video_path);
    $stmt->fetch();
    $stmt->close();

    if ($video_path && unlink($video_path)) {
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

$sql = "SELECT id, path, description FROM videos WHERE user_id = ?";
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
    <style>
    </style>
    <link rel="stylesheet" href="elements/css/myvideos.css">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
    <div class="header-container">
        <?php include("header.php"); ?>
    </div>

    <div class="sidebar">
        <a href="make.php"><i></i></a> 
        <a href="make.php"><i>🎥</i>Upload video</a> 
        <a href="myvideos.php"><i>🎞</i>Manage videos</a> 
        <a href="settings.php"><i>⚙</i>Settings</a> 
    </div>

    <div class="content">
        <h1>^</h1>
        <h1>My Videos</h1>
        <table>
            <tr>
                <th>Video</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()) : ?>
                <tr>
                    <td>
                        <video width="200" controls>
                            <source src="<?php echo htmlspecialchars($row['path']); ?>" type="video/mp4">
                        </video>
                    </td>
                    <td>
                        <form method="post" style="display:inline;">
                            <textarea name="new_description" rows="2" cols="30" required><?php echo htmlspecialchars($row['description']); ?></textarea>
                            <input type="hidden" name="video_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="update_description">Update Description</button>
                        </form>
                    </td>
                    <td>
                        <a href="?delete_video_id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this video?')">
                            <button>Delete</button>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
