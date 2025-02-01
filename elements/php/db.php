<?php
$host = '';
$db = '';
$user = '';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Ошибка подключения к базе данных: " . $conn->connect_error);
}
?>

<?php
//other thinks
$project_name = "MicroTok";
$registration_open = "1";
$server_open_now = "1";

//contacts(Change it!)
$x_admin = "https://x.com/foxgalaxy23";
$th_admin = "https://t.me/lisisamp";
$yt_admin = "https://www.youtube.com/@foxgalaxy23";
$gh_admin = "https://github.com/foxgalaxy23g/MicroTok";
$company_developer = "FoxGalaxy23";
?>