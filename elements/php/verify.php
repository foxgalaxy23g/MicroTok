<?php
function authenticate($conn) {
    if (!isset($_COOKIE['auth_token'])) {
        header('Location: index.php');
        exit();
    }

    $token = $_COOKIE['auth_token'];

    // Получаем данные о токене, включая IP, UserAgent, verification_code, created_at
    $sql = "SELECT user_id, verification_code, ip_address, user_agent, created_at, last_interaction
            FROM user_tokens ut 
            JOIN users u ON ut.user_id = u.id
            WHERE ut.token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->bind_result($user_id, $verification_code, $stored_ip, $stored_user_agent, $created_at, $last_interaction);
    $stmt->fetch();
    $stmt->close();

    if (!$user_id) {
        // Если не найден пользователь, перенаправляем на index.php
        header('Location: index.php');
        exit();
    }

    // Если verification_code не пустое, перенаправляем на index.php
    if (!empty($verification_code)) {
        header('Location: index.php');
        exit();
    }

    // Проверяем, совпадает ли IP-адрес и UserAgent
    $current_ip = $_SERVER['REMOTE_ADDR'];
    $current_user_agent = $_SERVER['HTTP_USER_AGENT'];

    if ($current_ip !== $stored_ip || $current_user_agent !== $stored_user_agent) {
        // Если данные не совпадают, перенаправляем на exit.php
        header('Location: exit.php');
        exit();
    }

    // Обновляем время последнего взаимодействия
    $update_sql = "UPDATE user_tokens SET last_interaction = NOW() WHERE token = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('s', $token);
    $update_stmt->execute();
    $update_stmt->close();

    // Возвращаем дополнительные данные
    return [
        'user_id' => $user_id,
        'stored_user_agent' => $stored_user_agent,
        'created_at' => $created_at,
        'last_interaction' => $last_interaction
    ];
}

$user_data = authenticate($conn);
$user_id = $user_data['user_id'];
$stored_user_agent = $user_data['stored_user_agent'];
$created_at = $user_data['created_at'];
$last_interaction = $user_data['last_interaction'];
?>
