<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Шапка сайта</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>

    </style>
    <link rel="stylesheet" href="elements/css/header.css">
    <link rel="icon" href="elements/embeded/me/logo.png" type="image/x-icon"/>
</head>
<body>
    <?php 
    include("elements/php/db.php"); 
    include("elements/php/translator.php");
    include("elements/php/cursor.php");
    ?>
    <noscript>
      <meta http-equiv="refresh" content="0; url=/javascript.html">
    </noscript>
    <header class="header">
        <a href="/" class="logo">
            <img src="elements/embeded/me/logo-header.png" alt="Логотип">
            <a2 style="color:rgb(98, 0, 255);"><?php echo($project_name); ?></a2>
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
    <script src="elements/js/safe.js"></script>
</body>
</html>
