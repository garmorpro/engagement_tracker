// Client-side inactivity auto-logout. Mirrors auth/session_check.php's own
// 15-minute server-side check ($inactive_limit there), but that one only
// fires reactively on the NEXT page load/request — someone who just sits on
// an already-open page never triggers it. This is what actually redirects
// while idle, without needing any further navigation.
//
// Was fully commented out (both this file's contents and its one active
// <script> include) and only ever wired into 2 of the app's ~14
// authenticated pages, which is why "logs out on inactivity" never actually
// happened in practice. Every page that includes auth/session_check.php
// should also include this script.
(function() {
    const INACTIVITY_LIMIT = 15 * 60 * 1000; // 15 minutes — keep in sync with session_check.php
    let inactivityTimer;

    function resetTimer() {
        clearTimeout(inactivityTimer);
        inactivityTimer = setTimeout(logoutUser, INACTIVITY_LIMIT);
    }

    function logoutUser() {
        fetch(BASE_URL + '/auth/logout.php')
            .finally(() => {
                window.location.href = BASE_URL + '/?timeout=1';
            });
    }

    // 'touchstart' added alongside the original set so a phone/tablet user
    // scrolling or tapping without ever firing a 'mousemove' still counts as
    // active.
    ['mousemove', 'keydown', 'scroll', 'click', 'touchstart'].forEach(evt => {
        document.addEventListener(evt, resetTimer, { passive: true });
    });

    resetTimer();
})();
