<?php
  include("elements/php/main/db.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JavaScript not working!</title>
    <link rel="stylesheet" href="elements/css/javascript.css">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="elements/embeded/me/logo.png" type="image/x-icon"/>
</head>
<body>
    <h1>Warning!</h1>
    <a>Please turn on JavaScript on your Web Browser!</a>
    <a><?php echo($project_name); ?> can't working without JavaScript</a>
    <script>
        try {
          window.location.href = '/index.php';
        } catch (e) {
          console.error('error', e);
        }
    </script>
</body>
</html>
<?php
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

  exit();
?>