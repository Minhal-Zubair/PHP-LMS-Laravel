<?php
session_start();

// 1. Clear Session
session_unset();
session_destroy();

// 2. Clear Cookies (Set expiration time to past)
if (isset($_COOKIE['user_id'])) {
    setcookie("user_id", "", time() - 3600, "/");
    setcookie("user_login", "", time() - 3600, "/");
    setcookie("user_name", "", time() - 3600, "/");
}

// 3. Redirect
header("Location: login.php");
exit();
?>