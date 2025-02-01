availableIds = availableIds.filter(id => id !== currentVideoId);

window.addEventListener('scroll', () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight) {
        loadRandomVideo();
    }
});

function loadRandomVideo() {
    if (availableIds.length > 0) {
        let randomId = availableIds[Math.floor(Math.random() * availableIds.length)];
        window.location.href = "?id=" + randomId;
    } else {
        alert("Нет других доступных видео.");
    }
}

document.querySelector('#video').addEventListener('play', () => {
    const videoElement = document.querySelector('#video');
    if (videoElement.muted) {
        videoElement.muted = false;
    }
});

const videoElement = document.querySelector('#video');
const progressBar = document.querySelector('.progress-bar');

videoElement.addEventListener('timeupdate', () => {
    const progress = (videoElement.currentTime / videoElement.duration) * 100;
    progressBar.style.width = progress + '%';
});

function togglePlayPause() {
    if (videoElement.paused) {
        videoElement.play();
    } else {
        videoElement.pause();
    }
}