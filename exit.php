<?php
include("elements/php/main/db.php");

// Начинаем сессию
session_start();

if (isset($_GET['id'])) {
    if (isset($_COOKIE['auth_token'])) {
        $token = $_COOKIE['auth_token'];
    
        // Удаление токена из таблицы user_tokens
        $deleteTokenSql = "DELETE FROM user_tokens WHERE token = ?";
        $deleteStmt = $conn->prepare($deleteTokenSql);
    
        if ($deleteStmt) {
            $deleteStmt->bind_param('s', $token);
            if ($deleteStmt->execute()) {
                // Токен успешно удален из базы данных
                $deleteStmt->close();
            } else {
                die("Ошибка выполнения запроса для удаления токена: " . $deleteStmt->error);
            }
        } else {
            die("Ошибка подготовки запроса: " . $conn->error);
        }
    }
    setcookie('auth_token', '', time() - 3600, '/', '', false, true);  // Устанавливаем срок действия куки в прошлом
    // Очистка всех переменных сессии
    session_unset();
    session_write_close();
    
    // Удаляем все куки
    foreach ($_COOKIE as $cookie_name => $cookie_value) {
        setcookie($cookie_name, '', time() - 3600, '/', '', false, true);  // Устанавливаем срок действия в прошлом
    }
    
    // Удаляем сессионную куку PHPSESSID
    setcookie(session_name(), '', time() - 3600, '/', '', false, true);
    
    // Перенаправление на главную страницу или нужную
    header('Location: index.php');
    exit();
} else {
    if (isset($_POST['exit_account']) && isset($_COOKIE['auth_token'])) {
        $token = $_COOKIE['auth_token'];
    
        // Удаление токена из таблицы user_tokens
        $deleteTokenSql = "DELETE FROM user_tokens WHERE token = ?";
        $deleteStmt = $conn->prepare($deleteTokenSql);
    
        if ($deleteStmt) {
            $deleteStmt->bind_param('s', $token);
            if ($deleteStmt->execute()) {
                // Токен успешно удален из базы данных
                $deleteStmt->close();
            } else {
                die("Ошибка выполнения запроса для удаления токена: " . $deleteStmt->error);
            }
        } else {
            die("Ошибка подготовки запроса: " . $conn->error);
        }
    
        // Удаляем куку auth_token
        setcookie('auth_token', '', time() - 3600, '/', '', false, true);  // Устанавливаем срок действия куки в прошлом
        // Очистка всех переменных сессии
        session_unset();
        session_write_close();
        
        // Удаляем все куки
        foreach ($_COOKIE as $cookie_name => $cookie_value) {
            setcookie($cookie_name, '', time() - 3600, '/', '', false, true);  // Устанавливаем срок действия в прошлом
        }
        
        // Удаляем сессионную куку PHPSESSID
        setcookie(session_name(), '', time() - 3600, '/', '', false, true);
        
        // Перенаправление на главную страницу или нужную
        header('Location: index.php');
        exit();
    }
}

?>

<?php 
include("elements/php/main/translator.php"); 
include("elements/php/main/cursor.php");
include("elements/php/main/db.php");

if (!isset($_COOKIE['auth_token'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout Confirmation</title>
    <link rel="stylesheet" href="elements/css/warning.css">
    <style>
    </style>
    <link rel="icon" href="elements/embeded/logo.png" type="image/x-icon"/>
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
    <noscript>
      <meta http-equiv="refresh" content="0; url=/javascript.html">
    </noscript>
    
    <div class="logout-container">
        <h2>Are you sure you want to log out?</h2>
        <div class="button-container">
            <!-- Форма для отправки POST-запроса для выхода -->
            <form method="POST" action="exit.php">
                <button type="submit" name="exit_account" class="exit_account">Log Out</button>
            </form>
            <button class="cancel-btn" onclick="window.location.href='feed.php'">Cancel</button>
        </div>
    </div>
    <script src="elements/js/safe.js"></script>
</body>
</html>
