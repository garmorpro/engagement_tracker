(function() {
    const INACTIVITY_LIMIT = 15 * 60 * 1000; // 15 minutes in milliseconds
    let inactivityTimer;

    function resetTimer() {
        clearTimeout(inactivityTimer);
        inactivityTimer = setTimeout(logoutUser, INACTIVITY_LIMIT);
    }

    function logoutUser() {
        // Call server to destroy session
        fetch('<?= BASE_URL ?>/auth/logout.php')
            .finally(() => {
                // Redirect to login page with timeout flag
                window.location.href = '<?= BASE_URL ?>/?timeout=1';
            });
    }

    // Reset timer on user activity
    ['mousemove', 'keydown', 'scroll', 'click'].forEach(evt => {
        document.addEventListener(evt, resetTimer);
    });

    // Start timer initially
    resetTimer();
})();