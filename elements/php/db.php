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
?>