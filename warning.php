<?php 
include("elements/php/translator.php"); 
include("elements/php/cursor.php");
include("elements/php/closed.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout Confirmation</title>
    <link rel="stylesheet" href="elements/css/warning.css">
    <style>
    </style>
    <link rel="icon" href="elements/embeded/logo.png" type="image/x-icon"/>
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
    <noscript>
      <meta http-equiv="refresh" content="0; url=/javascript.html">
    </noscript>
    <div class="logout-container">
        <h2>Are you sure you want to log out?</h2>
        <div class="button-container">
            <button class="logout-btn" onclick="window.location.href='exit.php'">Log Out</button>
            <button class="cancel-btn" onclick="window.location.href='feed.php'">Cancel</button>
        </div>
    </div>
    <script src="elements/js/safe.js"></script>
</body>
</html>