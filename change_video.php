<?php
include("elements/php/db.php");

$current_video_id = $_POST['current_video_id'];

// Получаем следующее видео
$sql = "
    SELECT v.id FROM videos v 
    LEFT JOIN video_likes vl ON v.id = vl.video_id
    WHERE v.id != ? 
    GROUP BY v.id 
    ORDER BY COUNT(vl.id) DESC 
    LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_video_id);
$stmt->execute();
$stmt->bind_result($next_video_id);
$stmt->fetch();
$stmt->close();

echo $next_video_id ? $next_video_id : $current_video_id;
?>
