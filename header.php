<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Шапка сайта</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color:rgb(255, 255, 255); 
            padding: 10px 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
        }

        .logo img {
            height: 40px;
            margin-right: 10px;
        }

        .buttons {
            display: flex;
            gap: 20px;
        }

        .buttons a {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            color: #fff;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .buttons a i {
            font-size: 1.2rem;
            color:rgb(98, 0, 255);
        }

        .buttons a {
            color:rgb(98, 0, 255); 
        }
    </style>
</head>
<body>
    <noscript>
      <meta http-equiv="refresh" content="0; url=/javascript.html">
    </noscript>
    <header class="header">
        <a href="/" class="logo">
            <img src="embeded/logo.png" alt="Логотип">
            <a2 style="color:rgb(98, 0, 255);">Microtok</a2>
        </a>
        <div class="buttons">
            <a href="/create-video">
                <i class="fas fa-video"></i> <a href="make.php">make video</a>
            </a>
            <a href="/logout">
                <i class="fas fa-sign-out-alt"></i> <a href="warning.php">exit</a>
            </a>
        </div>
    </header>
</body>
</html>
