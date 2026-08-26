<?php
/**
 * TEMPORARY diagnostic — confirms whether PHP and MySQL disagree about the
 * current time (the cause of reset links looking "expired" immediately).
 *
 * Visit http://localhost:8000/tz_check.php once, read the output, then
 * DELETE this file — it's not something to leave sitting in a real project.
 */
require_once __DIR__ . '/db.php';

echo "<pre>";
echo "PHP time (date()):        " . date('Y-m-d H:i:s') . " (timezone: " . date_default_timezone_get() . ")\n";

$result = mysqli_query($conn, "SELECT NOW() AS mysql_now, @@session.time_zone AS tz, @@global.time_zone AS global_tz");
$row = mysqli_fetch_assoc($result);
echo "MySQL time (NOW()):       " . $row['mysql_now'] . "\n";
echo "MySQL session time_zone:  " . $row['tz'] . "\n";
echo "MySQL global time_zone:   " . $row['global_tz'] . "\n";

$php_ts = strtotime(date('Y-m-d H:i:s'));
$mysql_ts = strtotime($row['mysql_now']);
$diff_minutes = round(($mysql_ts - $php_ts) / 60);

echo "\nDifference (MySQL - PHP): {$diff_minutes} minute(s)\n";

if (abs($diff_minutes) >= 2) {
    echo "\n⚠️  These disagree by {$diff_minutes} minutes — THIS is why reset/remember\n";
    echo "   tokens looked expired immediately. The fix already applied (computing\n";
    echo "   expiry with MySQL's DATE_ADD/NOW() instead of PHP's date()) resolves\n";
    echo "   this regardless of the mismatch, so no further action is needed —\n";
    echo "   this is just confirming the diagnosis.\n";
} else {
    echo "\n✅ Times agree closely — timezone mismatch was not the (or not the only) cause.\n";
    echo "   If reset links still fail after the fix, something else is going on —\n";
    echo "   report back what you see.\n";
}
echo "</pre>";