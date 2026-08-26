<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

// Invalidate the remember-me token server-side too, not just the cookie —
// otherwise a copy of the old cookie would still work after "logout".
if (function_exists('clear_remember_token')) {
    clear_remember_token($conn);
}

session_unset();
session_destroy();

header("Location: login.php");
exit();