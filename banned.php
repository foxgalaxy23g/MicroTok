<?php
include("elements/php/main/db.php");

// Check if the user is banned
if (isset($_COOKIE['auth_token'])) {
    $token = $_COOKIE['auth_token'];
    $sql = "SELECT b.ban_reason FROM banned_users b 
            JOIN user_tokens ut ON b.user_id = ut.user_id 
            WHERE ut.token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->bind_result($ban_reason);
    $stmt->fetch();
    $stmt->close();
} else {
    header('Location: index.php');
    exit();
}

// Получаем данные токена
$sql = "SELECT ut.user_id, ut.ip_address, ut.user_agent, ut.created_at, ut.last_interaction
        FROM user_tokens ut 
        WHERE ut.token = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $token);
$stmt->execute();
$stmt->bind_result($user_id, $stored_ip, $stored_user_agent, $created_at, $last_interaction);
$stmt->fetch();
$stmt->close();

if (empty($ban_reason)) {
    header('Location: feed.php');
    exit();
}

// Удаляем токен из таблицы user_tokens
$deleteTokenSql = "DELETE FROM user_tokens WHERE token = ?";
$deleteStmt = $conn->prepare($deleteTokenSql);
if ($deleteStmt) {
    $deleteStmt->bind_param('s', $token);
    if (!$deleteStmt->execute()) {
        die("Ошибка выполнения запроса для удаления токена: " . $deleteStmt->error);
    }
    $deleteStmt->close();
} else {
    die("Ошибка подготовки запроса: " . $conn->error);
}

setcookie('auth_token', '', time() - 3600, '/', '', false, true);  // Удаляем куку

// Очистка сессии
session_unset();
session_write_close();

// Удаляем все куки
foreach ($_COOKIE as $cookie_name => $cookie_value) {
    setcookie($cookie_name, '', time() - 3600, '/', '', false, true);
}
setcookie(session_name(), '', time() - 3600, '/', '', false, true);

// После всех операций выводим HTML с сообщением
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <link rel="stylesheet" href="elements/css/banned.css">
</head>
<body>
    <noscript>
        <meta http-equiv="refresh" content="0; url=/javascript.php">
    </noscript>
    <div class="container">
        <h1>You Are Banned</h1>
        <p>Your access to the site has been restricted for the following reason:</p>
        <div class="reason">
            <?php echo htmlspecialchars($ban_reason); ?>
        </div>
    </div>
</body>
</html>
