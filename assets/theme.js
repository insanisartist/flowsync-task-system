/*
 * Переключение светлой и темной темы.
 *
 * Что делает:
 * - сохраняет выбранную тему в localStorage;
 * - применяет тему при повторном открытии страниц;
 * - обновляет текст на кнопке переключения.
 */

(function () {
    function getSavedTheme() {
        return localStorage.getItem('flowsync-theme') || 'light';
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            var label = button.querySelector('[data-theme-label]');
            if (label) {
                label.textContent = theme === 'dark' ? 'Тёмная тема' : 'Светлая тема';
            }
        });
    }

    function toggleTheme() {
        var nextTheme = getSavedTheme() === 'dark' ? 'light' : 'dark';
        localStorage.setItem('flowsync-theme', nextTheme);
        applyTheme(nextTheme);
    }

    document.addEventListener('DOMContentLoaded', function () {
        applyTheme(getSavedTheme());
        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            button.addEventListener('click', toggleTheme);
        });
    });
})();
