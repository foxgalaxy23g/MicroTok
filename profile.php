<?php
include("elements/php/main/db.php");
include("elements/php/main/verify.php");

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : authenticate($conn);

// Получаем данные пользователя
$sql = "SELECT username, avatar FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();
    $username = $user_data['username'];
    $avatar = $user_data['avatar'] ?: 'default-avatar.jpg';
} else {
    echo "<p>Пользователь не найден.</p>";
    exit;
}

// Получаем все видео пользователя
$sql = "SELECT id, description, cover_image_path, path FROM videos WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$videos = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $videos[] = $row;
    }
}
$stmt->close();

// Получаем просмотры для всех видео пользователя за один запрос
$viewsCounts = [];
if (!empty($videos)) {
    $videoIds = array_column($videos, 'id');
    // Подготовим строку-заполнитель для IN (например, "?, ?, ?")
    $placeholders = implode(',', array_fill(0, count($videoIds), '?'));
    $types = str_repeat('i', count($videoIds));
    
    $stmtViews = $conn->prepare("SELECT video_id, COUNT(*) AS views FROM video_views WHERE video_id IN ($placeholders) GROUP BY video_id");
    // Используем вызов с передачей массива аргументов:
    $stmtViews->bind_param($types, ...$videoIds);
    $stmtViews->execute();
    $resultViews = $stmtViews->get_result();
    while ($row = $resultViews->fetch_assoc()) {
        $viewsCounts[$row['video_id']] = $row['views'];
    }
    $stmtViews->close();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Страница пользователя <?php echo htmlentities($username, ENT_QUOTES, 'UTF-8'); ?> - MicroTok</title>
  <!-- Если у вас есть основной CSS, его можно подключить здесь -->
  <link rel="stylesheet" href="elements/css/feed.css">
  <link rel="stylesheet" href="elements/css/profile.css">
  <script>
    // Автоматическое применение темы согласно системным настройкам
    function applySystemTheme() {
      const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
      document.body.classList.add(isDarkMode ? 'dark' : 'light');
    }
    document.addEventListener('DOMContentLoaded', function() {
      applySystemTheme();
      // Отслеживание изменений системной темы
      if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
          document.body.classList.toggle('dark', e.matches);
          document.body.classList.toggle('light', !e.matches);
        });
      }
    });
  </script>
</head>
<body>
  <?php include("elements/php/blocks/header.php"); ?>

  <div class="profile-page">
    <div class="user-info">
      <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar">
      <p><?php echo htmlentities($username, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    
    <?php if (!empty($videos)): ?>
      <div class="video-list">
        <?php foreach ($videos as $video): ?>
          <a href="feed.php?id=<?php echo $video['id']; ?>" 
             class="video-item" 
             style="background-image: url('<?php echo htmlspecialchars($video['cover_image_path']); ?>');">
            <div class="video-overlay">
              <h2><?php echo htmlentities($video['description']); ?></h2>
              <p style="color: white;"><?php echo isset($viewsCounts[$video['id']]) ? $viewsCounts[$video['id']] : 0; ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p>this user not have any videos.</p>
    <?php endif; ?>
  </div>
</body>
</html>
