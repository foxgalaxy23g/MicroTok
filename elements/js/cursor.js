document.addEventListener("contextmenu", function(e) {
    e.preventDefault();
    let menu = document.getElementById("contextMenu");
    menu.style.display = "block";
    menu.style.left = `${e.pageX}px`;
    menu.style.top = `${e.pageY}px`;
});

document.addEventListener("click", function() {
    document.getElementById("contextMenu").style.display = "none";
});

function toggleTheme() {
    document.body.classList.toggle("dark");
}

// Функция для установки темы в зависимости от предпочтений пользователя
function setTheme() {
    const userTheme = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
    document.body.classList.add(userTheme); // Добавляем класс темы (dark или light)
}
// Запуск функции при загрузке страницы
window.onload = setTheme;