<?php
include("elements/php/translator.php");
include("elements/php/db.php");
include("elements/php/cursor.php");
include("elements/php/closed.php");

$project_decsi = "Social media maded by no name furry just for fun";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_username'], $_POST['register_password'])) {
    $username = $_POST['register_username'];
    $password = password_hash($_POST['register_password'], PASSWORD_BCRYPT);

    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ss', $username, $password);

        if ($stmt->execute()) {
            // Генерация токена и автоматический вход
            $token = bin2hex(random_bytes(16));
            $updateTokenSql = "UPDATE users SET token = ? WHERE username = ?";
            $updateStmt = $conn->prepare($updateTokenSql);

            if ($updateStmt) {
                $updateStmt->bind_param('ss', $token, $username);
                $updateStmt->execute();
                $updateStmt->close();

                // Установка токена в cookies
                setcookie('auth_token', $token, time() + 86400 * 30, '/');
                header('Location: feed.php');
                exit();
            } else {
                die("Error token: " . $conn->error);
            }
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
    <link rel="stylesheet" href="elements/css/index.css">
    <link rel="icon" href="elements/embeded/me/logo.png" type="image/x-icon"/>
    <meta name="description" content="<?php echo($project_decsi); ?>">
</head>
<body>
    <noscript>
      <meta http-equiv="refresh" content="0; url=/javascript.html">
    </noscript>

    <!-- Экран загрузки -->
    <div id="loading-screen" style="background-color: #333;">
        <div id="logo-container" style="margin-bottom: 30%;">
            <img id="logo" style="margin-bottom: -40vh;" src="elements/embeded/me/logo.png" alt="Logo">
        </div>
        <p id="by-text" style="color: rgb(255, 255, 255); font-weight: bold; font-size: 1.5em;">By <?php echo($company_developer); ?></p>
    </div>

    <!-- Основной контент страницы (будет скрыт до окончания загрузки) -->
    <div id="main-content" style="display:none;">
        <div class="container">
            <div class="left-panel">
                <?php
                if($registration_open == "1"){
                    echo('<div class="form-container">');
                    echo('    <h1 class="fade-in">Register</h1>');
                    ?>
                    <?php if (isset($register_success)): ?>
                        <p style="color: green;"><?php echo htmlspecialchars($register_success); ?></p>
                    <?php endif; ?>
                    <?php if (isset($register_error)): ?>
                        <p style="color: red;"><?php echo htmlspecialchars($register_error); ?></p>
                    <?php endif; ?>
                    <?php
                    echo('    <form class="fade-in" method="post">');
                    echo('        <label for="register_username">Username:</label>');
                    echo('        <input type="text" id="register_username" name="register_username" required>');
                    echo('        <label for="register_password">Password:</label>');
                    echo('        <input type="password" id="register_password" name="register_password" required>');
                    echo('        <button type="submit">Register</button>');
                    echo('    </form>');
                    echo('</div>');
                }
                ?>

                <div class="form-container">
                    <h1>Login</h1>
                    <?php if (isset($login_error)) { echo "<p style='color: red;'>" . htmlspecialchars($login_error) . "</p>"; } ?>
                    <form method="post">
                        <label for="login_username" class="fade-in">Username:</label>
                        <input type="text" id="login_username" name="login_username" class="fade-in" required>

                        <label for="login_password" class="fade-in">Password:</label>
                        <input type="password" id="login_password" name="login_password" class="fade-in" required>

                        <button type="submit" class="fade-in">Login</button>
                    </form>
                </div>
                <a href="translate-activate.php" class="fade-in" style="color: rgb(98, 0, 255); text-decoration: none;">Activate translation by Google</a>
            </div>

            <div class="right-panel">
                <img class="fade-in" src="elements/embeded/me/ad.png" alt="Placeholder Image">
                <h2 class="fade-in">Welcome to <?php echo htmlentities($project_name); ?>!</h2>
                <p class="fade-in"><?php echo htmlentities($project_decsi); ?></p>
            </div>
        </div>
        
    </div>

    <script>
        window.addEventListener('load', function () {
            // Задержка 2 секунды перед началом анимации
            setTimeout(function () {
                const logo = document.getElementById('logo');
                const loadingScreen = document.getElementById('loading-screen');
                const mainContent = document.getElementById('main-content');
            
                // Остановка анимации пульсации логотипа
                logo.style.animation = 'none';
                logo.style.animation = 'fadeAndGrow 2s forwards';
            
                // После завершения анимации скрываем экран загрузки и показываем основной контент с анимацией
                setTimeout(function () {
                    loadingScreen.style.display = 'none';
                    mainContent.style.display = 'block'; // Показываем основной контент
                    mainContent.classList.add('show'); // Добавляем класс для плавного появления
                    document.querySelectorAll('.fade-in').forEach(el => {
                      el.classList.add('visible');
                    });
                }, 2000); // Длительность анимации логотипа (2 секунды)
            }, 2000); // Задержка перед началом анимации (2 секунды)
        });
    </script>
</body>
</html>
