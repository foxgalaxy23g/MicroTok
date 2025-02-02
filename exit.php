<?php
include("elements/php/db.php");

// Начинаем сессию
session_start();

// Проверяем, если существует кука с токеном
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

    // Удаляем куку auth_token
    setcookie('auth_token', '', time() - 3600, '/', '', false, true);  // Устанавливаем срок действия куки в прошлом
}

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
?>
