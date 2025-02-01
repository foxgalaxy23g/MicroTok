<?php
include("elements/php/translator.php");
include("elements/php/db.php");
include("elements/php/cursor.php");

if($server_open_now == "1")
{
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Unavailable</title>
    <link rel="stylesheet" href="elements/css/closed.css">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
    <noscript>
        <meta http-equiv="refresh" content="0; url=/javascript.html">
    </noscript>
    <?php include("header.php"); ?>
    <h1>Warning! <?php echo($project_name); ?> is currently closed!</h1>
    <p>Please try again later.</p>
    
    <div class="contacts">
        <p>If you have any questions, you can contact us:</p>
        <a href="<?php echo($x_admin); ?>" class="contact-link" target="_blank">
            <img src="elements/embeded/notme/x.png" alt="Twitter" style="border-radius: 10px;">
        </a>
        <a href="<?php echo($tg_admin); ?>" class="contact-link" target="_blank">
            <img src="elements/embeded/notme/tg.png" alt="Telegram" style="border-radius: 10px;">
        </a>
        <a href="<?php echo($yt_admin); ?>" class="contact-link" target="_blank">
            <img src="elements/embeded/notme/yt.png" alt="yt" style="border-radius: 10px;">
        </a>
        <a href="<?php echo($gh_admin); ?>" class="contact-link" target="_blank">
            <img src="elements/embeded/notme/gh.png" alt="gh" style="border-radius: 10px;">
        </a>
    </div>
</body>
</html>
