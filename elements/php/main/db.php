<?php
// Database configuration (Change it!)
$host = '';
$db = '';
$user = '';
$pass = '';

//mail
$mail_smtp = '';
$mail_login = '';
$mail_pass = '';
$mail_port = '';

// Other settings
$project_name = "MicroTok";
$registration_open = 1;
$aws_s3_enabled = 0;

// Contacts (Change it!)
$x_admin = "https://x.com/foxgalaxy23";
$tg_admin = "https://t.me/lisisamp";
$yt_admin = "https://www.youtube.com/@foxgalaxy23";
$gh_admin = "https://github.com/foxgalaxy23g/MicroTok";
$mail_admin = "mihabear4@gmail.com";
$company_developer = "FoxGalaxy23";

// Status
$server_open_now = 1;

//code
if ((!$mail_smtp || !$mail_login || !$mail_pass) && basename($_SERVER['PHP_SELF']) !== 'closed.php') {
    $server_open_now = 0;
}


// Enable error reporting for debugging
ini_set('display_errors', 13);
error_reporting(E_ALL);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db);
} catch (mysqli_sql_exception $e) {
    // Если не удалось подключиться к базе данных, сервер закрыт
    $server_open_now = 0;
}

// Проверка статуса сервера
if ($server_open_now == 0 && basename($_SERVER['PHP_SELF']) !== 'closed.php') {
    header('Location: closed.php');
    exit();
}
?>
