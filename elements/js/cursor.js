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