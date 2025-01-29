const carousel = document.getElementById("carousel");
    
let startY = 0; 
let currentY = 0;
let currentSlide = 0;
const slideCount = carousel.children.length;
    
function setTransform(offset) {
    carousel.style.transform = `translateY(${offset}px)`;
}

function startSwipe(event) {
    startY = event.touches ? event.touches[0].clientY : event.clientY;
    currentY = startY;
}

function moveSwipe(event) {
    const y = event.touches ? event.touches[0].clientY : event.clientY;
    const deltaY = y - startY;
    setTransform(-currentSlide * window.innerHeight + deltaY);
    currentY = y;
}

function endSwipe() {
    const deltaY = currentY - startY;

    if (Math.abs(deltaY) > 50) {
        if (deltaY < 0 && currentSlide < slideCount - 1) {
            currentSlide++;
        } else if (deltaY > 0 && currentSlide > 0) {
            currentSlide--; 
        }
    }

    setTransform(-currentSlide * window.innerHeight);
}

carousel.addEventListener("mousedown", startSwipe);
carousel.addEventListener("mousemove", moveSwipe);
carousel.addEventListener("mouseup", endSwipe);
carousel.addEventListener("mouseleave", endSwipe);

carousel.addEventListener("touchstart", startSwipe);
carousel.addEventListener("touchmove", moveSwipe);
carousel.addEventListener("touchend", endSwipe);
