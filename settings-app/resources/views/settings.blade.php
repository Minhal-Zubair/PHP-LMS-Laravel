<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduPro | Account Settings</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4e73df;
            --primary-dark: #224abe;
            --accent: #f6c23e;
            --bg: #f8fafd;
            --white: #ffffff;
            --text-dark: #2d3748;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #f6f9fc 0%, #eef2f7 100%);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 1000px;
            display: flex;
            background: var(--white);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            margin: 20px;
        }

        /* Left Side - Promotional/Info */
        .info-panel {
            flex: 1;
            background: linear-gradient(150deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 60px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .info-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://www.transparenttextures.com/patterns/cubes.png');
            opacity: 0.1;
        }

        .info-panel h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            position: relative;
        }

        .info-panel p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
            position: relative;
        }

        .feature-list {
            list-style: none;
            padding: 0;
            margin-top: 30px;
            position: relative;
        }

        .feature-list li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .feature-list i {
            color: var(--accent);
        }

        /* Right Side - Form */
        .form-panel {
            flex: 1.2;
            padding: 60px;
            background: var(--white);
        }

        .form-header {
            margin-bottom: 40px;
        }

        .form-header h2 {
            color: var(--text-dark);
            font-size: 1.8rem;
            margin: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .full-width {
            grid-column: span 2;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 8px;
            color: #718096;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #cbd5e0;
            transition: 0.3s;
        }

        input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #edf2f7;
            border-radius: 12px;
            font-size: 14px;
            transition: 0.3s;
            box-sizing: border-box;
        }

        input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(78, 115, 223, 0.1);
        }

        input:focus+i {
            color: var(--primary);
        }

        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            width: 100%;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
            box-shadow: 0 10px 15px -3px rgba(78, 115, 223, 0.3);
        }

        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(78, 115, 223, 0.4);
        }

        .alert {
            background: #f0fff4;
            color: #2f855a;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 5px solid #48bb78;
            font-weight: 500;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 30px;
            color: #a0aec0;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: 0.3s;
        }

        .back-btn:hover {
            color: var(--primary);
        }

        @media (max-width: 850px) {
            .container {
                flex-direction: column;
            }

            .info-panel {
                padding: 40px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: span 1;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <!-- Info Section -->
        <div class="info-panel">
            <h1>EduPro <span style="color: var(--accent);">Elite</span></h1>
            <p>Your educational journey is unique. Keep your profile updated to get the most out of our advanced learning tools and analytics.</p>
            <ul class="feature-list">
                <li><i class="fas fa-check-circle"></i> Secure Password Encryption</li>
                <li><i class="fas fa-check-circle"></i> Real-time Profile Sync</li>
                <li><i class="fas fa-check-circle"></i> Enhanced Privacy Controls</li>
            </ul>
        </div>

        <!-- Form Section -->
        <div class="form-panel">
            <div class="form-header">
                <h2>Account Settings</h2>
            </div>

            @if (session('status'))
            <div class="alert">
                <i class="fas fa-check-circle"></i> {{ session('status') }}
            </div>
            @endif

            <form action="{{ route('settings.update') }}" method="POST">
                @csrf
                <div class="form-grid">
                    <div class="input-group full-width">
                        <label>FULL NAME</label>
                        <div class="input-wrapper">
                            <input type="text" name="fullname" value="{{ $user->fullname }}" required>
                            <i class="fas fa-user"></i>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>PHONE NUMBER</label>
                        <div class="input-wrapper">
                            <input type="text" name="phone" value="{{ $user->phone }}">
                            <i class="fas fa-phone"></i>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>NEW PASSWORD</label>
                        <div class="input-wrapper">
                            <input type="password" name="password" placeholder="Leave blank to keep current">
                            <i class="fas fa-lock"></i>
                        </div>
                    </div>

                    <div class="input-group full-width">
                        <label>STREET ADDRESS</label>
                        <div class="input-wrapper">
                            <input type="text" name="address" value="{{ $user->address }}">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                    </div>

                    <div class="input-group full-width">
                        <label>EMAIL ADDRESS</label>
                        <div class="input-wrapper">
                            <input type="email" name="email" value="{{ $user->email }}" required>
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Update Profile</button>
            </form>

            <a href="/practice/Project-1/dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Return to Dashboard
            </a>

            <script>
                // This JavaScript approach is the most reliable for hybrid projects
                document.getElementById('backLink').addEventListener('click', function(e) {
                    e.preventDefault();
                    // This goes back to the folder containing 'settings-app'
                    let currentPath = window.location.pathname;
                    let newPath = currentPath.split('/settings-app')[0] + '/dashboard.php';
                    window.location.href = newPath;
                });
            </script>
        </div>
    </div>

</body>

</html>