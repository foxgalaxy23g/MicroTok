<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Шапка сайта</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* Стили для поисковой строки */
    .search-container {
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 9px;
      margin-left: 20px;
    }
    .search-container input {
      padding: 8px;
      font-size: 1rem;
      border-radius: 12px;
      border: 1px solid rgb(98, 0, 255);
      width: 250px;
    }
    .search-container button {
      background-color: rgb(98, 0, 255);
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 10px;
      cursor: pointer;
      margin-left: 5px;
    }
    .search-container button:hover {
      background-color: rgb(98, 0, 255);
    }
    /* Стили для выпадающих меню */
    .dropdown-menu {
      position: absolute;
      background-color: #fff;
      border: 1px solid #ccc;
      box-shadow: 0px 2px 6px rgba(0,0,0,0.15);
      border-radius: 4px;
      z-index: 1000;
      padding: 10px;
      margin-top: 5px;
    }
    .dropdown-menu ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .dropdown-menu ul li {
      margin: 5px 0;
    }
    .dropdown-menu ul li a {
      text-decoration: none;
      color: #333;
    }
    .notifications-container,
    .user-menu-container {
      position: relative;
      display: inline-block;
      margin-left: 15px;
    }
    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      cursor: pointer;
    }
    .buttons a, .buttons button {
      background: none;
      border: none;
      color: inherit;
      font: inherit;
      cursor: pointer;
      outline: inherit;
    }
  </style>
  <link rel="stylesheet" href="elements/css/header.css">
  <link rel="icon" href="elements/embeded/me/logo.png" type="image/x-icon"/>
</head>
<body>
  <?php 
    $sql = "SELECT avatar FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();

    if ($user_data = $user_result->fetch_assoc()) {
        $userAvatar = $user_data['avatar'] ?: 'default-avatar.jpg';
    } else {
        $userAvatar = 'default-avatar.jpg'; // Если пользователь не найден
    }
  ?>
  <noscript>
      <meta http-equiv="refresh" content="0; url=/javascript.html">
  </noscript>

  <header class="header">
    <a href="/" class="logo">
      <img src="elements/embeded/me/logo-header.png" alt="Логотип">
      <span style="color:rgb(98, 0, 255);"><?php echo($project_name); ?></span>
    </a>
    
    <!-- Поисковая строка -->
    <div class="search-container">
      <form action="/search.php" method="get">
        <input type="text" name="search" placeholder="Search video..." required>
        <button type="submit">Search</button>
      </form>
    </div>

    <!-- Блок с кнопками -->
    <div class="buttons">
      <a href="make.php">
        <i class="fas fa-video"></i> <span>Make video</span>
      </a>
      
      <!-- Контейнер меню пользователя -->
      <div class="user-menu-container">
        <img src="<?php echo $userAvatar; ?>" alt="User Avatar" id="userAvatar" class="user-avatar">
        <div id="userMenu" class="dropdown-menu" style="display: none; margin-left: -8vh;">
          <ul>
            <li><a href="/profile.php">Профиль</a></li>
            <li><a href="/settings.php">Настройки</a></li>
            <li><a href="/logout.php">Выход</a></li>
          </ul>
        </div>
      </div>
    </div>
  </header>

  <script src="elements/js/safe.js"></script>
  <script>
    // Переключение выпадающего меню пользователя
    document.getElementById('userAvatar').addEventListener('click', function(event) {
      var menu = document.getElementById('userMenu');
      menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
      event.stopPropagation();
    });

    // Скрытие выпадающих меню при клике вне их области
    document.addEventListener('click', function() {
      document.getElementById('userMenu').style.display = 'none';
      document.getElementById('notificationsMenu').style.display = 'none';
    });
  </script>
</body>
</html>
