<?php 
include("elements/php/main/translator.php"); 
include("elements/php/main/cursor.php");
include("elements/php/main/db.php");
?>

<?php
    include("elements/php/main/verify.php");

    // Удаление аккаунта
    if (isset($_POST['delete_account'])) {
        $delete_query = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param('i', $user_id);
        if ($stmt->execute()) {
            echo "Аккаунт удален.<br>";
            header("Location: exit.php");
            exit();
        } else {
            echo "Ошибка удаления аккаунта.<br>";
        }
        $stmt->close();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout Confirmation</title>
    <link rel="stylesheet" href="elements/css/warning.css">
    <link rel="icon" href="elements/embeded/logo.png" type="image/x-icon"/>
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
    <noscript>
      <meta http-equiv="refresh" content="0; url=/javascript.php">
    </noscript>
    <div class="logout-container">
        <h2>Are you sure you want delete your <?php echo($project_name); ?> account?</h2>
        <form method="POST">
            <div class="button-container">
                <button class="logout-btn" type="submit" name="delete_account">Delete</button>
                <button class="cancel-btn" onclick="window.location.href='feed.php'">Cancel</button>
            </div>
        </form>
    </div>
    <script src="elements/js/safe.js"></script>
</body>
</html>
