<?php
include("db.php");

if (isset($_COOKIE['auth_token'])) {
    $token = $_COOKIE['auth_token'];

    $sql = "UPDATE users SET token = NULL WHERE token = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->close();
    } else {
        die("Ошибка подготовки запроса: " . $conn->error);
    }

    setcookie('auth_token', '', time() - 3600, '/');
}

header('Location: index.php');
exit();
?>
