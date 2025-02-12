<?php
session_start();
// Подключаем API (в котором уже происходит аутентификация)
include("elements/php/main/api1.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['current_video_id'])) {
  // Получаем текущий ID видео из POST
  $current_video_id = (int) $_POST['current_video_id'];
  
  // Запрос для получения следующего видео (наиболее "популярного", отличного от текущего)
  $sql = "
      SELECT v.id 
      FROM videos v 
      LEFT JOIN video_likes vl ON v.id = vl.video_id
      WHERE v.id != ? 
      GROUP BY v.id 
      ORDER BY COUNT(vl.id) DESC 
      LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $current_video_id);
  $stmt->execute();
  $stmt->bind_result($next_video_id);
  $stmt->fetch();
  $stmt->close();
  
  // Если нашли следующее видео, возвращаем его ID, иначе возвращаем текущий
  echo $next_video_id ? $next_video_id : $current_video_id;
  exit;
}

// Загрузка списка видео
$ids = [];
$sql = "SELECT id FROM videos";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['id'];
    }
}

$video_id = isset($_GET['id']) ? (int) $_GET['id'] : null;
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
        include("elements/php/blocks/header.php");
        include("elements/php/blocks/first.php");
        exit;
    }
}

if ($result->num_rows > 0) {
  $video = $result->fetch_assoc();
  $video_owner_id = $video['user_id'];

  $sql = "SELECT username, avatar FROM users WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $video_owner_id);
  $stmt->execute();
  $user_result = $stmt->get_result();
  $user_data = $user_result->num_rows > 0 ? $user_result->fetch_assoc() : null;
  $username = $user_data ? $user_data['username'] : 'Unknown User';
  $avatar = $user_data ? $user_data['avatar'] : 'default-avatar.jpg';

  // Получаем данные о лайках и дизлайках
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

  // Получаем идентификатор текущего пользователя
  $current_user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

  // Проверка подписки на канал
  $sql = "SELECT COUNT(*) FROM subscriptions WHERE user_id = ? AND channel_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $current_user_id, $video_owner_id);
  $stmt->execute();
  $stmt->bind_result($is_subscribed);
  $stmt->fetch();
  $stmt->close();

  // Количество подписчиков
  $sql = "SELECT COUNT(*) FROM subscriptions WHERE channel_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $video_owner_id);
  $stmt->execute();
  $stmt->bind_result($subscribers_count);
  $stmt->fetch();
  $stmt->close();

  // Проверяем, был ли уже просмотр видео
  if (!isset($_SESSION['viewed_videos'])) {
      $_SESSION['viewed_videos'] = [];
  }

  // Добавление только если пользователь еще не смотрел видео
  if (!in_array($video_id, $_SESSION['viewed_videos'])) {
      $user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

      // Проверяем, был ли этот просмотр уже зарегистрирован в таблице
      if ($user_id === null) {
          $stmtViewsCheck = $conn->prepare("SELECT COUNT(*) FROM video_views WHERE video_id = ? AND user_id IS NULL");
          $stmtViewsCheck->bind_param("i", $video_id);
      } else {
          $stmtViewsCheck = $conn->prepare("SELECT COUNT(*) FROM video_views WHERE video_id = ? AND user_id = ?");
          $stmtViewsCheck->bind_param("ii", $video_id, $user_id);
      }
      $stmtViewsCheck->execute();
      $stmtViewsCheck->bind_result($view_exists);
      $stmtViewsCheck->fetch();
      $stmtViewsCheck->close();

      if ($view_exists == 0) {
          // Добавляем новый просмотр
          if ($user_id === null) {
              $stmtViews = $conn->prepare("INSERT INTO video_views (video_id, user_id) VALUES (?, NULL)");
              $stmtViews->bind_param("i", $video_id);
          } else {
              $stmtViews = $conn->prepare("INSERT INTO video_views (video_id, user_id) VALUES (?, ?)");
              $stmtViews->bind_param("ii", $video_id, $user_id);
          }
          $stmtViews->execute();
          $stmtViews->close();

          // Добавляем в сессию, чтобы избежать повторных просмотров
          $_SESSION['viewed_videos'][] = $video_id;
      }
  }

  // Получаем общее количество просмотров
  $stmtCount = $conn->prepare("SELECT COUNT(*) FROM video_views WHERE video_id = ?");
  $stmtCount->bind_param("i", $video_id);
  $stmtCount->execute();
  $stmtCount->bind_result($views);
  $stmtCount->fetch();
  $stmtCount->close();
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
  <link rel="stylesheet" href="elements/css/feed/feed.css">
  <link rel="stylesheet" href="elements/css/feed/feed2.css">
  <style>
    .progress-bar-container {
  width: 100%;
  height: 5px;
  background: #ccc;
  position: relative;
  overflow: hidden;
}
.progress-bar {
        width: 0;
        height: 100%;
        background: linear-gradient(90deg, red, orange, yellow, green, cyan, blue, violet);
        transition: width 0.1s linear;
      }
  </style>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <meta name="description" content="Watch <?php echo htmlentities(mb_strimwidth($video['description'], 0, 150, '...'), ENT_QUOTES, 'UTF-8'); ?> by <?php echo htmlentities($username, ENT_QUOTES, 'UTF-8'); ?> in MicroTok">
  <style>
    /* Пример простого стиля для комментариев */
    #comments-container.visible { display: block; }
    #comments-container { display: none; }
  </style>
</head>
<body>
  <noscript>
    <meta http-equiv="refresh" content="0; url=/javascript.php">
  </noscript>
  <?php include("elements\php\blocks\headers\header.php"); ?>
  <h2 style="color: rgba(98, 0, 255, 0);">^</h2>
  <div id="reaction-rain-container"></div>
  <div class="main-container" id="main-content">
    <div>
      <div class="video-container" id="video-container">
        <video id="video" src="<?php echo htmlspecialchars($video['path']); ?>" autoplay loop muted playsinline></video>
        <div class="overlay">
          <p>
            <a style="text-decoration: none;" href="profile.php?id=<?php echo $video_owner_id; ?>">
              <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" style="width: 25px; height: 25px; border-radius: 50%; object-fit: cover;">
            </a>
            <a style="text-decoration: none; color: #ffffff;" href="profile.php?id=<?php echo $video_owner_id; ?>"><?php echo htmlspecialchars($username); ?></a>
            (subs <?php echo $subscribers_count; ?>)
          </p>
          <p>Description: <?php echo htmlspecialchars($video['description']); ?></p>
          <p>Просмотры: <?php echo $views; ?></p>
        </div>
        <div class="progress-bar-container">
          <div class="progress-bar"></div>
        </div>
      </div>
      <div class="reaction-buttons">
        <!-- Форма лайка -->
        <form method="post" id="like-form">
          <input type="hidden" name="video_id" value="<?php echo $video_id; ?>">
          <input type="hidden" name="action" value="like">
          <button type="submit">
            👍<br>
            <span id="likes-count"><?php echo $likes; ?></span>
          </button>
        </form>
        <!-- Форма дизлайка -->
        <form method="post" id="dislike-form">
          <input type="hidden" name="video_id" value="<?php echo $video_id; ?>">
          <input type="hidden" name="action" value="dislike">
          <button type="submit">
            👎<br>
            <span id="dislikes-count"><?php echo $dislikes; ?></span>
          </button>
          <!-- Кнопка для показа комментариев -->
          <button id="open-comments-btn" style="border-radius: 50px; margin-top: 300%; margin-right: -20%;">C</button>
        </form>
        <div class="subscription-buttons">
          <?php if ($is_subscribed > 0): ?>
            <!-- Форма отписки -->
            <form method="post" class="subscription-form" id="subscription-form">
              <input type="hidden" name="action" value="unsubscribe">
              <input type="hidden" name="channel_id" value="<?php echo $video_owner_id; ?>">
              <button type="submit">–</button>
            </form>
          <?php else: ?>
            <!-- Форма подписки -->
            <form method="post" class="subscription-form" id="subscription-form">
              <input type="hidden" name="action" value="subscribe">
              <input type="hidden" name="channel_id" value="<?php echo $video_owner_id; ?>">
              <button type="submit">+</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <!-- Блок комментариев -->
  <div id="comments-container">
    <h3>Комментарии</h3>
    <form id="comment-form">
      <textarea name="comment" placeholder="Оставьте комментарий..." required></textarea>
      <input type="hidden" name="video_id" value="<?php echo $video_id; ?>">
      <button type="submit">Отправить</button>
    </form>
    <div id="comments-list"></div>
  </div>
  <h2 style="color: rgba(98, 0, 255, 0);">^</h2>
  <h2 style="color: rgba(98, 0, 255, 0);">^</h2>
  <script>
  $(document).ready(function() {
    // Обработка клика по кнопке показа/скрытия комментариев
    let commentsVisible = false;
    $('#open-comments-btn').click(function(e) {
      e.preventDefault();
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

    // Переключение воспроизведения видео (если клик не по ссылке)
    $('#video-container').click(function(e) {
      if ($(e.target).closest('a').length === 0) {
        let video = $('#video').get(0);
        if (video.paused) {
          video.play();
        } else {
          video.pause();
        }
      }
    });

    // AJAX-загрузка комментариев
    function loadComments() {
      $.ajax({
        url: '', // текущий URL
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

    // Обработка отправки нового комментария
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

    // Обработка лайка комментария
    $(document).on('click', '.like-comment', function(e) {
      e.preventDefault();
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
        }
      });
    });

    // Обработка дизлайка комментария
    $(document).on('click', '.dislike-comment', function(e) {
      e.preventDefault();
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
        }
      });
    });

    // Показ/скрытие ответов к комментарию
    $(document).on('click', '.show-replies', function(e) {
      e.preventDefault();
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
                        </div>`;
            });
            $(`#reply-list-${commentId}`).html(html);
            repliesContainer.show();
          }
        });
      }
    });

    // Обработка отправки ответа
    $(document).on('submit', '.reply-form', function(e) {
      e.preventDefault();
      let form = $(this);
      let commentId = form.data('comment-id');
      $.ajax({
        url: '',
        type: 'POST',
        data: form.serialize() + '&comment_id=' + commentId,
        success: function(response) {
          // После отправки ответа перезагружаем список ответов
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
                          </div>`;
              });
              $(`#reply-list-${commentId}`).html(html);
              form.find('textarea').val('');
            }
          });
        }
      });
    });

    // Обработка отправки формы лайка видео
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

    // Обработка отправки формы дизлайка видео
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

    // Обработка подписки/отписки
    $('.subscription-form').submit(function(e) {
      e.preventDefault();
      let form = $(this);
      $.ajax({
        url: '',
        type: 'POST',
        data: form.serialize(),
        success: function(response) {
          let data = JSON.parse(response);
          if(data.status === 'success'){
            // Если подписка/отписка прошла успешно, можно изменить интерфейс
            // Например, заменить содержимое формы (это пример, адаптируйте под свою логику)
            if (form.find('input[name="action"]').val() === 'subscribe') {
              form.find('input[name="action"]').val('unsubscribe');
              form.find('button').text('-');
              location.reload();
            } else {
              form.find('input[name="action"]').val('subscribe');
              form.find('button').text('+');
              location.reload();
            }
          }
        }
      });
    });

    // Функция эффекта "дождя" для реакций
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

    // Автоматическая смена видео при прокрутке до низа страницы
    $(window).on('scroll', function() {
      const scrollPosition = $(window).scrollTop() + $(window).height();
      const pageHeight = $(document).height();
      if (scrollPosition >= pageHeight) {
        changeVideo();
      }
    });

    function changeVideo() {
      $.ajax({
        url: '',
        type: 'POST',
        data: { current_video_id: currentVideoId },
        success: function(response) {
          if (response) {
            window.location.href = '?id=' + response;
          }
        }
      });
    }
    
    let availableIds = <?php echo json_encode(array_values($ids)); ?>;
    let currentVideoId = <?php echo $video['id']; ?>;
  });
  </script>
  <script>
    $(document).ready(function() {
  // Получаем элементы видео и прогрессбара
  let video = document.getElementById('video');
  let progressBar = document.querySelector('.progress-bar');

  // При обновлении времени воспроизведения видео
  video.addEventListener('timeupdate', function() {
    if (video.duration) {
      let percentage = (video.currentTime / video.duration) * 100;
      progressBar.style.width = percentage + '%';
    }
  });
});

  </script>
  <script src="elements/js/feed.js"></script>
  <script src="elements/js/safe.js"></script>
</body>
</html>
