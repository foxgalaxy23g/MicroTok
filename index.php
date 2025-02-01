<?php
include("db.php");

$project_decsi = "Social media maded by no name furry just for fun";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_username'], $_POST['register_password'])) {
    $username = $_POST['register_username'];
    $password = password_hash($_POST['register_password'], PASSWORD_BCRYPT);

    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ss', $username, $password);

        if ($stmt->execute()) {
            $register_success = "Регистрация успешна. Теперь вы можете войти.";
        } else {
            $register_error = "this username is aleady used";
        }
        $stmt->close();
    } else {
        die("Error: " . $conn->error);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_username'], $_POST['login_password'])) {
    $username = $_POST['login_username'];
    $password = $_POST['login_password'];

    $sql = "SELECT password, token FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->bind_result($hashedPassword, $token);
        $stmt->fetch();
        $stmt->close();

        if ($hashedPassword && password_verify($password, $hashedPassword)) {
            if (!$token) {
                $token = bin2hex(random_bytes(16));
                $updateTokenSql = "UPDATE users SET token = ? WHERE username = ?";
                $updateStmt = $conn->prepare($updateTokenSql);

                if ($updateStmt) {
                    $updateStmt->bind_param('ss', $token, $username);
                    $updateStmt->execute();
                    $updateStmt->close();
                } else {
                    die("Error token: " . $conn->error);
                }
            }

            setcookie('auth_token', $token, time() + 86400 * 30, '/');
            header('Location: feed.php');
            exit();
        } else {
            $login_error = "Incorrect password or username!";
        }
    } else {
        die("Error: " . $conn->error);
    }
}

if (isset($_COOKIE['auth_token'])) {
    $token = $_COOKIE['auth_token'];

    $sql = "SELECT id FROM users WHERE token = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->bind_result($userId);
        $stmt->fetch();
        $stmt->close();

        if ($userId) {
            header('Location: feed.php');
            exit();
        }
    } else {
        die("Error: " . $conn->error);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to <?php echo htmlentities($project_name); ?></title>
    <style>

    </style>
    <link rel="stylesheet" href="elements/css/index.css">
    <link rel="icon" href="elements/embeded/logo.png" type="image/x-icon"/>
    <meta name="description" content="<?php echo($project_decsi); ?>">
</head>
<body>
    <noscript>
      <meta http-equiv="refresh" content="0; url=/javascript.html">
    </noscript>
    <div class="container">
        <div class="left-panel">
            <div class="form-container">
                <h1>Register</h1>
                <?php if (isset($register_success)) { echo "<p style='color: green;'>" . htmlspecialchars($register_success) . "</p>"; } ?>
                <?php if (isset($register_error)) { echo "<p style='color: red;'>" . htmlspecialchars($register_error) . "</p>"; } ?>
                <form method="post">
                    <label for="register_username">Username:</label>
                    <input type="text" id="register_username" name="register_username" required>

                    <label for="register_password">Password:</label>
                    <input type="password" id="register_password" name="register_password" required>

                    <button type="submit">Register</button>
                </form>
            </div>

            <div class="form-container">
                <h1>Login</h1>
                <?php if (isset($login_error)) { echo "<p style='color: red;'>" . htmlspecialchars($login_error) . "</p>"; } ?>
                <form method="post">
                    <label for="login_username">Username:</label>
                    <input type="text" id="login_username" name="login_username" required>

                    <label for="login_password">Password:</label>
                    <input type="password" id="login_password" name="login_password" required>

                    <button type="submit">Login</button>
                </form>
            </div>
        </div>

        <div class="right-panel">
            <img src="elements/embeded/ad.png" alt="Placeholder Image">
            <h2>Welcome to <?php echo htmlentities($project_name); ?>!</h2>
            <p><?php echo htmlentities($project_decsi); ?></p>
        </div>
    </div>
    <script src="elements/js/safe.js"></script>
</body>
</html>
