<?php
/**
 * Local DB credentials for development.
 *
 * 1. Copy this file to `db_config.local.php` (same folder).
 * 2. Fill in your real local values below.
 * 3. Never commit `db_config.local.php` — add it to .gitignore.
 *
 * db.php automatically loads db_config.local.php if it exists, so once
 * this is in place you never need to set environment variables by hand
 * again — works the same whether you run `php -S` or use XAMPP/Apache.
 */

putenv('DB_HOST=127.0.0.1');
putenv('DB_USER=root');
putenv('DB_PASS=');
putenv('DB_NAME=user_db');