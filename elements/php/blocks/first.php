<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Поддержка автоматической смены темы браузером -->
    <meta name="color-scheme" content="light dark">
    <style>
        /* Объявляем CSS-переменные для обеих тем */
        :root {
            --background-color: #eee;         /* Светлый фон */
            --text-color: #000;               /* Тёмный текст */
            --button-bg-color: #ae00ff;        /* Фон кнопки */
            --button-text-color: #ffffff;      /* Цвет текста кнопки */
        }
        /* Переопределяем переменные для тёмной темы */
        @media (prefers-color-scheme: dark) {
            :root {
                --background-color: #222;     /* Тёмный фон */
                --text-color: #fff;           /* Светлый текст */
                /* Цвет кнопки можно оставить тем же или изменить */
                --button-bg-color: #ae00ff;
                --button-text-color: #ffffff;
            }
        }
        
        /* Применяем переменные в стилях */
        body {
            margin: 0;
            background-color: var(--background-color);
            color: var(--text-color);
            font-family: sans-serif;
        }
        
        .grid-center {
            display: grid;
            place-items: center;  /* Центрирование по обоим осям */
            min-height: 100vh;    /* Занимает всю высоту экрана */
        }
        
        .huba {
            height: 50vh;
        }
        
        .metarunner {
            background-color: var(--button-bg-color);
            border: none;
            color: var(--button-text-color);
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            cursor: pointer;
        }
        
        /* Ссылка внутри кнопки наследует цвет */
        .metarunner a {
            color: inherit;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="grid-center">
        <img style="width: 30vw; height: auto;" src="elements/embeded/me/ad.png" alt="You first!">
        <h1 style="text-align: center;"><?php echo htmlentities($project_name) ?> has no video at all</h1>
        <p style="text-align: center;">But you can publish the video first on this platform!</p>
        <div class="metarunner">
            <a href="make.php">upload first video</a>
        </div>
        <div class="huba"></div>
    </div>
</body>
</html>
