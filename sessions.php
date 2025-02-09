<?php
require_once 'elements/php/main/db.php'; 
include("elements/php/main/verify.php");

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

// Функция получения токенов пользователя
function getUserTokens($conn, $user_id) {
    $sql = "SELECT token, ip_address, user_agent, created_at, last_interaction FROM user_tokens WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result;
}

// Функция удаления токена
function deleteToken($conn, $token) {
    $sql = "DELETE FROM user_tokens WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    return $stmt->affected_rows > 0; // Возвращает true, если токен удален
}

// Обработка удаления токена
if (isset($_GET['delete_token'])) {
    $tokenToDelete = $_GET['delete_token'];
    if (deleteToken($conn, $tokenToDelete)) {
        $delete_message = "Токен успешно удален.";
    } else {
        $delete_message = "Ошибка при удалении токена.";
    }
}

// Панель управления токенами
$tokens = getUserTokens($conn, $user_id);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="elements/css/settings.css">
    <link rel="stylesheet" href="elements/css/sessions.css">
</head>
<body>
<noscript>
    <meta http-equiv="refresh" content="0; url=/javascript.php">
  </noscript>
    <?php 
        include("elements/php/blocks/header_old.php"); 
    ?>
    <h2>Manage connected devices</h2>
    <?php if (isset($delete_message)): ?>
        <p><?= htmlspecialchars($delete_message) ?></p>
    <?php endif; ?>

    <?php if ($tokens->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Token</th>
                    <th>IP Address</th>
                    <th>User Agent</th>
                    <th>Created At</th>
                    <th>Last Interaction</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $tokens->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['token']) ?></td>
                        <td><?= htmlspecialchars($row['ip_address']) ?></td>
                        <td><?= htmlspecialchars($row['user_agent']) ?></td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                        <td><?= htmlspecialchars($row['last_interaction']) ?></td>
                        <td>
                            <a href="?delete_token=<?= urlencode($row['token']) ?>">Delete Token</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No active tokens found.</p>
    <?php endif; ?>
</body>
</html>
