<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .logout-container {
            text-align: center;
            background-color: #ffffff;
            padding: 20px 40px;
            border: 1px solidrgb(255, 255, 255);
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .logout-container h2 {
            margin-bottom: 20px;
        }

        .button-container {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .button-container button {
            flex: 1;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .logout-btn {
            background-color:rgb(98, 0, 255);
            color: white;
        }

        .logout-btn:hover {
            background-color:rgb(85, 0, 255);
        }

        .cancel-btn {
            background-color:rgb(255, 255, 255);
            color:rgb(115, 0, 255);
        }

        .cancel-btn:hover {
            background-color:rgb(255, 255, 255);
        }
    </style>
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
</body>
</html>