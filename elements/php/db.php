<?php
//data base MySQL(Change it!)
$host = '';
$db = '';
$user = '';
$pass = '';

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

//connect to bd
ini_set('display_errors', 11);

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    $current_page = basename($_SERVER['PHP_SELF']);
    $server_open_now = "0";
    if ($current_page !== 'closed.php') {
        header('Location: closed.php');
        exit();
    }
}
?>