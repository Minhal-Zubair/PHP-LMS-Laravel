<?php
/**
 * Shared security helpers used across the app.
 * Require this AFTER session_start() and AFTER db.php (needs $conn).
 */

// ---------------------------------------------------------------------
// Output escaping shortcut
// ---------------------------------------------------------------------
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ---------------------------------------------------------------------
// Course progress tracking
// ---------------------------------------------------------------------

/**
 * Recalculates a course's progress % from its linked tasks and saves it.
 * If the course has zero linked tasks, this does nothing — progress stays
 * whatever it was (manual-set courses aren't touched by this).
 * Call this any time a task's course_id, status, or existence changes.
 */
function recalc_course_progress(mysqli $conn, int $courseId): void
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total, SUM(status = 'Completed') AS done
         FROM tasks WHERE course_id = ?"
    );
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ((int) $row['total'] === 0) {
        return; // no linked tasks — leave whatever progress value is set manually
    }

    $progress = (int) round(((int) $row['done'] / (int) $row['total']) * 100);

    $update = $conn->prepare("UPDATE courses SET progress = ? WHERE id = ?");
    $update->bind_param('ii', $progress, $courseId);
    $update->execute();
    $update->close();
}

/**
 * True if the course has at least one linked task — used to decide whether
 * progress is auto-calculated (has tasks) or manually settable (doesn't).
 */
function course_has_linked_tasks(mysqli $conn, int $courseId): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM tasks WHERE course_id = ? LIMIT 1");
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $has = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $has;
}

// ---------------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Call at the top of every POST handler. Kills the request with a 403
 * if the token is missing or wrong, instead of silently continuing.
 */
function csrf_verify(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';

    if ($submitted === '' || $expected === '' || !hash_equals($expected, $submitted)) {
        http_response_code(403);
        die('Security check failed (invalid or expired form token). Please go back and try again.');
    }
}

// ---------------------------------------------------------------------
// Safe "open in new tab" link validation for user-supplied URLs
// (course links, etc.) — blocks javascript:, data:, vbscript: and similar.
// ---------------------------------------------------------------------
function safe_url(?string $url): string
{
    $url = trim((string) $url);
    if ($url === '') {
        return '#';
    }

    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

    // Allow only http/https, or scheme-less values (treated as relative/https)
    if ($scheme !== '' && !in_array($scheme, ['http', 'https'], true)) {
        return '#';
    }

    return $url;
}

// ---------------------------------------------------------------------
// Field limits — kept in sync with `users` table column sizes so
// validation errors happen with a friendly message instead of a DB
// truncation/strict-mode failure. See user_db.sql for the source of truth.
// ---------------------------------------------------------------------
const MAX_FULLNAME_LEN = 100;
const MAX_EMAIL_LEN = 100;
const MAX_USERNAME_LEN = 50;
const MAX_PHONE_LEN = 20;
// bcrypt (PASSWORD_DEFAULT) silently ignores bytes beyond 72 — cap input
// so a long password doesn't produce a hash of a truncated string with
// no warning to the user.
const MAX_PASSWORD_LEN = 72;

// ---------------------------------------------------------------------
// Password reset tokens (single-use, short-lived, hashed at rest)
// Requires the `password_resets` table — see user_db.sql.
// ---------------------------------------------------------------------
const PASSWORD_RESET_MINUTES = 60;

/**
 * Issues a new reset token for the given user, invalidating any previous
 * outstanding tokens for that user first. Returns the RAW token — only
 * ever put this in the reset link/email, never store it anywhere.
 */
function issue_password_reset_token(mysqli $conn, int $userId): string
{
    $del = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $del->bind_param('i', $userId);
    $del->execute();
    $del->close();

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);

    // Expiry is computed by MySQL itself (DATE_ADD(NOW(), ...)) rather than
    // in PHP, and later compared with MySQL's NOW() too (see
    // verify_password_reset_token). If this were computed in PHP with
    // date()/time() instead, a mismatch between PHP's and MySQL's
    // configured timezones (very common — e.g. PHP defaulting to UTC while
    // MySQL's NOW() uses the OS's local time) could make a token look
    // expired the moment it's created.
    $stmt = $conn->prepare(
        "INSERT INTO password_resets (user_id, token_hash, expires_at)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))"
    );
    $minutes = PASSWORD_RESET_MINUTES;
    $stmt->bind_param('isi', $userId, $tokenHash, $minutes);
    $stmt->execute();
    $stmt->close();

    return $token;
}

/**
 * Looks up the user_id for a raw token, if it's valid and not expired.
 * Returns null otherwise. Does NOT consume the token — call
 * consume_password_reset_token() only after the new password is
 * successfully saved.
 */
function verify_password_reset_token(mysqli $conn, string $token): ?int
{
    if ($token === '') {
        return null;
    }

    $tokenHash = hash('sha256', $token);
    $stmt = $conn->prepare(
        "SELECT user_id FROM password_resets WHERE token_hash = ? AND expires_at > NOW()"
    );
    $stmt->bind_param('s', $tokenHash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ? (int) $row['user_id'] : null;
}

function consume_password_reset_token(mysqli $conn, string $token): void
{
    $tokenHash = hash('sha256', $token);
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE token_hash = ?");
    $stmt->bind_param('s', $tokenHash);
    $stmt->execute();
    $stmt->close();
}

/**
 * Sends the reset link. Falls back to returning the link (instead of
 * emailing it) when not running with APP_ENV=production and/or PHP's
 * mail() isn't configured — most local/XAMPP setups have no working
 * mail transport, so this keeps the flow testable without real email.
 * Wire up a real mailer (PHPMailer + SMTP, etc.) for production use.
 */
function send_password_reset_email(string $toEmail, string $resetLink): bool
{
    $isProduction = getenv('APP_ENV') === 'production';

    if ($isProduction) {
        $subject = 'Reset your EduManage password';
        $body = "Click the link below to reset your password. This link expires in "
            . PASSWORD_RESET_MINUTES . " minutes.\n\n" . $resetLink
            . "\n\nIf you didn't request this, you can ignore this email.";
        $headers = 'Content-Type: text/plain; charset=UTF-8';
        return @mail($toEmail, $subject, $body, $headers);
    }

    // Local/dev fallback: don't attempt mail() (usually unconfigured on
    // XAMPP/localhost and will just fail or hang) — the caller displays
    // the link on-screen instead.
    return false;
}


// Replaces the old "trust whatever is in the cookie" approach.
// Requires the `remember_tokens` table — see user_db.sql.
// ---------------------------------------------------------------------
const REMEMBER_COOKIE = 'remember_me';
const REMEMBER_DAYS = 30;

function issue_remember_token(mysqli $conn, int $userId): void
{
    $selector = bin2hex(random_bytes(9));
    $validator = bin2hex(random_bytes(32));
    $validatorHash = hash('sha256', $validator);

    // Expiry computed by MySQL (DATE_ADD/NOW), same reasoning as
    // issue_password_reset_token() above — avoids PHP/MySQL timezone drift.
    $stmt = $conn->prepare(
        "INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at)
         VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY))"
    );
    $days = REMEMBER_DAYS;
    $stmt->bind_param('issi', $userId, $selector, $validatorHash, $days);
    $stmt->execute();
    $stmt->close();

    setcookie(REMEMBER_COOKIE, $selector . ':' . $validator, [
        'expires' => time() + REMEMBER_DAYS * 86400,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true, // enable once served over HTTPS
    ]);
}

/**
 * Verifies the remember-me cookie against the DB. Returns the user row
 * (array) on success, or null if the cookie is missing/invalid/expired.
 * On success it also ROTATES the token (old one is deleted, a new one
 * issued) so a stolen cookie can't be replayed indefinitely.
 */
function verify_remember_token(mysqli $conn): ?array
{
    if (empty($_COOKIE[REMEMBER_COOKIE]) || strpos($_COOKIE[REMEMBER_COOKIE], ':') === false) {
        return null;
    }

    [$selector, $validator] = explode(':', $_COOKIE[REMEMBER_COOKIE], 2);

    $stmt = $conn->prepare(
        "SELECT rt.user_id, rt.validator_hash, u.username, u.fullname
         FROM remember_tokens rt
         JOIN users u ON u.id = rt.user_id
         WHERE rt.selector = ? AND rt.expires_at > NOW()"
    );
    $stmt->bind_param('s', $selector);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        clear_remember_token($conn);
        return null;
    }

    if (!hash_equals($row['validator_hash'], hash('sha256', $validator))) {
        // Mismatched validator for a known selector = possible theft.
        // Invalidate every remembered session for this user to be safe.
        $del = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        $del->bind_param('i', $row['user_id']);
        $del->execute();
        $del->close();
        clear_remember_token($conn);
        return null;
    }

    // Rotate: delete the used token, issue a fresh one.
    $del = $conn->prepare("DELETE FROM remember_tokens WHERE selector = ?");
    $del->bind_param('s', $selector);
    $del->execute();
    $del->close();
    issue_remember_token($conn, (int) $row['user_id']);

    return [
        'id' => (int) $row['user_id'],
        'username' => $row['username'],
        'fullname' => $row['fullname'],
    ];
}

function clear_remember_token(?mysqli $conn = null): void
{
    if ($conn && !empty($_COOKIE[REMEMBER_COOKIE]) && strpos($_COOKIE[REMEMBER_COOKIE], ':') !== false) {
        [$selector] = explode(':', $_COOKIE[REMEMBER_COOKIE], 2);
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE selector = ?");
        $stmt->bind_param('s', $selector);
        $stmt->execute();
        $stmt->close();
    }

    setcookie(REMEMBER_COOKIE, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Invalidates EVERY remembered session for a user, on every device —
 * not just the current cookie. Call this after a password reset/change
 * so a stolen old password can't be paired with a still-valid
 * remember-me cookie to stay logged in elsewhere.
 */
function invalidate_all_remember_tokens(mysqli $conn, int $userId): void
{
    $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
}