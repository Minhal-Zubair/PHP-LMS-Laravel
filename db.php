<?php

$db_host = getenv('DB_HOST');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_name = getenv('DB_NAME');

if ($db_host === false || $db_user === false || $db_pass === false || $db_name === false) {
    die('Database configuration missing: set DB_HOST, DB_USER, DB_PASS, DB_NAME environment variables.');
}

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}
