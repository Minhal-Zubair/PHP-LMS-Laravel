<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join EduManage | Student Registration</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #2e59d9;
            --dark-color: #2c3e50;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --light-gray: #f8f9fc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: var(--light-gray);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Navigation (Consistent with Home) */
        nav {
            background-color: white;
            padding: 15px 10%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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

        /* Registration Section */
        .register-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            background: url('https://img.magnific.com/free-vector/education-technology-futuristic-background-vector-gradient-blue-digital-remix_53876-114092.jpg?semt=ais_hybrid&w=740&q=80'), var(--bg-gradient);
        }

        .register-container {
            background: white;
            width: 100%;
            max-width: 900px;
            display: flex;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }

        /* Left Side Image/Info */
        .register-info {
            flex: 1;
            background: var(--primary-color);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
            background-image: linear-gradient(rgba(78, 115, 223, 0.9), rgba(78, 115, 223, 0.9)), 
                              url('https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80');
            background-size: cover;
        }

        .register-info h2 { font-size: 2rem; margin-bottom: 20px; }
        .register-info p { font-size: 1rem; opacity: 0.9; }

        /* Right Side Form */
        .register-form-box {
            flex: 1.5;
            padding: 40px;
        }

        .register-form-box h2 {
            color: var(--dark-color);
            margin-bottom: 10px;
        }

        .register-form-box p {
            font-size: 0.9rem;
            color: #888;
            margin-bottom: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .input-group {
            margin-bottom: 15px;
            position: relative;
        }

        .input-group.full-width {
            grid-column: span 2;
        }

        .input-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark-color);
        }

        .input-group i {
            position: absolute;
            left: 12px;
            top: 38px;
            color: var(--primary-color);
        }

        .input-group input, 
        .input-group select, 
        .input-group textarea {
            width: 100%;
            padding: 10px 10px 10px 35px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            transition: 0.3s;
            background: #f9f9f9;
        }

        .input-group input:focus {
            border-color: var(--primary-color);
            background: #fff;
            box-shadow: 0 0 8px rgba(78, 115, 223, 0.2);
        }

        button {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        button:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        /* Footer */
        footer {
            background: white;
            text-align: center;
            padding: 20px;
            font-size: 0.9rem;
            color: #777;
            border-top: 1px solid #ddd;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .register-container { flex-direction: column; }
            .register-info { display: none; }
            .form-grid { grid-template-columns: 1fr; }
            .input-group.full-width { grid-column: span 1; }
        }
    </style>
</head>

<body>

<nav>
    <a href="index.php" class="logo"><i class="fas fa-graduation-cap"></i> EduManage</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
    </div>
</nav>

<section class="register-section">
    <div class="register-container">
        <!-- Left Banner -->
        <div class="register-info">
            <i class="fas fa-user-plus fa-4x" style="margin-bottom: 20px;"></i>
            <h2>Join Our Community</h2>
            <p>Create an account to manage your academic profile, track your grades, and connect with faculty.</p>
        </div>

        <!-- Right Form -->
        <div class="register-form-box">
            <h2>Create Your Account</h2>
            <p>Please enter your details to register.</p>

            <form action="" method="POST" id="registrationForm">
                <div class="form-grid">
                    <!-- Full Name -->
                    <div class="input-group">
                        <label>Full Name</label>
                        <i class="fas fa-user"></i>
                        <input type="text" name="fullname" placeholder="John Doe" required>
                    </div>

                    <!-- Email -->
                    <div class="input-group">
                        <label>Email Address</label>
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="name@example.com" required>
                    </div>

                    <!-- Username -->
                    <div class="input-group">
                        <label>Username</label>
                        <i class="fas fa-at"></i>
                        <input type="text" name="username" placeholder="johndoe123" required>
                    </div>

                    <!-- Phone -->
                    <div class="input-group">
                        <label>Phone Number</label>
                        <i class="fas fa-phone"></i>
                        <input type="text" name="phone" placeholder="03XXXXXXXXX">
                    </div>

                    <!-- Password -->
                    <div class="input-group">
                        <label>Password</label>
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="pass" placeholder="••••••••" required>
                    </div>

                    <!-- Confirm Password -->
                    <div class="input-group">
                        <label>Confirm Password</label>
                        <i class="fas fa-check-circle"></i>
                        <input type="password" name="confirm_password" id="confirm_pass" placeholder="••••••••" required>
                    </div>

                    <!-- Gender -->
                    <div class="input-group">
                        <label>Gender</label>
                        <i class="fas fa-venus-mars"></i>
                        <select name="gender" style="padding-left: 35px;">
                            <option value="">Select</option>
                            <option>Male</option>
                            <option>Female</option>
                        </select>
                    </div>

                    <!-- Address -->
                    <div class="input-group full-width">
                        <label>Address</label>
                        <i class="fas fa-map-marker-alt"></i>
                        <textarea name="address" rows="2" placeholder="Street, City, Country" style="padding-left: 35px;"></textarea>
                    </div>
                </div>

                <button type="submit" name="register_btn">
                    <i class="fas fa-paper-plane"></i> Register Now
                </button>
            </form>

            <p class="login-link">
                Already have an account? <a href="login.php">Login Here</a>
            </p>
        </div>
    </div>
</section>

<footer>
    &copy; <?php echo date("Y"); ?> EduManage | All Rights Reserved
</footer>

<?php
// Database Connection
require_once('db.php');

if (isset($_POST['register_btn'])) {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    // Check if passwords match
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    // Check if Username or Email already exists
    $check_user = "SELECT * FROM users WHERE username='$username' OR email='$email'";
    $result = mysqli_query($conn, $check_user);
    if (mysqli_num_rows($result) > 0) {
        echo "<script>alert('Username or Email already taken!'); window.history.back();</script>";
        exit();
    }

    // Hash the password (Security)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert into Database
    $sql = "INSERT INTO users (fullname, email, username, password, gender, phone, address) 
            VALUES ('$fullname', '$email', '$username', '$hashed_password', '$gender', '$phone', '$address')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Registration Successful!'); window.location.href='login.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

</body>
</html>