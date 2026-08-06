<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduManage | Professional Student Management System</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <style>
        :root {
    --primary: #e56a31;
    --secondary: #d48b4b;
    --dark: #b3492b;
    --light: #F7FAF8;
    --accent: #df965f;
    --text: #555555;
    --white: #FFFFFF;
}

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background-color: var(--light);
            color: var(--text);
            overflow-x: hidden;
        }

        /* --- Navigation --- */
        nav {
            background-color: transparent;
            padding: 20px 10%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 2000;
            transition: 0.4s;
        }

        nav.scrolled {
            background-color: var(--white);
            padding: 12px 10%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        nav .logo {
            font-size: 26px;
            font-weight: 700;
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.4s;
        }

        nav.scrolled .logo {
            color: var(--primary);
        }

        nav .nav-links a {
            color: black;
            text-decoration: none;
            margin-left: 30px;
            font-weight: 500;
            transition: 0.3s;
        }

        

        nav.scrolled .nav-links a {
            color: var(--dark);
        }

        nav .nav-links a:hover {
            color: black !important;
        }

        nav .btn-login {
            background: var(--accent);
            color: var(--dark) !important;
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
        }



        /* --- Hero Section --- */
        .hero {
            background: linear-gradient(135deg, rgba(223, 112, 78, 0.9), rgba(34, 74, 190, 0.8)),
                url('https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
            color: var(--white);
            padding: 0 10%;
        }

        .hero-content {
            max-width: 700px;
        }

        .hero-content h1 {
            font-size: 4rem;
            line-height: 1.1;
            margin-bottom: 25px;
            font-weight: 700;
        }

        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 35px;
            opacity: 0.9;
        }

        .btn-group {
            display: flex;
            gap: 20px;
        }

        .btn-main {
            background: var(--accent);
            color: var(--dark);
            padding: 15px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            transition: 0.3s;
        }

        .btn-outline {
            border: 2px solid var(--white);
            color: var(--white);
            padding: 15px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            transition: 0.3s;
        }

        .btn-main:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        /* --- Stats Section --- */
        .stats {
            display: flex;
            justify-content: space-around;
            padding: 60px 5%;
            background: var(--white);
            width: 85%;
            margin: -80px auto 0;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 10;
        }

        .stat-item {
            text-align: center;
        }

        .stat-item h3 {
            font-size: 2.5rem;
            color: var(--primary);
            font-weight: 700;
        }

        .stat-item p {
            font-weight: 500;
            color: var(--dark);
        }

        /* --- Features Section --- */
        .section-padding {
            padding: 100px 10%;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-header h2 {
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 15px;
        }

        .section-header .underline {
            width: 80px;
            height: 5px;
            background: var(--primary);
            margin: 0 auto;
            border-radius: 5px;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: var(--white);
            padding: 50px 40px;
            border-radius: 20px;
            transition: 0.4s;
            border-bottom: 5px solid transparent;
        }

        .feature-card:hover {
            transform: translateY(-15px);
            border-color: var(--primary);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
        }

        .feature-card i {
            font-size: 3.5rem;
            color: var(--primary);
            margin-bottom: 25px;
        }

        .feature-card h3 {
            margin-bottom: 15px;
            color: var(--dark);
        }

        /* --- Modules Section (New) --- */
        .modules-bg {
            background-color: var(--dark);
            color: var(--white);
        }

        .module-list {
            list-style: none;
            margin-top: 20px;
        }

        .module-list li {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .module-list li i {
            color: var(--accent);
        }

        /* --- Contact Section --- */
        .contact-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.05);
        }

        .contact-info {
            background: var(--primary);
            color: var(--white);
            padding: 60px;
        }

        .contact-form {
            padding: 60px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
        }

        /* --- Footer --- */
        footer {
            background-color: #1a1e21;
            color: var(--white);
            padding: 80px 10% 30px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 40px;
            margin-bottom: 50px;
        }

        .footer-col h4 {
            margin-bottom: 25px;
            font-size: 1.2rem;
            color: var(--accent);
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 12px;
        }

        .footer-col ul li a {
            color: #bbb;
            text-decoration: none;
            transition: 0.3s;
        }

        .footer-col ul li a:hover {
            color: var(--white);
            padding-left: 5px;
        }

        .copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid #333;
            color: #777;
            font-size: 0.9rem;
        }

        /* --- Map Section --- */
        .map-section {
            padding: 0 10% 100px;
            /* Reduced top padding since it follows Contact */
        }

        .map-wrapper {
            width: 100%;
            height: 450px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            border: 8px solid var(--white);
            /* Gives it a nice "framed" look */
        }

        .map-wrapper iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }

        /* --- Pure CSS Carousel (No JS) --- */
        .carousel-container {
            width: 100%;
            max-width: 1000px;
            margin: 50px auto;
            text-align: center;
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }

        /* Hide Radio Buttons */
        .carousel-container input[type="radio"] {
            display: none;
        }

        .slides-wrapper {
            display: flex;
            width: 500%;
            /* Change this based on number of slides (5 slides = 500%) */
            transition: transform 0.6s cubic-bezier(0.77, 0, 0.175, 1);
        }

        .slide-item {
            width: 20%;
            /* 100% divided by number of slides */
            height: 500px;
            position: relative;
        }

        .slide-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .slide-caption {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 40px;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
            color: white;
            text-align: left;
        }

        /* Navigation Bullets */
        .carousel-nav {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
        }

        .carousel-nav label {
            width: 12px;
            height: 12px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            cursor: pointer;
            transition: 0.3s;
        }

        /* --- THE LOGIC: CSS Pseudo-selectors to move the slides --- */
        #slide1:checked~.slides-wrapper {
            transform: translateX(0%);
        }

        #slide2:checked~.slides-wrapper {
            transform: translateX(-20%);
        }

        #slide3:checked~.slides-wrapper {
            transform: translateX(-40%);
        }

        #slide4:checked~.slides-wrapper {
            transform: translateX(-60%);
        }

        #slide5:checked~.slides-wrapper {
            transform: translateX(-80%);
        }

        /* Active Bullet Styling */
        #slide1:checked~.carousel-nav label[for="slide1"],
        #slide2:checked~.carousel-nav label[for="slide2"],
        #slide3:checked~.carousel-nav label[for="slide3"],
        #slide4:checked~.carousel-nav label[for="slide4"],
        #slide5:checked~.carousel-nav label[for="slide5"] {
            background: var(--accent);
            transform: scale(1.3);
        }

        /* --- Mobile Optimization --- */
        @media (max-width: 992px) {
            .hero-content h1 {
                font-size: 3rem;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
            }

            .contact-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- Header / Nav -->
    <nav id="navbar">
        <a href="#" class="logo"><i class="fas fa-graduation-cap"></i> EduManage</a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="#features">Features</a>
            <a href="#modules">Modules</a>
            <a href="dashboard.php">Dashboard</a>
            <a href="#contact">Contact</a>
            <a href="login.php" class="btn-login">Portal Login</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content" data-aos="fade-right">
            <h1>The Future of School Management.</h1>
            <p>Empower your institution with a data-driven platform designed to streamline administration, engage students, and simplify complex academic workflows.</p>
            <div class="btn-group">
                <a href="register.php" class="btn-main">Get Started</a>
                <a href="#features" class="btn-outline">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="stats" data-aos="zoom-in">
        <div class="stat-item">
            <h3>12k+</h3>
            <p>Active Students</p>
        </div>
        <div class="stat-item">
            <h3>450+</h3>
            <p>Institutions</p>
        </div>
        <div class="stat-item">
            <h3>99.9%</h3>
            <p>Uptime Record</p>
        </div>
        <div class="stat-item">
            <h3>24/7</h3>
            <p>Expert Support</p>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section-padding" id="features">
        <div class="section-header" data-aos="fade-up">
            <h2>Why Choose EduManage?</h2>
            <div class="underline"></div>
        </div>

        <div class="feature-grid">
            <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                <i class="fas fa-bolt"></i>
                <h3>Real-time Analytics</h3>
                <p>Track attendance, grades, and performance indicators instantly with our live dashboard systems.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                <i class="fas fa-lock"></i>
                <h3>Bank-Grade Security</h3>
                <p>Your data is encrypted with SHA-256 protocols and stored securely in redundant cloud servers.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                <i class="fas fa-mobile-alt"></i>
                <h3>Mobile Friendly</h3>
                <p>Access the portal from any device. Our responsive design ensures a smooth experience on phones.</p>
            </div>
        </div>
    </section>

    <!-- Modules Section -->
    <section class="section-padding modules-bg" id="modules">
        <div class="about">
            <div class="about-img" data-aos="fade-right">
                <img src="https://images.unsplash.com/photo-1531482615713-2afd69097998?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" alt="Dashboard Preview" style="width:100%; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
            </div>
            <div class="about-text" data-aos="fade-left" style="padding-left: 50px;">
                <h2 style="color: var(--accent);">Comprehensive Modules</h2>
                <p>Everything you need to run a modern educational institution in one integrated software package.</p>
                <ul class="module-list">
                    <li><i class="fas fa-check-circle"></i> Automated Attendance via QR or Portal</li>
                    <li><i class="fas fa-check-circle"></i> Dynamic Gradebook & Report Card Generator</li>
                    <li><i class="fas fa-check-circle"></i> Online Fee Management with Payment Gateways</li>
                    <li><i class="fas fa-check-circle"></i> Timetable & Resource Scheduling</li>
                    <li><i class="fas fa-check-circle"></i> Parent-Teacher Communication Bridge</li>
                </ul>
                <br>
                <a href="register.php" class="btn-main">Request a Demo</a>
            </div>
        </div>
    </section>

    <!-- Carousel Section -->
    <section class="section-padding" style="background: var(--white);">
        <div class="section-header">
            <h2>Life at EduManage</h2>
            <div class="underline"></div>
        </div>

        <?php
        // Define slides in a PHP Array
        $my_slides = [
            ["img" => "https://images.unsplash.com/photo-1523240795612-9a054b0db644", "title" => "Collaborative Learning", "desc" => "Students working together on tech projects."],
            ["img" => "https://images.unsplash.com/photo-1517245386807-bb43f82c33c4", "title" => "Digital Workshops", "desc" => "Mastering modern software in our labs."],
            ["img" => "https://images.unsplash.com/photo-1522202176988-66273c2fd55f", "title" => "Research Center", "desc" => "Access to global libraries and data."],
            ["img" => "https://images.unsplash.com/photo-1552664730-d307ca884978", "title" => "Industry Mentors", "desc" => "Weekly seminars from Silicon Valley experts."],
            ["img" => "https://images.unsplash.com/photo-1516321318423-f06f85e504b3", "title" => "Innovation Hub", "desc" => "Where student ideas become real startups."]
        ];
        ?>

        <div class="carousel-container">
            <!-- PHP generates the radio buttons -->
            <?php foreach ($my_slides as $index => $slide): ?>
                <input type="radio" name="carousel-control" id="slide<?php echo $index + 1; ?>" <?php echo $index == 0 ? 'checked' : ''; ?>>
            <?php endforeach; ?>

            <!-- PHP generates the slides -->
            <div class="slides-wrapper">
                <?php foreach ($my_slides as $slide): ?>
                    <div class="slide-item">
                        <img src="<?php echo $slide['img']; ?>?auto=format&fit=crop&w=1000&q=80" alt="Campus">
                        <div class="slide-caption">
                            <h4><?php echo $slide['title']; ?></h4>
                            <p><?php echo $slide['desc']; ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- PHP generates the navigation bullets -->
            <div class="carousel-nav">
                <?php foreach ($my_slides as $index => $slide): ?>
                    <label for="slide<?php echo $index + 1; ?>"></label>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="section-padding" id="contact">
        <div class="section-header">
            <h2>Get In Touch</h2>
            <p>Have questions? Our team is here to help you scale.</p>
        </div>

        <div class="contact-container" data-aos="flip-up">
            <div class="contact-info">
                <h3>Contact Information</h3>
                <p style="margin: 20px 0;">Reach out to us via any of the channels below.</p>
                <div style="margin-top: 40px;">
                    <p><i class="fas fa-map-marker-alt"></i> 123 Tech Avenue, Silicon Valley, CA</p><br>
                    <p><i class="fas fa-phone"></i> +1 (555) 000-1234</p><br>
                    <p><i class="fas fa-envelope"></i> support@edumanage.com</p>
                </div>
            </div>
            <div class="contact-form">
                <form>
                    <div class="form-group">
                        <label>Your Name</label>
                        <input type="text" placeholder="Enter name">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" placeholder="Enter email">
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea rows="4" placeholder="How can we help?"></textarea>
                    </div>
                    <button class="btn-main" style="border:none; width:100%; cursor:pointer;">Send Message</button>
                </form>
            </div>
        </div>
    </section>
    <!-- Location Map Section -->
    <section class="map-section">
        <div class="section-header" data-aos="fade-up">
            <h2>Visit Our Campus</h2>
            <div class="underline"></div>
            <p style="margin-top: 15px;">Conveniently located in the heart of the technology district.</p>
        </div>

        <div class="map-wrapper" data-aos="zoom-in-up">
            <!-- Google Maps Embed Iframe -->
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3153.8354345093747!2d-122.4194155!3d37.7749295!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x80859a6d00690021%3A0x4a501367f076adff!2sSan%20Francisco%2C%20CA!5e0!3m2!1sen!2sus!4v1670000000000!5m2!1sen!2sus"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <h2 style="color:white; margin-bottom:20px;"><i class="fas fa-graduation-cap"></i> EduManage</h2>
                <p style="color: #bbb;">The world's leading student management software. Empowering over 450+ schools globally with modern tools.</p>
                <div class="social-links" style="margin-top: 20px; text-align: left;">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Our Team</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Support</h4>
                <ul>
                    <li><a href="#">Documentation</a></li>
                    <li><a href="#">Video Tutorials</a></li>
                    <li><a href="#">System Status</a></li>
                    <li><a href="#">Help Desk</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Newsletter</h4>
                <p style="color: #bbb; font-size: 0.9rem; margin-bottom: 15px;">Get updates on new features and educational tips.</p>
                <div class="form-group">
                    <input type="email" placeholder="Your Email" style="background: #333; border: none; color: white;">
                    <button class="btn-main" style="padding: 10px; width: 100%; margin-top: 10px; border:none;">Subscribe</button>
                </div>
            </div>
        </div>


        <div class="copyright">
            &copy; <?php echo date("Y"); ?> EduManage Pro | All Rights Reserved.
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize Animations
        AOS.init({
            duration: 1000,
            once: true
        });

        // Navbar Scroll Effect
        window.addEventListener('scroll', function() {
            const nav = document.getElementById('navbar');
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });
    </script>

</body>

</html>