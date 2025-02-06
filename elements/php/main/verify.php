<?php
// verify.php
// Запускаем сессию, если ещё не запущена
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Функция для обнаружения подозрительных строк, типичных для XSS
function xss_detected($input) {
    $patterns = [
        '/<script\b[^>]*>(.*?)<\/script>/is',
        '/on\w+\s*=/i',
        '/javascript:/i',
        '/<.*?javascript:.*?>/i',
        '/<.*?\s+on\w+\s*=.*?>/i',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $input)) {
            return true;
        }
    }
    return false;
}

// Функция для проверки входящих данных (GET и POST) на наличие XSS
function scan_request_for_xss() {
    foreach ($_GET as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (xss_detected($item)) {
                    return true;
                }
            }
        } else {
            if (xss_detected($value)) {
                return true;
            }
        }
    }
    foreach ($_POST as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (xss_detected($item)) {
                    return true;
                }
            }
        } else {
            if (xss_detected($value)) {
                return true;
            }
        }
    }
    return false;
}

// Функция для бана пользователя
function banUser($conn, $user_id, $reason = 'XSS attack detected') {
    $stmt = $conn->prepare("INSERT INTO banned_users (user_id, ban_reason, banned_at) VALUES (?, ?, NOW()) 
                            ON DUPLICATE KEY UPDATE ban_reason = VALUES(ban_reason), banned_at = VALUES(banned_at)");
    $stmt->bind_param("is", $user_id, $reason);
    $stmt->execute();
    $stmt->close();
    header('Location: banned.php');
    exit();
}

// Функция для проверки, забанен ли пользователь
function isUserBanned($conn, $user_id) {
    $sql = "SELECT COUNT(*) FROM banned_users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return ($count > 0);
}

// Функция аутентификации пользователя
function authenticate($conn) {
    if (!isset($_COOKIE['auth_token'])) {
        header('Location: index.php');
        exit();
    }
    $token = $_COOKIE['auth_token'];
    $sql = "SELECT ut.user_id, ut.ip_address, ut.user_agent, ut.created_at, ut.last_interaction
            FROM user_tokens ut 
            WHERE ut.token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->bind_result($user_id, $stored_ip, $stored_user_agent, $created_at, $last_interaction);
    $stmt->fetch();
    $stmt->close();
    if (!$user_id) {
        header('Location: index.php');
        exit();
    }
    if (isUserBanned($conn, $user_id)) {
        header('Location: banned.php');
        exit();
    }
    $current_ip = $_SERVER['REMOTE_ADDR'];
    $current_user_agent = $_SERVER['HTTP_USER_AGENT'];
    if ($current_ip !== $stored_ip || $current_user_agent !== $stored_user_agent) {
        header('Location: exit.php');
        exit();
    }
    $update_sql = "UPDATE user_tokens SET last_interaction = NOW() WHERE token = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('s', $token);
    $update_stmt->execute();
    $update_stmt->close();
    return [
        'user_id'           => $user_id,
        'stored_user_agent' => $stored_user_agent,
        'created_at'        => $created_at,
        'last_interaction'  => $last_interaction
    ];
}

// Проверяем входящие данные на XSS
if (scan_request_for_xss()) {
    if (isset($_COOKIE['auth_token'])) {
        $token = $_COOKIE['auth_token'];
        $sql = "SELECT user_id FROM user_tokens WHERE token = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->bind_result($user_id);
        $stmt->fetch();
        $stmt->close();
        if ($user_id) {
            banUser($conn, $user_id, 'XSS attack detected');
        }
    }
    exit('XSS attempt detected.');
}

// Аутентифицируем пользователя и сохраняем его id в сессию
$user_data = authenticate($conn);
$user_id = $user_data['user_id'];
$_SESSION['user_id'] = $user_id;
?>
