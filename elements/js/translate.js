document.getElementById('translateButton').addEventListener('click', function() {
    var iframe = document.querySelector('iframe');
    var contentWindow = iframe.contentWindow;
    var translateSelect = contentWindow.document.querySelector('.goog-te-combo');
    
    // Устанавливаем язык перевода на русский
    translateSelect.value = 'ru';
    translateSelect.dispatchEvent(new Event('change'));
});