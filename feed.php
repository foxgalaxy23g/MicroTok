<?php
include("elements/php/db.php");
include("elements/php/closed.php");
include("elements/php/verify.php");
include("elements/php/api1.php");
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

function changeVideo() {
  global $conn, $video_id;

  // 1. Получаем текущую тему видео
  $sql = "SELECT theme_id FROM videos WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $video_id);
  $stmt->execute();
  $stmt->bind_result($theme_id);
  $stmt->fetch();
  $stmt->close();

  // 2. Ищем видео из той же темы, что и текущее, или самое популярное, если нет тем
  $sql = "
      SELECT v.id FROM videos v 
      LEFT JOIN video_likes vl ON v.id = vl.video_id
      WHERE v.theme_id = ? AND v.id != ? 
      GROUP BY v.id 
      ORDER BY COUNT(vl.id) DESC 
      LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $theme_id, $video_id);
  $stmt->execute();
  $stmt->bind_result($next_video_id);
  $stmt->fetch();
  $stmt->close();

  // 3. Если нет видео из той же темы, показываем случайное
  if (!$next_video_id) {
      $sql = "SELECT id FROM videos WHERE id != ? ORDER BY RAND() LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("i", $video_id);
      $stmt->execute();
      $stmt->bind_result($next_video_id);
      $stmt->fetch();
      $stmt->close();
  }

  // 4. Переадресуем пользователя на следующее видео
  if ($next_video_id) {
      header("Location: ?id=" . $next_video_id);
      exit;
  }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlentities(mb_strimwidth($video['description'], 0, 60, '...'), ENT_QUOTES, 'UTF-8'); ?> - MicroTok</title>
  <style>

  </style>
  <link rel="stylesheet" href="elements/css/feed/feed.css">
  <link rel="stylesheet" href="elements/css/feed/feed2.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <meta name="description" content="Watch <?php echo htmlentities(mb_strimwidth($video['description'], 0, 150, '...'), ENT_QUOTES, 'UTF-8'); ?> by <?php echo htmlentities($username, ENT_QUOTES, 'UTF-8'); ?> in MicroTok">
</head>
<body>
  <?php include("header.php"); ?>
  <h2 style="color: rgba(98, 0, 255, 0);">^</h2>

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
            <a style="text-decoration: none;"href="profile.php?id=<?php echo $video['user_id']; ?>">
              <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" style="width: 25px; height: 25px; border-radius: 50%; object-fit: cover; vertical-align: middle;">
            </a>
            <a style="text-decoration: none; color: #ffffff;" href="profile.php?id=<?php echo $video['user_id']; ?>"><?php echo htmlspecialchars($username); ?></a>
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
          <button id="open-comments-btn" style="border-radius: 50px; margin-top: 300%; margin-right: -20%;">C</button>
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
    $.ajax({
        url: 'change_video.php',
        type: 'POST',
        data: { current_video_id: currentVideoId },
        success: function(response) {
            if (response) {
                window.location.href = '?id=' + response;
            }
        }
    });
}

  </script>
  <script>
    let availableIds = <?php echo json_encode(array_values($ids)); ?>;
    let currentVideoId = <?php echo $video['id']; ?>;
  </script>
  <h2 style="color: rgba(255, 255, 255, 0);">_</h2>
  <script src="elements/js/feed.js"></script>
  <script src="elements/js/safe.js"></script>
</body>
</html>