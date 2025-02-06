<?php
include("elements/php/translator.php");
include("elements/php/db.php");
include("elements/php/cursor.php");
include("elements/php/closed.php");

$project_decsi = "Social media made by no name furry just for fun";

// Функция для отправки кода на email через Python скрипт
function sendVerificationCode($email, $code, $ip_address, $user_agent, $project_name) {
    // Указываем полный путь к Python и скрипту
    $pythonPath = "C:\\Users\\MihaB\\AppData\\Local\\Programs\\Python\\Python313\\python.exe"; // Замените на путь к вашему Python
    $scriptPath = "C:\\OSPanel\\domains\\MicroTok\\elements\\python\\send_mail.py"; // Замените на путь к вашему скрипту

    // Формируем команду
    $command = escapeshellcmd("$pythonPath $scriptPath $email $code $ip_address $user_agent $project_name");


    // Логирование для отладки
    error_log("Executing command: $command");

    // Выполнение команды
    $output = shell_exec($command);

    // Логирование вывода
    error_log("Output: $output");
}


session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_username'], $_POST['register_password'], $_POST['register_email']) && !isset($_SESSION['verification_sent'])) {
    $username = trim($_POST['register_username']);
    $rawPassword = $_POST['register_password'];
    $email = trim($_POST['register_email']);

    // --- Валидация данных ---
    // Проверка длины username (максимум 13 символов)
    if (strlen($username) > 13) {
        $register_error = "Username не должен превышать 13 символов.";
    }
    // Проверка на допустимые символы (только латинские буквы и цифры)
    elseif (!preg_match("/^[a-zA-Z0-9]+$/", $username)) {
        $register_error = "The username can only contain Latin letters and numbers.";
    }
    // Проверка длины пароля (минимум 8 символов)
    elseif (strlen($rawPassword) < 8) {
        $register_error = "The password must be at least 8 characters.";
    }
    // Проверка на существование email в базе данных
    else {
        $emailCheckSql = "SELECT id FROM users WHERE email = ?";
        $emailStmt = $conn->prepare($emailCheckSql);
        $emailStmt->bind_param('s', $email);
        $emailStmt->execute();
        $emailStmt->store_result();
        if ($emailStmt->num_rows > 0) {
            $register_error = "The specified email is already in use.";
        }
        $emailStmt->close();
    }

    // Если ошибки не обнаружены, продолжаем регистрацию
    if (!isset($register_error)) {
        $password = password_hash($rawPassword, PASSWORD_BCRYPT);
        // Генерация случайного кода для верификации
        $verificationCode = rand(100000, 999999);

        // Вставка пользователя в таблицу users (без верификации)
        $sql = "INSERT INTO users (username, password, email) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('sss', $username, $password, $email);
            
            if ($stmt->execute()) {
                // Получение IP и UserAgent
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];

                // Сохранение информации о токене в таблице user_tokens
                $user_id = $conn->insert_id;
                $token = bin2hex(random_bytes(16));
                $sqlToken = "INSERT INTO user_tokens (user_id, token, verification_code, ip_address, user_agent) 
                             VALUES (?, ?, ?, ?, ?)";
                $stmtToken = $conn->prepare($sqlToken);
                $stmtToken->bind_param('sssss', $user_id, $token, $verificationCode, $ip_address, $user_agent);
                $stmtToken->execute();
                $stmtToken->close();

                // Отправка кода на email с помощью Python скрипта
                sendVerificationCode($email, $verificationCode, $ip_address, $user_agent, $project_name);

                // Переход к форме для ввода кода
                $_SESSION['username'] = $username;
                $_SESSION['verification_sent'] = true;
                header('Location: index.php');
                exit();
            } else {
                $register_error = "This username is already used.";
            }
            $stmt->close();
        } else {
            die("Error: " . $conn->error);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_username'], $_POST['login_password']) && !isset($_SESSION['verification_sent'])) {
    $username = $_POST['login_username'];
    $password = $_POST['login_password'];

    // Получаем пользователя по имени
    $sql = "SELECT id, password, email FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->bind_result($user_id, $hashedPassword, $email);
        $stmt->fetch();
        $stmt->close();

        if ($hashedPassword && password_verify($password, $hashedPassword)) {
            // Генерация нового кода для верификации
            $verificationCode = rand(100000, 999999);

            // Получаем IP и UserAgent
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];

            // Генерация нового токена
            $token = bin2hex(random_bytes(16)); // Генерация нового токена

            // Записываем токен в таблицу user_tokens
            $sqlToken = "INSERT INTO user_tokens (user_id, token, verification_code, ip_address, user_agent) 
                         VALUES (?, ?, ?, ?, ?)";
            $stmtToken = $conn->prepare($sqlToken);
            $stmtToken->bind_param('sssss', $user_id, $token, $verificationCode, $ip_address, $user_agent);
            $stmtToken->execute();
            $stmtToken->close();

            // Отправка кода на email с помощью Python скрипта
            sendVerificationCode($email, $verificationCode, $ip_address, $user_agent, $project_name);

            // Переход к форме для ввода кода
            $_SESSION['username'] = $username;
            $_SESSION['verification_sent'] = true;
            header('Location: index.php');
            exit();
        } else {
            $login_error = "Incorrect username or password.";
        }
    } else {
        error_log("Executing SQL: " . $sqlToken);
        die("Error: " . $conn->error);
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['verification_code'])) {
    $verificationCode = trim($_POST['verification_code']);
    $username = $_SESSION['username'];

    // Получаем id пользователя для обновлений
    $userIdSql = "SELECT id FROM users WHERE username = ?";
    if ($userIdStmt = $conn->prepare($userIdSql)) {
        $userIdStmt->bind_param('s', $username);
        $userIdStmt->execute();
        $userIdStmt->bind_result($userId);
        $userIdStmt->fetch();
        $userIdStmt->close();

        // Если пользователь найден
        if ($userId) {
            // Получаем verification_code и token
            $sql = "SELECT verification_code, token FROM user_tokens WHERE user_id = ? AND verification_code IS NOT NULL ORDER BY created_at DESC LIMIT 1";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param('i', $userId); // Используем id пользователя
                $stmt->execute();
                $stmt->bind_result($storedCode, $storedToken);
                $stmt->fetch();
                $stmt->close();

                if ($storedCode !== null && $verificationCode === $storedCode) {
                    // Если токен не существует, генерируем новый
                    if (!$storedToken) {
                        $token = bin2hex(random_bytes(16));

                        $updateSql = "UPDATE user_tokens SET token = ? WHERE user_id = ?";
                        if ($updateStmt = $conn->prepare($updateSql)) {
                            $updateStmt->bind_param('si', $token, $userId); // Используем id пользователя
                            $updateStmt->execute();
                            $updateStmt->close();
                        } else {
                            error_log("Ошибка при обновлении токена: " . $conn->error);
                        }
                    } else {
                        $token = $storedToken;
                    }

                    // Очистим verification_code после успешной аутентификации
                    $clearCodeSql = "UPDATE user_tokens SET verification_code = NULL WHERE user_id = ?";
                    if ($clearCodeStmt = $conn->prepare($clearCodeSql)) {
                        $clearCodeStmt->bind_param('i', $userId); // Используем id пользователя
                        $clearCodeStmt->execute();
                        $clearCodeStmt->close();
                    } else {
                        error_log("Ошибка при очистке verification_code: " . $conn->error);
                    }

                    // Устанавливаем куки
                    setcookie('auth_token', $token, time() + 86400 * 30, '/');
                    header('Location: feed.php');
                    exit();
                } else {
                    $verification_error = "Неверный код подтверждения.";
                }
            } else {
                error_log("Ошибка при подготовке запроса для получения кода и токена: " . $conn->error);
            }
        } else {
            error_log("Пользователь не найден.");
        }
    } else {
        error_log("Ошибка при получении id пользователя: " . $conn->error);
    }
} else {
    error_log("Код подтверждения не был отправлен.");
}

if (isset($_COOKIE['auth_token'])) {
    $token = $_COOKIE['auth_token'];

    // Запрос к таблице user_tokens
    $sql = "SELECT user_id FROM user_tokens WHERE token = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('s', $token); // Привязываем значение токена
        $stmt->execute();
        $stmt->bind_result($user_id); // Привязываем результат в переменную
        $stmt->fetch(); // Получаем результат
        $stmt->close();

        if ($user_id) {
            // Если токен найден, перенаправляем на feed.php
            header('Location: feed.php');
            exit();
        } else {
            // Токен не найден
            echo "Token not valid.";
        }
    } else {
        // Ошибка в подготовке запроса
        error_log("Executing SQL: " . $sqlToken);
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
    <style>
        /* Стили для контейнера поля пароля с полоской */
        .password-container {
            position: relative;
        }
        /* Поле ввода пароля растягивается на 100% */
        .password-container input {
            width: 100%;
            box-sizing: border-box;
            padding-right: 50px; /* место для полоски */
        }
        /* Полоска надёжности пароля */
        .password-strength {
            position: absolute;
            right: 5px;
            top: 37%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            border-radius: 15px;
            background-color: #ccc;
        }
    </style>
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

    <div id="main-content" style="display:none;">
        <div class="container">
            <div class="left-panel">
                <?php
                    // Форма регистрации показывается, если регистрация открыта и верификация не отправлена
                    if ($registration_open == "1") {
                        if (!isset($_SESSION['verification_sent'])) {
                            echo('<div class="form-container">');
                            echo('    <h1 class="fade-in">Register</h1>');
                            if (isset($register_success)):
                                echo('<p style="color: green;">'.htmlspecialchars($register_success).'</p>');
                            endif;
                            if (isset($register_error)):
                                echo('<p style="color: red;">'.htmlspecialchars($register_error).'</p>');
                            endif;
                            echo('    <form class="fade-in" method="post">');
                            echo('        <label for="register_username">Username:</label>');
                            echo('        <input type="text" id="register_username" name="register_username" required>');
                            echo('        <label for="register_email">Email:</label>');
                            echo('        <input type="email" id="register_email" name="register_email" required>');
                            echo('        <label for="register_password">Password:</label>');
                            echo('        <div class="password-container">');
                            echo('            <input type="password" id="register_password" name="register_password" required oninput="checkPasswordStrength(this.value)">');
                            echo('            <div id="password-strength" class="password-strength"></div>');
                            echo('        </div>');
                            echo('        <button type="submit">Register</button>');
                            echo('    </form>');
                            echo('</div>');
                        }
                    }
                ?>

                <!-- Форма входа -->
                <?php if (!isset($_SESSION['verification_sent'])): ?>
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
                <?php endif; ?>

                <!-- Форма верификации -->
                <?php if (isset($_SESSION['verification_sent']) && !isset($_COOKIE['auth_token'])): ?>
                    <div class="form-container">
                        <h1>Verify Your Account</h1>
                        <?php if (isset($verification_error)): ?>
                            <p style="color: red;"><?php echo htmlspecialchars($verification_error); ?></p>
                        <?php endif; ?>
                        <form method="post">
                            <label for="verification_code">Enter Verification Code:</label>
                            <input type="text" name="verification_code" required>
                            <button type="submit">Verify</button>
                        </form>
                    </div>
                <?php endif; ?>
                <div style="display: flex;">
                    <?php if (isset($_SESSION['verification_sent']) && !isset($_COOKIE['auth_token'])): ?>
                        <a href="exit.php?id=1" style="color: rgb(98, 0, 255); text-decoration: none; margin-right: 5%;">Exit from this account</a>
                    <?php endif; ?>
                    <a href="translate-activate.php" class="fade-in" style="color: rgb(98, 0, 255); text-decoration: none;">Activate translation by Google</a>
                </div>
            </div>

            <div class="right-panel">
                <img class="fade-in" src="elements/embeded/me/ad.png" alt="Placeholder Image">
                <h2 class="fade-in">Welcome to <?php echo htmlentities($project_name); ?>!</h2>
                <p class="fade-in"><?php echo htmlentities($project_decsi); ?></p>
            </div>
        </div>
    </div>

    <script>
        function checkPasswordStrength(password) {
            var strengthBar = document.getElementById('password-strength');
            var strength = 0;

            // Примерный расчёт: увеличиваем силу за длину и разнообразие символов
            if (password.length >= 8) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[a-z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;

            // Устанавливаем цвет полоски в зависимости от силы пароля
            if (strength <= 2) {
                strengthBar.style.backgroundColor = 'red';
            } else if (strength === 3 || strength === 4) {
                strengthBar.style.backgroundColor = 'yellow';
            } else if (strength >= 5) {
                strengthBar.style.backgroundColor = 'green';
            }
        }

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