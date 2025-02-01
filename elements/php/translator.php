<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
<?php
$gtranslator = '1'; // переводчик

if($gtranslator == '1') {
    echo '<link rel="stylesheet" href="elements/css/translator.css">';
    echo '<script type="text/javascript">';
    echo 'function googleTranslateElementInit() {';
    echo 'new google.translate.TranslateElement({pageLanguage: "en"}, "google_translate_element");';
    echo '}';
    echo '</script>';
    echo '<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>';
}
?>
</body>
</html>
