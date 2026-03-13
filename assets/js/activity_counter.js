// (function() {
//     const INACTIVITY_LIMIT = 15 * 60 * 1000; // 15 minutes
//     let inactivityTimer;

//     function resetTimer() {
//         clearTimeout(inactivityTimer);
//         inactivityTimer = setTimeout(logoutUser, INACTIVITY_LIMIT);
//     }

//     function logoutUser() {
//         fetch(BASE_URL + '/auth/logout.php')
//             .finally(() => {
//                 window.location.href = BASE_URL + '/?timeout=1';
//             });
//     }

//     ['mousemove', 'keydown', 'scroll', 'click'].forEach(evt => {
//         document.addEventListener(evt, resetTimer);
//     });

//     resetTimer();
// })();