<?php
// Dashboard and Archive were merged into a single page (dashboard.php) with
// an Active/Archived toggle. This redirect exists so links/bookmarks
// pointing here (including this app's own nav bars on other pages) keep
// working without needing every page updated individually.
require_once '../auth/session_check.php';
require_once '../path.php';
header('Location: ' . BASE_URL . '/pages/dashboard.php?view=archived');
exit;
