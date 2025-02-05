<?php
require_once 'elements/php/db.php'; 
include("elements/php/closed.php");
include("elements/php/verify.php");

// Получаем данные пользователя
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Ошибка: пользователь не найден.");
}

function generateRandomFileName($length = 16) {
    return bin2hex(random_bytes($length));
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверяем, нажата ли кнопка "Обновить"
    if (isset($_POST['update'])) {
        $new_username = trim($_POST['username'] ?? '');
        $new_password = trim($_POST['password'] ?? '');

        if (!empty($new_username) && $new_username !== $user['username']) {
            $update_query = "UPDATE users SET username = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('si', $new_username, $user_id);
            if ($stmt->execute()) {
                echo "Имя пользователя обновлено.<br>";
            } else {
                echo "Ошибка обновления имени.<br>";
            }
            $stmt->close();
        }

        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('si', $hashed_password, $user_id);
            if ($stmt->execute()) {
                echo "Пароль обновлен.<br>";
            } else {
                echo "Ошибка обновления пароля.<br>";
            }
            $stmt->close();
        }
    }

    // Обновление аватара
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK) {
        $avatar_dir = 'uploads/avatars/';
        if (!file_exists($avatar_dir)) {
            mkdir($avatar_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $unique_name = generateRandomFileName() . '.' . $file_extension;
        $avatar_path = $avatar_dir . $unique_name;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar_path)) {
            $update_avatar_query = "UPDATE users SET avatar = ? WHERE id = ?";
            $stmt = $conn->prepare($update_avatar_query);
            $stmt->bind_param('si', $avatar_path, $user_id);
            if ($stmt->execute()) {
                echo "Аватар обновлен.<br>";
            } else {
                echo "Ошибка обновления аватара.<br>";
            }
            $stmt->close();
        } else {
            echo "Ошибка при загрузке файла.<br>";
        }
    }
    if (isset($_POST['account_session'])) {
        header("Location: sessions.php");
    }
    if (isset($_POST['exit_account'])) {
        header("Location: exit.php");
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="elements/css/settings.css">
</head>
<body>
    <?php 
        include("header_old.php"); 
    ?>
    <h1>Settings</h1>
    
    <form method="post" enctype="multipart/form-data">
        <div>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
        </div>

        <div>
            <label for="password">New password:</label>
            <input type="password" id="password" name="password">
        </div>

        <div>
            <label for="avatar">Choose your avatar:</label>
            <input type="file" id="avatar" name="avatar" accept="image/*">
        </div>

        <div>
            <button type="submit" name="update">Update</button>
        </div>
    </form>
    <form>
        <h2>Translation and language</h2>
        <a href="translate-activate.php">translation by Google</a>
    </form>

    <form method="post">
        <h2>Actions with my account</h2>
        <div style="display: flex;">
            <button type="submit" name="delete_account">Delete your account</button>
        </div>
        <div>
            <button type="submit" name="account_session">Sessions</button>
            <button type="submit" name="exit_account">Exit from my account</button>
        </div>
    </form>
    <form method="post">
        <h2>Our contacts</h2>
        <div style="display: flex;">
            <div>
                <a href="<?php echo($x_admin) ?>"><img class="hawaii" src="elements/embeded/notme/x.png" alt="X"></a>
                <p style="text-align: center;">X</p>
            </div>
            <div>
                <a href="<?php echo($tg_admin) ?>"><img  class="hawaii"src="elements/embeded/notme/tg.png" alt="tg"></a>
                <p style="text-align: center;">Telegram</p>
            </div>
            <div>
                <a href="<?php echo($yt_admin) ?>"><img  class="hawaii"src="elements/embeded/notme/yt.png" alt="yt"></a>
                <p style="text-align: center;">YouTube</p>
            </div>
            <div>
                <a href="<?php echo($gh_admin) ?>"><img  class="hawaii"src="elements/embeded/notme/gh.png" alt="gh"></a>
                <p style="text-align: center;">GitHub</p>
            </div>
        </div>
    </form>
    <h2>_</h2>
</body>
</html>
