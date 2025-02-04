<?php
// includes/functions.php

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
