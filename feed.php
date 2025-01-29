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
        echo "<p>Видео пока нет.</p>";
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
    <title>Лента видео</title>
    <style>
        .commenta {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f0f0f0;
        }
        .video-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            border-radius: 15px;
            margin-left: 65vh;
            overflow: hidden;
            background: #000;
        }
        video {
            width: 100%;
            height: auto;
            display: block;
        }
        .overlay {
            position: absolute;
            bottom: 10px;
            left: 10px;
            color: white;
            background: rgba(0, 0, 0, 0.6);
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
            line-height: 1.4;
            box-shadow: 0 0 22px rgba(0, 0, 0, 0.5);
        }
        .bottom-space {
            height: 100px; 
        }
        .play-pause-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
            cursor: pointer;
        }

        .progress-bar-container {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: #333;
            overflow: hidden;
        }

        .progress-bar {
            width: 0;
            height: 100%;
            background: linear-gradient(90deg, red, orange, yellow, green, cyan, blue, violet);
            transition: width 0.1s linear;
        }
        .reaction-buttons {
            position: relative;
            margin-right: 65vh;
            display: flex;
            flex-direction: column;
            gap: 10px;
            
        }
        .reaction-buttons button {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px 15px;
            font-size: 14px;
            margin-left: 5vh;
            cursor: pointer;
            width: 100px;
            transition: background-color 0.2s ease;
        }
        .reaction-buttons button:hover {
            background-color: #e0e0e0;
        }

        .glow-on-hover {
            width: 220px;
            height: 50px;
            border: none;
            outline: none;
            color: #fff;
            background: #111;
            cursor: pointer;
            position: relative;
            z-index: 0;
            border-radius: 10px;
        }

        .glow-on-hover:before {
            content: '';
            background: linear-gradient(45deg, #ff0000, #ff7300, #fffb00, #48ff00, #00ffd5, #002bff, #7a00ff, #ff00c8, #ff0000);
            position: absolute;
            top: -2px;
            left:-2px;
            background-size: 400%;
            z-index: -1;
            filter: blur(5px);
            width: calc(100% + 4px);
            height: calc(100% + 4px);
            animation: glowing 20s linear infinite;
            opacity: 0;
            transition: opacity .3s ease-in-out;
            border-radius: 10px;
        }

        .glow-on-hover:active {
            color: #000
        }

        .glow-on-hover:active:after {
            background: transparent;
        }

        .glow-on-hover:hover:before {
            opacity: 1;
        }

        .glow-on-hover:after {
            z-index: -1;
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: #111;
            left: 0;
            top: 0;
            border-radius: 10px;
        }

        @keyframes glowing {
            0% { background-position: 0 0; }
            50% { background-position: 400% 0; }
            100% { background-position: 0 0; }
        }

        .subscription-buttons {
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include("header.php") ?>
    <div class="commenta">
        <div class="video-container">
            <video id="video" src="<?php echo htmlspecialchars($video['path']); ?>" autoplay loop muted playsinline></video>
            <div class="overlay">
                <p>Author: <?php echo htmlspecialchars($username); ?>(subs <?php echo $subscribers_count; ?>)</p>
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

        availableIds = availableIds.filter(id => id !== currentVideoId);

        window.addEventListener('scroll', () => {
            if (window.innerHeight + window.scrollY >= document.body.offsetHeight) {
                loadRandomVideo();
            }
        });

        function loadRandomVideo() {
            if (availableIds.length > 0) {
                let randomId = availableIds[Math.floor(Math.random() * availableIds.length)];
                window.location.href = "?id=" + randomId;
            } else {
                alert("Нет других доступных видео.");
            }
        }

        document.querySelector('#video').addEventListener('play', () => {
            const videoElement = document.querySelector('#video');
            if (videoElement.muted) {
                videoElement.muted = false;
            }
        });

        const videoElement = document.querySelector('#video');
        const progressBar = document.querySelector('.progress-bar');

        videoElement.addEventListener('timeupdate', () => {
            const progress = (videoElement.currentTime / videoElement.duration) * 100;
            progressBar.style.width = progress + '%';
        });

        function togglePlayPause() {
            if (videoElement.paused) {
                videoElement.play();
            } else {
                videoElement.pause();
            }
        }
    </script>
    <script src="safe.js"></script>
</body>
</html>
