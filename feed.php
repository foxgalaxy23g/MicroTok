<?php
include("elements/php/db.php");
include("elements/php/closed.php");
include("elements/php/verify.php");

// ==================================================================
// Обработка AJAX-запросов
// ==================================================================

// 1. Лайки/дизлайки для видео
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['video_id']) && !isset($_POST['load_comments'])) {
    $user_id = authenticate($conn);
    $video_id = (int) $_POST['video_id'];
    $action = $_POST['action'];

    if (in_array($action, ['like', 'dislike'])) {
        $sql = "DELETE FROM video_likes WHERE video_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $video_id, $user_id);
        $stmt->execute();
        $stmt->close();

        $sql = "INSERT INTO video_likes (video_id, user_id, reaction) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $video_id, $user_id, $action);
        $stmt->execute();
        $stmt->close();
    }

    $sql = "SELECT 
                SUM(CASE WHEN reaction = 'like' THEN 1 ELSE 0 END) AS likes,
                SUM(CASE WHEN reaction = 'dislike' THEN 1 ELSE 0 END) AS dislikes
            FROM video_likes WHERE video_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $video_id);
    $stmt->execute();
    $stmt->bind_result($likes, $dislikes);
    $stmt->fetch();
    $stmt->close();

    echo json_encode([
        'likes' => $likes,
        'dislikes' => $dislikes
    ]);
    exit();
}

// 2. Подписка/отписка канала
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['channel_id'])) {
    $user_id = authenticate($conn);
    $channel_id = (int) $_POST['channel_id'];
    $action = $_POST['action'];

    if ($action === 'subscribe') {
        $sql = "INSERT INTO subscriptions (user_id, channel_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $user_id, $channel_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'unsubscribe') {
        $sql = "DELETE FROM subscriptions WHERE user_id = ? AND channel_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $user_id, $channel_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: ?id=$video_id");
    exit();
}

// 3. Лайки/дизлайки для комментариев
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_action'], $_POST['comment_id'])) {
    $user_id = authenticate($conn);
    $comment_id = (int) $_POST['comment_id'];
    $action = $_POST['comment_action'];

    if (in_array($action, ['like', 'dislike'])) {
        $sql = "DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $comment_id, $user_id);
        $stmt->execute();
        $stmt->close();

        $sql = "INSERT INTO comment_likes (comment_id, user_id, reaction) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $comment_id, $user_id, $action);
        $stmt->execute();
        $stmt->close();
    }
    $sql = "SELECT 
                IFNULL((SELECT SUM(CASE WHEN reaction = 'like' THEN 1 ELSE 0 END) FROM comment_likes WHERE comment_id = ?),0) AS likes,
                IFNULL((SELECT SUM(CASE WHEN reaction = 'dislike' THEN 1 ELSE 0 END) FROM comment_likes WHERE comment_id = ?),0) AS dislikes";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $comment_id, $comment_id);
    $stmt->execute();
    $stmt->bind_result($likes, $dislikes);
    $stmt->fetch();
    $stmt->close();
    echo json_encode(['likes' => $likes, 'dislikes' => $dislikes]);
    exit();
}

// 4. Отправка нового комментария
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'], $_POST['video_id']) && !isset($_POST['reply'])) {
    $user_id = authenticate($conn);
    $video_id = (int) $_POST['video_id'];
    $comment = htmlspecialchars($_POST['comment'], ENT_QUOTES, 'UTF-8');
    if (!empty($comment)) {
        $sql = "INSERT INTO comments (video_id, user_id, comment) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $video_id, $user_id, $comment);
        $stmt->execute();
        $stmt->close();
    }
    echo loadComments($video_id);
    exit();
}

// 5. Отправка ответа на комментарий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'], $_POST['comment_id'])) {
    $user_id = authenticate($conn);
    $comment_id = (int) $_POST['comment_id'];
    $reply = htmlspecialchars($_POST['reply'], ENT_QUOTES, 'UTF-8');
    if (!empty($reply)) {
        $sql = "INSERT INTO comment_replies (comment_id, user_id, reply) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $comment_id, $user_id, $reply);
        $stmt->execute();
        $stmt->close();
    }
    echo loadCommentReplies($comment_id);
    exit();
}

// 6. Загрузка комментариев по AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['load_comments'], $_POST['video_id'])) {
    $video_id = (int) $_POST['video_id'];
    echo loadComments($video_id);
    exit();
}

// 7. Загрузка ответов по AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['load_replies'], $_POST['comment_id'])) {
    $comment_id = (int) $_POST['comment_id'];
    echo loadCommentReplies($comment_id);
    exit();
}

function loadComments($video_id) {
    global $conn;
    // Возвращаем id автора комментария (user_id) и аватар
    $sql = "SELECT c.id, c.user_id, c.comment, c.created_at, u.username, u.avatar,
            IFNULL((SELECT SUM(CASE WHEN reaction = 'like' THEN 1 ELSE 0 END) FROM comment_likes WHERE comment_id = c.id),0) AS likes,
            IFNULL((SELECT SUM(CASE WHEN reaction = 'dislike' THEN 1 ELSE 0 END) FROM comment_likes WHERE comment_id = c.id),0) AS dislikes,
            (SELECT COUNT(*) FROM comment_replies WHERE comment_id = c.id) AS replies_count
            FROM comments c 
            JOIN users u ON c.user_id = u.id
            WHERE c.video_id = ?
            ORDER BY c.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $video_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    $stmt->close();
    return json_encode($comments);
}

function loadCommentReplies($comment_id) {
    global $conn;
    // Возвращаем id автора ответа (user_id) и аватар
    $sql = "SELECT r.id, r.reply, r.created_at, u.username, u.avatar, r.user_id 
            FROM comment_replies r 
            JOIN users u ON r.user_id = u.id
            WHERE r.comment_id = ?
            ORDER BY r.created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $replies = [];
    while ($row = $result->fetch_assoc()) {
        $replies[] = $row;
    }
    $stmt->close();
    return json_encode($replies);
}

// ==================================================================
// Загрузка видео и данных для отображения страницы
// ==================================================================

$ids = [];
$sql = "SELECT id FROM videos";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['id'];
    }
}

$video_id = isset($_GET['id']) ? $_GET['id'] : null;
if ($video_id && in_array($video_id, $ids)) {
    $sql = "SELECT * FROM videos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $video_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    if (!empty($ids)) {
        $random_id = $ids[array_rand($ids)];
        header("Location: ?id=" . $random_id);
        exit;
    } else {
        include("header.php");
        echo "<h1>" . htmlentities($project_name) . " has no video at all</h1>";
        echo "<p>But you can publish the video first on this platform!</p>";
        echo "<a href='make.php'>upload first video</a>";
        exit;
    }
}

if ($result->num_rows > 0) {
    $video = $result->fetch_assoc();
    $user_id = $video['user_id'];
    $sql = "SELECT username, avatar FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user_data = $user_result->num_rows > 0 ? $user_result->fetch_assoc() : null;
    $username = $user_data ? $user_data['username'] : 'Unknown User';
    $avatar = $user_data ? $user_data['avatar'] : 'default-avatar.jpg';

    $sql = "SELECT 
                SUM(CASE WHEN reaction = 'like' THEN 1 ELSE 0 END) AS likes,
                SUM(CASE WHEN reaction = 'dislike' THEN 1 ELSE 0 END) AS dislikes
            FROM video_likes WHERE video_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $video_id);
    $stmt->execute();
    $stmt->bind_result($likes, $dislikes);
    $stmt->fetch();
    $stmt->close();

    $sql = "SELECT COUNT(*) FROM subscriptions WHERE user_id = ? AND channel_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $video['user_id']);
    $stmt->execute();
    $stmt->bind_result($is_subscribed);
    $stmt->fetch();
    $stmt->close();

    $sql = "SELECT COUNT(*) FROM subscriptions WHERE channel_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $video['user_id']);
    $stmt->execute();
    $stmt->bind_result($subscribers_count);
    $stmt->fetch();
    $stmt->close();
} else {
    echo "<p>Видео не найдено.</p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlentities(mb_strimwidth($video['description'], 0, 60, '...'), ENT_QUOTES, 'UTF-8'); ?> - MicroTok</title>
  <style>
    /* Общие стили */
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      overflow-x: hidden;
      background-color: #f0f0f0;
    }
    header {
      z-index: 3000;
      position: relative;
    }
    /* Контейнер основного контента. При открытии окна комментариев сдвигается влево */
    .main-container {
      display: flex;
      justify-content: center;
      align-items: center;
      transition: transform 0.5s ease;
      width: 100%;
    }
    .with-comments {
      transform: translateX(-33.33%);
    }
    /* Центрированный плеер */
    .video-container {
      position: relative;
      width: 100%;
      max-width: 400px;
      border-radius: 15px;
      overflow: hidden;
      background: #000;
      margin: 0 auto;
      cursor: pointer; /* для клика по видео */
    }
    video {
      width: 100%;
      height: auto;
      display: block;
    }
    .overlay {
      position: absolute;
      bottom: 10px;
      left: 10px;
      color: white;
      background: rgba(0, 0, 0, 0.6);
      padding: 5px 10px;
      border-radius: 5px;
      font-size: 14px;
      line-height: 1.4;
      box-shadow: 0 0 22px rgba(0, 0, 0, 0.5);
    }
    /* Кнопки реакции для видео – расположены строго справа от плеера */
    .reaction-buttons {
      position: absolute;
      top: 50%;
      right: 10px;
      transform: translateY(-50%);
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .reaction-buttons form,
    .reaction-buttons div {
      margin: 0;
    }
    .reaction-buttons button {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      border: none;
      background-color: #f0f0f0;
      font-size: 20px;
      cursor: pointer;
      display: flex;
      justify-content: center;
      align-items: center;
      box-shadow: 0 0 5px rgba(0,0,0,0.2);
      transition: background-color 0.2s ease;
    }
    .reaction-buttons button:hover {
      
    }
    /* Подписка – та же круглая кнопка */
    .subscription-buttons button {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      border: none;
      background-color: #f0f0f0;
      font-size: 20px;
      cursor: pointer;
      display: flex;
      justify-content: center;
      align-items: center;
      box-shadow: 0 0 5px rgba(0,0,0,0.2);
      transition: background-color 0.2s ease;
    }
    .subscription-buttons button:hover {
    }
    /* Эффект "дождя" */
    #reaction-rain-container {
      position: absolute;
      top: 0;
      left: 0;
      pointer-events: none;
      z-index: 1000;
    }
    /* Кнопка для открытия окна комментариев (расположена отдельно, например, в правом верхнем углу) */
    #open-comments-btn {
      margin-top: 10px;
    }
    /* Окно комментариев */
    #comments-container {
      position: fixed;
      top: 0;
      right: 0;
      width: 33.33%;
      height: 100%;
      background: #fff;
      border-left: 1px solid #ccc;
      padding: 60px 10px 10px 10px;
      box-shadow: -2px 0 5px rgba(0,0,0,0.1);
      z-index: 2000;
      transform: translateX(100%);
      transition: transform 0.5s ease;
      overflow-y: auto;
    }
    #comments-container.visible {
      transform: translateX(0);
    }
    #comments-container h3 {
      margin-top: 0;
      text-align: center;
    }
    /* Форма нового комментария */
    #comments-container form {
      margin-bottom: 15px;
    }
    #comments-container form textarea {
      width: 100%;
      height: 60px;
      resize: vertical;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 10px;
      font-family: inherit;
    }
    #comments-container form button {
      padding: 8px 12px;
      background-color: #007bff;
      color: #fff;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      margin-top: 5px;
    }
    /* Оформление блока комментария */
    .comment {
      background-color: #fafafa;
      border: 1px solid #ddd;
      border-radius: 15px;
      padding: 10px;
      margin-bottom: 10px;
    }
    .comment-header {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 5px;
    }
    .comment-header img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      cursor: pointer;
    }
    .comment-header a {
      text-decoration: none;
      color: #007bff;
      font-weight: bold;
      cursor: pointer;
    }
    .comment-body {
      margin: 5px 0;
      padding: 8px;
      background-color: #fff;
      border-radius: 10px;
      border: 1px solid #ddd;
    }
    .comment-actions {
      margin-top: 5px;
      display: flex;
      gap: 10px;
      align-items: center;
    }
    .comment-actions .action-btn {
      background: none;
      border: none;
      font-size: 18px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 3px;
    }
    .comment-actions .action-btn:hover {
      opacity: 0.8;
    }
    /* Оформление блока ответов */
    .replies {
      margin-top: 10px;
      padding-left: 10px;
      border-left: 2px dashed #ddd;
    }
    .reply {
      background-color: #fefefe;
      border: 1px solid #ddd;
      border-radius: 15px;
      padding: 8px;
      margin-bottom: 8px;
    }
    .reply-header {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 5px;
    }
    .reply-header img {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      object-fit: cover;
      cursor: pointer;
    }
    .reply-header a {
      text-decoration: none;
      color: #007bff;
      font-weight: bold;
      cursor: pointer;
    }
    .reply-body {
      padding: 6px;
      background-color: #fff;
      border-radius: 10px;
      border: 1px solid #ddd;
    }
    .reply-actions {
      margin-top: 5px;
      display: flex;
      gap: 10px;
      align-items: center;
    }
    .reply-actions .action-btn {
      background: none;
      border: none;
      font-size: 16px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 3px;
    }
    .reply-actions .action-btn:hover {
      opacity: 0.8;
    }
    
    /* Тёмная тема для окна комментариев */
    @media (prefers-color-scheme: dark) {
      body {
        background-color: #121212;
        color: #fff;
      }
      .video-container {
        background: #333;
      }
      .reply
      {
        background: #333;
        
      }
      .overlay {
        background: rgba(0, 0, 0, 0.8);
      }
      .reaction-buttons button,
      .subscription-buttons button {
        background-color: #333;
        color: #fff;
      }
      #comments-container {
        background: #222;
        border-left: 1px solid #444;
        color: #fff;
      }
      .reply-header
        {
            background-color: #313131;
        }
        .overlay {
          background: rgba(0, 0, 0, 0.8);
        }
      .comment {
        background-color: #333;
        border: 1px solid #444;
      }
      .comment-header a,
      .reply-header a {
        color: #66aaff;
      }
      .comment-body, .reply-body {
        background-color: #444;
        border: 1px solid #555;
      }
    }
    /* Плавное появление элементов */
    .fade-in {
      opacity: 0;
      transition: opacity 1s ease-in-out;
    }
    .fade-in.visible {
      opacity: 1;
    }
    .video-container{
        margin-top: 5%;
    }
  </style>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <meta name="description" content="Watch <?php echo htmlentities(mb_strimwidth($video['description'], 0, 150, '...'), ENT_QUOTES, 'UTF-8'); ?> by <?php echo htmlentities($username, ENT_QUOTES, 'UTF-8'); ?> in MicroTok">
</head>
<body>
  <?php include("header.php"); ?>

  <!-- Кнопка для открытия/закрытия комментариев (отдельно от кнопок лайка/дизлайка) -->

  <!-- Контейнер для эффекта "дождя" -->
  <div id="reaction-rain-container"></div>

  <!-- Основной контейнер с видео и реакциями -->
  <div class="main-container" id="main-content">
    <div>
      <!-- Видео. При клике по видео переключается его состояние (play/pause) -->
      <div class="video-container" id="video-container">
        <!-- Оборачиваем видео в ссылку на профиль автора только при клике по аватарке/нику -->
        <video id="video" src="<?php echo htmlspecialchars($video['path']); ?>" autoplay loop muted playsinline></video>
        <div class="overlay">
          <p>
            <a href="profile.php?id=<?php echo $video['user_id']; ?>">
              <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" style="width: 25px; height: 25px; border-radius: 50%; object-fit: cover; vertical-align: middle;">
            </a>
            <a href="profile.php?id=<?php echo $video['user_id']; ?>"><?php echo htmlspecialchars($username); ?></a>
            (subs <?php echo $subscribers_count; ?>)
          </p>
          <p>Description: <?php echo htmlspecialchars($video['description']); ?></p>
        </div>
        <div class="progress-bar-container">
          <div class="progress-bar"></div>
        </div>
      </div>

      <!-- Блок с круглыми кнопками реакции (расположен справа от плеера) -->
      <div class="reaction-buttons">
        <form method="post" id="like-form">
          <input type="hidden" name="video_id" value="<?php echo $video_id; ?>">
          <input type="hidden" name="action" value="like">
          <button type="submit">
            👍<br>
            <span id="likes-count"><?php echo $likes; ?></span>
          </button>
        </form>
        <form method="post" id="dislike-form">
          <input type="hidden" name="video_id" value="<?php echo $video_id; ?>">
          <input type="hidden" name="action" value="dislike">
          <button type="submit">
            👎<br>
            <span id="dislikes-count"><?php echo $dislikes; ?></span>
          </button>
          <button id="open-comments-btn">C</button>
        </form>
        <div class="subscription-buttons">
          <?php if ($is_subscribed > 0): ?>
            <form method="post">
              <input type="hidden" name="action" value="unsubscribe">
              <input type="hidden" name="channel_id" value="<?php echo $video['user_id']; ?>">
              <button type="submit">–</button>
            </form>
          <?php else: ?>
            <form method="post">
              <input type="hidden" name="action" value="subscribe">
              <input type="hidden" name="channel_id" value="<?php echo $video['user_id']; ?>">
              <button type="submit">+</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Окно комментариев (изначально скрыто) -->
  <div id="comments-container">
    <h3>Комментарии</h3>
    <form id="comment-form">
      <textarea name="comment" placeholder="Оставьте комментарий..." required></textarea>
      <input type="hidden" name="video_id" value="<?php echo $video_id; ?>">
      <button type="submit">Отправить</button>
    </form>
    <div id="comments-list">
      <!-- Список комментариев подгружается AJAX-ом -->
    </div>
  </div>

  <div class="bottom-space"></div>

  <script>
    $(document).ready(function() {
      let commentsVisible = false;

      // Открытие/закрытие окна комментариев
      $('#open-comments-btn').click(function() {
        commentsVisible = !commentsVisible;
        if (commentsVisible) {
          $('#comments-container').addClass('visible');
          $('#main-content').addClass('with-comments');
          $(this).text('C');
          loadComments();
        } else {
          $('#comments-container').removeClass('visible');
          $('#main-content').removeClass('with-comments');
          $(this).text('C');
        }
      });

      // Переключение play/pause при клике по видео (но не при клике по ссылкам внутри overlay)
      $('#video-container').click(function(e) {
        // Если клик произошёл не по элементам с ссылками
        if ($(e.target).closest('a').length === 0) {
          let video = $('#video').get(0);
          if (video.paused) {
            video.play();
          } else {
            video.pause();
          }
        }
      });

      // Загрузка комментариев
      function loadComments() {
        $.ajax({
          url: '',
          type: 'POST',
          data: {
            load_comments: true,
            video_id: <?php echo $video_id; ?>
          },
          success: function(response) {
            let comments = JSON.parse(response);
            $('#comments-list').empty();
            comments.forEach(function(comment) {
              let commentHtml = `
              <div class="comment" data-comment-id="${comment.id}">
                <div class="comment-header">
                  <a href="profile.php?id=${comment.user_id}">
                    <img src="${comment.avatar}" alt="Avatar">
                  </a>
                  <a href="profile.php?id=${comment.user_id}">${comment.username}</a>
                  <small>${comment.created_at}</small>
                </div>
                <div class="comment-body">${comment.comment}</div>
                <div class="comment-actions">
                  <button class="action-btn like-comment" data-comment-id="${comment.id}">👍 <span>${comment.likes}</span></button>
                  <button class="action-btn dislike-comment" data-comment-id="${comment.id}">👎 <span>${comment.dislikes}</span></button>
                  <button class="action-btn show-replies" data-comment-id="${comment.id}">💬 <span>${comment.replies_count}</span></button>
                </div>
                <div class="replies" id="replies-${comment.id}" style="display: none;">
                  <form class="reply-form" data-comment-id="${comment.id}">
                    <textarea name="reply" placeholder="Ответить..." required></textarea>
                    <button type="submit">Отправить</button>
                  </form>
                  <div class="reply-list" id="reply-list-${comment.id}"></div>
                </div>
              </div>`;
              $('#comments-list').append(commentHtml);
            });
          }
        });
      }

      // Отправка нового комментария
      $('#comment-form').submit(function(e) {
        e.preventDefault();
        let form = $(this);
        $.ajax({
          url: '',
          type: 'POST',
          data: form.serialize(),
          success: function(response) {
            loadComments();
            form.find('textarea').val('');
          }
        });
      });

      // Лайк комментария
      $(document).on('click', '.like-comment', function() {
        let commentId = $(this).data('comment-id');
        $.ajax({
          url: '',
          type: 'POST',
          data: {
            comment_action: 'like',
            comment_id: commentId
          },
          success: function(response) {
            let data = JSON.parse(response);
            $(`.comment[data-comment-id="${commentId}"] .like-comment span`).text(data.likes);
            $(`.comment[data-comment-id="${commentId}"] .dislike-comment span`).text(data.dislikes);
            createRain('👍');
          }
        });
      });

      // Дизлайк комментария
      $(document).on('click', '.dislike-comment', function() {
        let commentId = $(this).data('comment-id');
        $.ajax({
          url: '',
          type: 'POST',
          data: {
            comment_action: 'dislike',
            comment_id: commentId
          },
          success: function(response) {
            let data = JSON.parse(response);
            $(`.comment[data-comment-id="${commentId}"] .like-comment span`).text(data.likes);
            $(`.comment[data-comment-id="${commentId}"] .dislike-comment span`).text(data.dislikes);
            createRain('👎');
          }
        });
      });

      // Показать/скрыть ответы
      $(document).on('click', '.show-replies', function() {
        let commentId = $(this).data('comment-id');
        let repliesContainer = $(`#replies-${commentId}`);
        if (repliesContainer.is(':visible')) {
          repliesContainer.hide();
        } else {
          $.ajax({
            url: '',
            type: 'POST',
            data: {
              load_replies: true,
              comment_id: commentId
            },
            success: function(response) {
              let replies = JSON.parse(response);
              let html = '';
              replies.forEach(function(reply) {
                html += `<div class="reply">
                            <div class="reply-header">
                              <a href="profile.php?id=${reply.user_id}">
                                <img src="${reply.avatar}" alt="Avatar">
                              </a>
                              <a href="profile.php?id=${reply.user_id}">${reply.username}</a>
                              <small>${reply.created_at}</small>
                            </div>
                            <div class="reply-body">${reply.reply}</div>
                            <div class="reply-actions">
                            </div>
                          </div>`;
              });
              $(`#reply-list-${commentId}`).html(html);
              repliesContainer.show();
            }
          });
        }
      });

      // Отправка ответа
      $(document).on('submit', '.reply-form', function(e) {
        e.preventDefault();
        let form = $(this);
        let commentId = form.data('comment-id');
        $.ajax({
          url: '',
          type: 'POST',
          data: form.serialize() + '&comment_id=' + commentId,
          success: function(response) {
            $.ajax({
              url: '',
              type: 'POST',
              data: {
                load_replies: true,
                comment_id: commentId
              },
              success: function(resp) {
                let replies = JSON.parse(resp);
                let html = '';
                replies.forEach(function(reply) {
                  html += `<div class="reply">
                              <div class="reply-header">
                                <a href="profile.php?id=${reply.user_id}">
                                  <img src="${reply.avatar}" alt="Avatar">
                                </a>
                                <a href="profile.php?id=${reply.user_id}">${reply.username}</a>
                                <small>${reply.created_at}</small>
                              </div>
                              <div class="reply-body">${reply.reply}</div>
                              <div class="reply-actions">
                              </div>
                            </div>`;
                });
                $(`#reply-list-${commentId}`).html(html);
                form.find('textarea').val('');
              }
            });
          }
        });
      });

      // Обработчики для видео-лайков/дизлайков
      $('#like-form').submit(function(e) {
        e.preventDefault();
        let form = $(this);
        $.ajax({
          url: '',
          type: 'POST',
          data: form.serialize(),
          success: function(response) {
            let data = JSON.parse(response);
            $('#likes-count').text(data.likes);
            $('#dislikes-count').text(data.dislikes);
            createRain('👍');
          }
        });
      });
      $('#dislike-form').submit(function(e) {
        e.preventDefault();
        let form = $(this);
        $.ajax({
          url: '',
          type: 'POST',
          data: form.serialize(),
          success: function(response) {
            let data = JSON.parse(response);
            $('#likes-count').text(data.likes);
            $('#dislikes-count').text(data.dislikes);
            createRain('👎');
          }
        });
      });

      // Эффект "дождя" для реакций (для видео, комментариев и ответов)
      function createRain(symbol) {
        for (let i = 0; i < 20; i++) {
          let randX = Math.random() * $(window).width();
          let randDuration = Math.random() * 5 + 4;
          let size = Math.random() * 20 + 10;
          let rainElement = $('<div>').text(symbol)
            .css({
              position: 'absolute',
              top: '-50px',
              left: randX + 'px',
              fontSize: size + 'px',
              opacity: 0.8,
              color: 'rgba(0, 0, 0, 0.5)',
              pointerEvents: 'none',
              zIndex: 1000
            })
            .appendTo('#reaction-rain-container');
          rainElement.animate({
            top: $(window).height() + 'px',
            opacity: 0
          }, randDuration * 1000, 'linear', function() {
            $(this).remove();
          });
        }
      }
    });
    window.addEventListener('scroll', function() {
  const scrollPosition = window.scrollY + window.innerHeight;
  const pageHeight = document.documentElement.scrollHeight;

  // Когда пользователь прокручивает до самого низа
  if (scrollPosition >= pageHeight) {
    // Здесь можно переключить на следующее видео (или предыдущие, если это нужно)
    changeVideo();
  }
});

function changeVideo() {
  // Логика для смены видео. Например:
  const currentVideo = document.querySelector('.video.active');
  const nextVideo = currentVideo.nextElementSibling;  // Или .previousElementSibling для предыдущего

  if (nextVideo) {
    currentVideo.classList.remove('active');
    nextVideo.classList.add('active');
  }
}
  </script>
  <script>
    let availableIds = <?php echo json_encode(array_values($ids)); ?>;
    let currentVideoId = <?php echo $video['id']; ?>;
  </script>
  <h2>_</h2>
  <script src="elements/js/feed.js"></script>
  <script src="elements/js/safe.js"></script>
</body>
</html>
