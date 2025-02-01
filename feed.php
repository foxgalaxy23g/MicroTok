<?php
include("elements/php/db.php");

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['video_id'])) {
    $user_id = authenticate($conn); 
    $video_id = (int) $_POST['video_id'];
    $action = $_POST['action']; 

    if (in_array($action, ['like', 'dislike'])) {

        $sql = "DELETE FROM video_likes WHERE video_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $video_id, $user_id);
        $stmt->execute();
        $stmt->close();

        $sql = "INSERT INTO video_likes (video_id, user_id, reaction) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $video_id, $user_id, $action);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: ?id=$video_id");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['channel_id'])) {
    $user_id = authenticate($conn);
    $channel_id = (int) $_POST['channel_id'];
    $action = $_POST['action'];

    if ($action === 'subscribe') {

        $sql = "INSERT INTO subscriptions (user_id, channel_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $user_id, $channel_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'unsubscribe') {
        $sql = "DELETE FROM subscriptions WHERE user_id = ? AND channel_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $user_id, $channel_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: ?id=$video_id");
    exit();
}

$ids = [];
$sql = "SELECT id FROM videos";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['id'];
    }
}

$video_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($video_id && in_array($video_id, $ids)) {

    $sql = "SELECT * FROM videos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $video_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {

    if (!empty($ids)) {
        $random_id = $ids[array_rand($ids)];
        header("Location: ?id=" . $random_id);
        exit;
    } else {
        include("header.php");
        echo "<h1>";
        echo htmlentities($project_name);
        echo " has no video at all</h1>";
        echo "<p>But you can publish the video first on this platform!</p>";
        echo "<a href='make.php'>uplaod first video</a>";
        exit;
    }
}

if ($result->num_rows > 0) {
    $video = $result->fetch_assoc();
    $user_id = $video['user_id'];
    $sql = "SELECT username FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $username = $user_result->num_rows > 0 ? $user_result->fetch_assoc()['username'] : 'Unknown User';
    $ids = array_diff($ids, [$video_id]);

    $sql = "SELECT username, avatar FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user_data = $user_result->num_rows > 0 ? $user_result->fetch_assoc() : null;
    $username = $user_data ? $user_data['username'] : 'Unknown User';
    $avatar = $user_data ? $user_data['avatar'] : 'default-avatar.jpg'; 


    $sql = "SELECT 
        SUM(CASE WHEN reaction = 'like' THEN 1 ELSE 0 END) AS likes,
        SUM(CASE WHEN reaction = 'dislike' THEN 1 ELSE 0 END) AS dislikes
    FROM video_likes WHERE video_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $video_id);
    $stmt->execute();
    $stmt->bind_result($likes, $dislikes);
    $stmt->fetch();
    $stmt->close();

    $sql = "SELECT COUNT(*) FROM subscriptions WHERE user_id = ? AND channel_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $video['user_id']);
    $stmt->execute();
    $stmt->bind_result($is_subscribed);
    $stmt->fetch();
    $stmt->close();

    $sql = "SELECT COUNT(*) FROM subscriptions WHERE channel_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $video['user_id']);
    $stmt->execute();
    $stmt->bind_result($subscribers_count);
    $stmt->fetch();
    $stmt->close();
} else {
    echo "<p>Видео не найдено.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlentities(mb_strimwidth($video['description'], 0, 60, '...'), ENT_QUOTES, 'UTF-8'); ?> - MicroTok</title>
    <link rel="stylesheet" href="elements/css/feed.css">
    <style>

    </style>
    <meta name="description" content="Watch <?php echo htmlentities(mb_strimwidth($video['description'], 0, 150, '...'), ENT_QUOTES, 'UTF-8'); ?> by <?php echo htmlentities($username, ENT_QUOTES, 'UTF-8'); ?> in MicroTok">

</head>
<body>
    <?php include("header.php") ?>
    <div class="commenta">
        <div class="video-container">
            <video id="video" src="<?php echo htmlspecialchars($video['path']); ?>" autoplay loop muted playsinline></video>
            <div class="overlay">
                <p><img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" style="width: 15px; height: 15px; border-radius: 50%; box-shadow: 0 0 5px rgba(0, 0, 0, 0.5);">Author: <?php echo htmlspecialchars($username); ?>(subs <?php echo $subscribers_count; ?>)</p>
                <p>Description: <?php echo htmlspecialchars($video['description']); ?></p>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar"></div>
        </div>
        <div class="play-pause-layer" onclick="togglePlayPause()"></div>
    </div>

    <div class="reaction-buttons">
            <form method="post">
                <input type="hidden" name="video_id" value="<?php echo $video_id; ?>">
                <input type="hidden" name="action" value="like">
                <button type="submit" style="border-radius: 10vh; height: 10vh; box-shadow: 0 0 5px rgba(0, 0, 0, 0.5);"><h2 style="font-size: 1.3rem;">👍</h2> <h2 style="font-size: 1rem;"><?php echo $likes; ?></h2></button>
            </form>
            <form method="post">
                <input type="hidden" name="video_id" value="<?php echo $video_id; ?>">
                <input type="hidden" name="action" value="dislike">
                <button type="submit" class="navbut" class="navbut" style="border-radius: 10vh; height: 10vh; box-shadow: 0 0 5px rgba(0, 0, 0, 0.5);"><h2 style="font-size: 1.3rem;">👎</h2><h2 style="font-size: 1rem;"><?php echo $dislikes; ?></h2></button>
            </form>
            <div class="subscription-buttons">
            <?php if ($is_subscribed > 0): ?>
            <form method="post">
                <input type="hidden" name="action" value="unsubscribe">
                <input type="hidden" name="channel_id" value="<?php echo $video['user_id']; ?>">
                <button type="submit" style="border-radius: 10vh; height: 10vh; box-shadow: 0 0 5px rgba(0, 0, 0, 0.5);"><h2>-</h2></button>
            </form>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="action" value="subscribe">
                <input type="hidden" name="channel_id" value="<?php echo $video['user_id']; ?>">
                <button type="submit" style="border-radius: 10vh; height: 10vh; box-shadow: 0 0 5px rgba(0, 0, 0, 0.5);"><h2">+</h2></button>
            </form>
        <?php endif; ?>
            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" style="width: 100px; height: 100px; border-radius: 50%; margin-top: 10px; margin-left: 4.8vh; box-shadow: 0 0 5px rgba(0, 0, 0, 0.5);">
    </div>
        </div>

    <div class="bottom-space"></div>

    <script>
        let availableIds = <?php echo json_encode(array_values($ids)); ?>;
        let currentVideoId = <?php echo $video['id']; ?>;
    </script>
    <script src="elements/js/feed.js"></script>
    <script src="elements/js/safe.js"></script>
</body>
</html>
