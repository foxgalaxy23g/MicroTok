<?php
include("elements/php/main/db.php");
include("elements/php/main/verify.php");

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : authenticate($conn); // Проверка, задан ли user_id в URL

// Получаем данные пользователя
$sql = "SELECT username, avatar FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();
    $username = $user_data['username'];
    $avatar = $user_data['avatar'] ?: 'default-avatar.jpg';  // Если аватар не найден, используем стандартный
} else {
    // Пользователь не найден
    echo "<p>Пользователь не найден.</p>";
    exit;
}

// Обработка поиска
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Получаем видео по запросу или по умолчанию
if ($search_query) {
    $sql = "SELECT id, description, cover_image_path, path FROM videos WHERE user_id = ? AND description LIKE ?";
    $search_query = "%" . $search_query . "%";  // Подготовка строки поиска
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $user_id, $search_query);
} else {
    $sql = "SELECT id, description, cover_image_path, path FROM videos WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $videos = [];
    while ($row = $result->fetch_assoc()) {
        $videos[] = $row;
    }
} else {
    $videos = [];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница пользователя <?php echo htmlentities($username, ENT_QUOTES, 'UTF-8'); ?> - MicroTok</title>
    <link rel="stylesheet" href="elements/css/feed.css">
    <link rel="stylesheet" href="elements/css/search.css">
</head>
<?php include("elements/php/blocks/header.php"); ?>

    <div class="profile-page">
        
        <?php if (!empty($videos)): ?>
            <div class="video-list">
                <?php foreach ($videos as $video): ?>
                    <div class="video-item">
                        <a href="feed.php?id=<?php echo $video['id']; ?>">
                            <img src="<?php echo htmlspecialchars($video['cover_image_path']); ?>" alt="Video Cover">
                            <h2><?php echo htmlentities($video['description']); ?></h2>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>У этого пользователя нет опубликованных видео.</p>
        <?php endif; ?>
    </div>

    <script>
        // Переключение темы
        function toggleTheme() {
            const body = document.body;
            if (body.classList.contains('light')) {
                body.classList.remove('light');
                body.classList.add('dark');
            } else {
                body.classList.remove('dark');
                body.classList.add('light');
            }
        }
    </script>

</body>
</html>
