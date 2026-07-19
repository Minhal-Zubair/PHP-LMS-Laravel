<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "user_db");

$message = "";
$message_type = "";

if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_login'])) {
    $_SESSION['user_id'] = $_COOKIE['user_id'];
    $_SESSION['username'] = $_COOKIE['user_login'];
    $_SESSION['fullname'] = $_COOKIE['user_name'];
    header("Location: dashboard.php");
}

if (isset($_POST['login_btn'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // 1. Search for the user by username
    $query = "SELECT * FROM users WHERE username='$username'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        // 2. Verify the hashed password
        if (password_verify($password, $row['password'])) {
            // Success! Store user data in Session variables
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['fullname'] = $row['fullname'];

            // --- SET COOKIES (If "Remember Me" is checked) ---
            if (isset($_POST['remember'])) {
                // Expire in 30 days (86400 * 30)
                setcookie("user_id", $row['id'], time() + (86400 * 30), "/");
                setcookie("user_login", $row['username'], time() + (86400 * 30), "/");
                setcookie("user_name", $row['fullname'], time() + (86400 * 30), "/");
            }

            // Redirect to a dashboard or home page
            header("Location: index.php");
            exit();
        } else {
            $message = "Incorrect password. Please try again.";
            $message_type = "error";
        }
    } else {
        $message = "Username not found.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | EduManage System</title>

    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #4e73df;
            --dark-color: #2c3e50;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f8f9fc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navigation */
        nav {
            background: white;
            padding: 15px 10%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        nav .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }

        nav .nav-links a {
            color: var(--dark-color);
            text-decoration: none;
            margin-left: 25px;
            font-weight: 500;
        }

        /* Login Container */
        .login-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
                url('https://img.magnific.com/free-vector/geometric-science-education-background-vector-gradient-blue-digital-remix_53876-125993.jpg?semt=ais_hybrid&w=740&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .login-card {
            background: white;
            width: 100%;
            max-width: 450px;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .login-card i.main-icon {
            font-size: 50px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .login-card h2 {
            color: var(--dark-color);
            margin-bottom: 10px;
            font-size: 24px;
        }

        .login-card p {
            color: #777;
            font-size: 14px;
            margin-bottom: 30px;
        }

        /* Form Styling */
        .input-group {
            margin-bottom: 20px;
            text-align: left;
            position: relative;
        }

        .input-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark-color);
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 38px;
            color: #ccc;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            transition: 0.3s;
        }

        .input-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 8px rgba(78, 115, 223, 0.2);
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .login-btn:hover {
            background: #2e59d9;
            transform: translateY(-2px);
        }

        /* Error Message */
        .alert {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #f5c6cb;
        }

        .register-link {
            margin-top: 25px;
            font-size: 14px;
            color: #666;
        }

        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        footer {
            background: white;
            text-align: center;
            padding: 20px;
            font-size: 13px;
            color: #888;
            border-top: 1px solid #eee;
        }
    </style>
</head>

<body>

    <nav>
        <a href="index.php" class="logo"><i class="fas fa-graduation-cap"></i> EduManage</a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="register.php">Register</a>
        </div>
    </nav>

    <section class="login-section">
        <div class="login-card">
            <i class="fas fa-user-circle main-icon"></i>
            <h2>Welcome Back</h2>
            <p>Enter your credentials to access your account</p>

            <!-- PHP Alert Message -->
            <?php if ($message != ""): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="input-group">
                    <label>Username</label>
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Enter username" required>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Enter password" required>
                </div>

                <button type="submit" name="login_btn" class="login-btn">
                    Login
                </button>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember" style="font-size: 13px; color: var(--text-muted);">Remember Me</label>
                </div>
            </form>

            <p class="register-link">
                Don't have an account? <a href="register.php">Register Now</a>
            </p>
        </div>
    </section>

    <footer>
        &copy; <?php echo date("Y"); ?> EduManage | Secure Student Portal
    </footer>

</body>

</html>