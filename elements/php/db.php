<?php
// Database configuration (Change it!)
$host = '';
$db = '';
$user = '';
$pass = '';

// Other settings
$project_name = "MicroTok";
$registration_open = "1";

// Enable error reporting for debugging
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Connect to database
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    $server_open_now = "0"; 
    if (basename($_SERVER['PHP_SELF']) !== 'closed.php') {
        header('Location: closed.php');
        exit();
    }
    die();
}

$server_open_now = "1";

// Contacts (Change it!)
$x_admin = "https://x.com/foxgalaxy23";
$th_admin = "https://t.me/lisisamp";
$yt_admin = "https://www.youtube.com/@foxgalaxy23";
$gh_admin = "https://github.com/foxgalaxy23g/MicroTok";
$company_developer = "FoxGalaxy23";

?>
