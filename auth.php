<?php
session_start();

// If Session doesn't exist, check for Cookies
if (!isset($_SESSION['user_id'])) {
    if (isset($_COOKIE['user_id']) && isset($_COOKIE['user_login'])) {
        // Re-establish session from cookies
        $_SESSION['user_id'] = $_COOKIE['user_id'];
        $_SESSION['username'] = $_COOKIE['user_login'];
        $_SESSION['fullname'] = $_COOKIE['user_name'];
    } else {
        // Neither session nor cookie exists - Boot them to login
        header("Location: login.php");
        exit();
    }
}
?>