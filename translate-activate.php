<!DOCTYPE html>
<html lang="en-US">
<head>
    <title> Select your language🌎 </title>
    <link rel="icon" href="elements/embeded/me/logo.png" type="image/x-icon"/>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="elements/css/settings.css">
</head>
<body>
    <?php include("elements/php/main/db.php"); ?>
    <noscript>
        <meta http-equiv="refresh" content="0; url=/javascript.php">
    </noscript>
    <h1>Select your language🌎</h1>
    <a href="index.php">Go back</a>
    <div id="google_translate_element"></div>

    <script type="text/javascript">
        function googleTranslateElementInit() {
        new google.translate.TranslateElement({pageLanguage: 'en'}, 'google_translate_element');
        }
    </script>

    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

</body>
</html> 