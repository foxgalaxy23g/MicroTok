(function() {
    let devToolsOpen = false; 

    function detectDevTools() {
        const threshold = 160; 
        if (
            window.outerWidth - window.innerWidth > threshold || 
            window.outerHeight - window.innerHeight > threshold
        ) {
            if (!devToolsOpen) {
                devToolsOpen = true; 
                console.log("Never gonna give you up!");
                location.href = "https://www.youtube.com/watch?v=dQw4w9WgXcQ"; // Рикролл
            }
        } else {
            devToolsOpen = false;
        }

        requestAnimationFrame(detectDevTools);
    }


    detectDevTools();

    document.addEventListener('contextmenu', (e) => {
        e.preventDefault();
    });

    document.addEventListener('keydown', (e) => {
            if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && e.key === 'I')) {
                e.preventDefault();
                alert("Инструменты разработчика запрещены!");
            }

            const forbiddenCharacters = /[{}:><?"|\[\];/~]/;
            const inputValue = event.target.value;
            
            if (forbiddenCharacters.test(inputValue)) {
                event.target.value = inputValue.replace(forbiddenCharacters, "");
            }
        });
    })();
    document.addEventListener('keydown', (e) => {
    if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && e.key === 'I') || (e.ctrlKey && e.key === 'U')) {
        e.preventDefault();
    }
    
});