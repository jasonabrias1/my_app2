(function () {
    var logoutStarted = false;

    document.addEventListener('click', function (event) {
        var target = event.target;
        var link = target && target.closest ? target.closest('a.logout, a.logout-link') : null;
        if (!link || event.defaultPrevented || event.button !== 0
            || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        event.preventDefault();
        if (logoutStarted) return;
        logoutStarted = true;

        var destination = link.href;
        link.setAttribute('aria-disabled', 'true');

        var overlay = document.createElement('div');
        overlay.className = 'logout-animation';
        overlay.setAttribute('role', 'status');
        overlay.setAttribute('aria-live', 'polite');
        overlay.innerHTML = '<div class="logout-animation__panel">'
            + '<div class="logout-animation__spinner" aria-hidden="true"></div>'
            + '<p class="logout-animation__message">Signing out...</p>'
            + '</div>';

        document.body.appendChild(overlay);
        document.body.classList.add('logout-in-progress');
        window.requestAnimationFrame(function () {
            overlay.classList.add('is-active');
        });

        var delay = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches
            ? 0
            : 300;
        window.setTimeout(function () {
            window.location.assign(destination);
        }, delay);
    });
})();
