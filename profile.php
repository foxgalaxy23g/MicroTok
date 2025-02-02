<?php
include("elements/php/db.php");
include("elements/php/closed.php");

include("elements/php/verify.php");

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

// Получаем все видео пользователя
$sql = "SELECT id, description, cover_image_path, path FROM videos WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
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
    <style>
        /* Общие стили */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
            color: #333;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .header {
            background-color: #fff;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .header .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }

        .header .theme-switcher {
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
        }

        .theme-switcher svg {
            width: 24px;
            height: 24px;
            fill: currentColor;
        }

        /* Стиль профиля */
        .profile-page {
            padding: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .user-info img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .user-info p {
            font-size: 1.2rem;
            margin: 0;
        }

        /* Сетка для видео */
        .video-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            justify-items: center;
            margin-top: 20px;
        }

        .video-item {
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 320px;
            height: 0;
            padding-bottom: 177.77%; /* Соотношение 9:16 */
            position: relative;
        }

        .video-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }

        .video-item h2 {
            font-size: 1rem;
            margin: 10px;
            padding: 0;
        }

        .video-item a {
            display: block;
            padding: 10px;
            text-align: center;
            color: #007BFF;
            text-decoration: none;
            border-top: 1px solid #ddd;
        }

        /* Тема */
        body.light {
            background-color: #f0f0f0;
            color: #333;
        }

        body.dark {
            background-color: #121212;
            color: #fff;
        }

        /* Переключатель темы */
        .theme-switcher svg {
            color: #000;
        }

        .dark .theme-switcher svg {
            color: #fff;
        }

    </style>
</head>
<?php include("header.php"); ?>

    <div class="profile-page">
        <h1>Страница пользователя <?php echo htmlentities($username, ENT_QUOTES, 'UTF-8'); ?></h1>
        <div class="user-info">
            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar">
            <p>Пользователь: <?php echo htmlentities($username, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        
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
