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