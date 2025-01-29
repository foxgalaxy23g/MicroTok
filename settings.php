<?php
require_once 'db.php'; 

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

$user_id = authenticate($conn);

$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);  
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

function generateRandomFileName($length = 16) {
    return bin2hex(random_bytes($length));  
}

if (!empty($new_username)) {
    $update_query = "UPDATE users SET username = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('si', $new_username, $user_id); 
    $stmt->execute();  
    $stmt->close();
}

if (!empty($new_password)) {
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $update_query = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('si', $hashed_password, $user_id); 
    $stmt->execute();  
    $stmt->close();
}

// Обновление аватара
if ($_FILES['avatar']['error'] == 0) {
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
        $stmt->execute();  
        $stmt->close();
    } else {
        echo "Ошибка при загрузке файла.";
    }
}

    if (isset($_POST['delete_account'])) {
        $delete_query = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->execute([$user_id]);
        
        header("Location: exit.php");
        exit();
    }


?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки аккаунта</title>
</head>
<body>
    <h1>Настройки аккаунта</h1>
    
    <form method="post" enctype="multipart/form-data">
        <div>
            <label for="username">Имя пользователя:</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
        </div>

        <div>
            <label for="password">Новый пароль:</label>
            <input type="password" id="password" name="password">
        </div>

        <div>
            <label for="avatar">Выберите аватар:</label>
            <input type="file" id="avatar" name="avatar" accept="image/*">
        </div>

        <div>
            <button type="submit" name="update">Обновить</button>
        </div>
    </form>

    <form method="post">
        <div>
            <button type="submit" name="delete_account">Удалить аккаунт</button>
        </div>
    </form>

    <h2>Ваш аватар</h2>
    <?php if (!empty($user['avatar'])): ?>
        <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Аватар" style="max-width: 150px;">
    <?php else: ?>
        <p>Аватар не установлен.</p>
    <?php endif; ?>

</body>
</html>
