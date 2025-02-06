<?php
// api1.php
session_start();
include("elements/php/main/db.php");
include("elements/php/main/verify.php");

// Проверяем наличие идентификатора текущего пользователя в сессии
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Пользователь не авторизован']);
    exit();
}
$current_user_id = (int) $_SESSION['user_id'];

/*
  Обработка AJAX-запросов:
*/

// 1. Лайки/дизлайки для видео
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['video_id']) && !isset($_POST['load_comments'])) {
    $video_id = (int) $_POST['video_id'];
    $action = $_POST['action'];
    if (in_array($action, ['like', 'dislike'])) {
        // Удаляем предыдущую реакцию
        $sql = "DELETE FROM video_likes WHERE video_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $video_id, $current_user_id);
        $stmt->execute();
        $stmt->close();
        // Добавляем новую реакцию
        $sql = "INSERT INTO video_likes (video_id, user_id, reaction) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $video_id, $current_user_id, $action);
        $stmt->execute();
        $stmt->close();
    }
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
    echo json_encode(['likes' => $likes, 'dislikes' => $dislikes]);
    exit();
}

// 2. Подписка/отписка канала
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['channel_id'])) {
    $channel_id = (int) $_POST['channel_id'];
    $action = $_POST['action'];
    if ($action === 'subscribe') {
        $sql = "SELECT COUNT(*) FROM subscriptions WHERE user_id = ? AND channel_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $current_user_id, $channel_id);
        $stmt->execute();
        $stmt->bind_result($exists);
        $stmt->fetch();
        $stmt->close();
        if ($exists == 0) {
            $sql = "INSERT INTO subscriptions (user_id, channel_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $current_user_id, $channel_id);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($action === 'unsubscribe') {
        $sql = "DELETE FROM subscriptions WHERE user_id = ? AND channel_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $current_user_id, $channel_id);
        $stmt->execute();
        $stmt->close();
    }
    echo json_encode(['status' => 'success']);
    exit();
}

// 3. Лайки/дизлайки для комментариев
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_action'], $_POST['comment_id'])) {
    $comment_id = (int) $_POST['comment_id'];
    $action = $_POST['comment_action'];
    if (in_array($action, ['like', 'dislike'])) {
        $sql = "DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $comment_id, $current_user_id);
        $stmt->execute();
        $stmt->close();
        $sql = "INSERT INTO comment_likes (comment_id, user_id, reaction) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $comment_id, $current_user_id, $action);
        $stmt->execute();
        $stmt->close();
    }
    $sql = "SELECT 
                IFNULL((SELECT SUM(CASE WHEN reaction = 'like' THEN 1 ELSE 0 END) FROM comment_likes WHERE comment_id = ?),0) AS likes,
                IFNULL((SELECT SUM(CASE WHEN reaction = 'dislike' THEN 1 ELSE 0 END) FROM comment_likes WHERE comment_id = ?),0) AS dislikes";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $comment_id, $comment_id);
    $stmt->execute();
    $stmt->bind_result($likes, $dislikes);
    $stmt->fetch();
    $stmt->close();
    echo json_encode(['likes' => $likes, 'dislikes' => $dislikes]);
    exit();
}

// 4. Отправка нового комментария
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'], $_POST['video_id']) && !isset($_POST['reply'])) {
    $video_id = (int) $_POST['video_id'];
    $comment = htmlspecialchars($_POST['comment'], ENT_QUOTES, 'UTF-8');
    if (!empty($comment)) {
        $sql = "INSERT INTO comments (video_id, user_id, comment) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $video_id, $current_user_id, $comment);
        $stmt->execute();
        $stmt->close();
    }
    echo loadComments($video_id);
    exit();
}

// 5. Отправка ответа на комментарий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'], $_POST['comment_id'])) {
    $comment_id = (int) $_POST['comment_id'];
    $reply = htmlspecialchars($_POST['reply'], ENT_QUOTES, 'UTF-8');
    if (!empty($reply)) {
        $sql = "INSERT INTO comment_replies (comment_id, user_id, reply) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $comment_id, $current_user_id, $reply);
        $stmt->execute();
        $stmt->close();
    }
    echo loadCommentReplies($comment_id);
    exit();
}

// 6. Загрузка комментариев по AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['load_comments'], $_POST['video_id'])) {
    $video_id = (int) $_POST['video_id'];
    echo loadComments($video_id);
    exit();
}

// 7. Загрузка ответов по AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['load_replies'], $_POST['comment_id'])) {
    $comment_id = (int) $_POST['comment_id'];
    echo loadCommentReplies($comment_id);
    exit();
}

function loadComments($video_id) {
    global $conn;
    $sql = "SELECT c.id, c.user_id, c.comment, c.created_at, u.username, u.avatar,
            IFNULL((SELECT SUM(CASE WHEN reaction = 'like' THEN 1 ELSE 0 END) FROM comment_likes WHERE comment_id = c.id),0) AS likes,
            IFNULL((SELECT SUM(CASE WHEN reaction = 'dislike' THEN 1 ELSE 0 END) FROM comment_likes WHERE comment_id = c.id),0) AS dislikes,
            (SELECT COUNT(*) FROM comment_replies WHERE comment_id = c.id) AS replies_count
            FROM comments c 
            JOIN users u ON c.user_id = u.id
            WHERE c.video_id = ?
            ORDER BY c.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $video_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    $stmt->close();
    return json_encode($comments);
}

function loadCommentReplies($comment_id) {
    global $conn;
    $sql = "SELECT r.id, r.reply, r.created_at, u.username, u.avatar, r.user_id 
            FROM comment_replies r 
            JOIN users u ON r.user_id = u.id
            WHERE r.comment_id = ?
            ORDER BY r.created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $replies = [];
    while ($row = $result->fetch_assoc()) {
        $replies[] = $row;
    }
    $stmt->close();
    return json_encode($replies);
}
?>
