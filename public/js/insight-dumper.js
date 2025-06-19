document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.insight-dump-toggle');

    toggles.forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            const content = toggle.nextElementSibling;
            if (toggle.classList.contains('opened')) {
                toggle.classList.remove('opened');
                toggle.classList.add('closed');
                content.classList.remove('insight-dump-array-content-opened');
                content.classList.add('insight-dump-array-content-closed');
            } else {
                toggle.classList.remove('closed');
                toggle.classList.add('opened');
                content.classList.remove('insight-dump-array-content-closed');
                content.classList.add('insight-dump-array-content-opened');
            }
        });
    });
});
