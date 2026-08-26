# Sets the DB environment variables and starts the PHP dev server.
# Usage: from the project root, run:  .\run.ps1

$env:DB_HOST = "127.0.0.1"
$env:DB_USER = "root"
$env:DB_PASS = ""
$env:DB_NAME = "user_db"

Write-Host "Starting server with DB_HOST=$env:DB_HOST DB_USER=$env:DB_USER DB_NAME=$env:DB_NAME"
php -S localhost:8000