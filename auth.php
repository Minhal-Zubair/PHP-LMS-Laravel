<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

// If there's no active session, the ONLY way back in is a verified
// remember-me token looked up server-side — never trust cookie values
// directly (the old code did `$_SESSION['user_id'] = $_COOKIE['user_id']`,
// which let anyone log in as any user by just setting a cookie).
if (!isset($_SESSION['user_id'])) {
    $user = function_exists('verify_remember_token') ? verify_remember_token($conn) : null;

    if ($user) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['fullname'] = $user['fullname'];
    } else {
        header("Location: login.php");
        exit();
    }
}