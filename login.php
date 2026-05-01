<?php
// login.php - Client Login for Magic Hotel
session_start();
require_once 'config.php';

$error = "";
$success = "";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: client_dashboard.php");
    exit();
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                
                // Update last login
                $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Set remember me cookie (30 days)
                if ($remember) {
                    setcookie('remember_email', $email, time() + (86400 * 30), "/");
                }
                
                header("Location: client_dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "No account found with this email. Please register first.";
        }
        $stmt->close();
    }
}

// Check for remember me cookie
$remembered_email = isset($_COOKIE['remember_email']) ? $_COOKIE['remember_email'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Login - Magic Hotel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
            background-color: #0a0a0a;
            color: #e0e0e0;
            min-height: 100vh;
        }

        /* Navigation Bar */
        .navbar {
            position: sticky;
            top: 0;
            width: 100%;
            background: #000000;
            padding: 1.2rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
        }

        .logo {
            font-size: 1.9rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: #ff6600;
            text-transform: uppercase;
            text-decoration: none;
        }

        .nav-links a {
            color: #f0f0f0;
            text-decoration: none;
            margin-left: 2.2rem;
            font-size: 1rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
        }

        .nav-links a:hover {
            color: #ff6600;
        }

        /* Hero Section */
        .hero-login {
            width: 100%;
            height: 40vh;
            min-height: 300px;
            background: linear-gradient(135deg, rgba(0,0,0,0.85), rgba(255,102,0,0.25)), url('https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?auto=compress&cs=tinysrgb&w=1600');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-overlay {
            text-align: center;
            padding: 2rem;
            background: rgba(0,0,0,0.6);
            border-radius: 20px;
            border-left: 5px solid #ff6600;
            border-right: 5px solid #ff6600;
        }

        .hero-overlay h1 {
            font-size: 3rem;
            font-weight: 700;
            letter-spacing: 4px;
            color: white;
            text-transform: uppercase;
        }

        .hero-overlay h1 span {
            color: #ff6600;
        }

        .hero-overlay p {
            font-size: 1rem;
            color: #ddd;
            margin-top: 0.5rem;
        }

        /* Login Container */
        .login-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 3rem 20px;
        }

        .login-container {
            width: 100%;
            max-width: 480px;
        }

        .login-card {
            background: #111111;
            border-radius: 28px;
            padding: 2.5rem;
            box-shadow: 0 25px 40px rgba(0,0,0,0.5);
            border: 1px solid #2a2a2a;
            transition: all 0.3s ease;
        }

        .login-card:hover {
            border-color: #ff6600;
        }

        .form-title {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-title h2 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #ff6600;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .divider {
            width: 60px;
            height: 3px;
            background: #ff6600;
            margin: 0.8rem auto 0;
            border-radius: 3px;
        }

        /* Alerts */
        .alert-error {
            background: rgba(220, 53, 69, 0.15);
            border-left: 4px solid #dc3545;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            color: #ff8888;
            text-align: center;
        }

        .alert-success {
            background: rgba(255, 102, 0, 0.15);
            border-left: 4px solid #ff6600;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            color: #ffaa66;
            text-align: center;
        }

        /* Input Groups */
        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.6rem;
            font-size: 0.85rem;
            letter-spacing: 1px;
            color: #ccc;
            font-weight: 500;
        }

        .input-group input {
            width: 100%;
            padding: 0.9rem 1.2rem;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 30px;
            font-size: 1rem;
            color: white;
            transition: all 0.3s;
            font-family: inherit;
        }

        .input-group input:focus {
            outline: none;
            border-color: #ff6600;
            box-shadow: 0 0 0 3px rgba(255,102,0,0.2);
        }

        .input-group input::placeholder {
            color: #555;
        }

        /* Form Options */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.8rem;
            font-size: 0.85rem;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .checkbox-group input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #ff6600;
        }

        .checkbox-group label {
            cursor: pointer;
            color: #aaa;
        }

        .forgot-link {
            color: #ff6600;
            text-decoration: none;
            transition: 0.2s;
        }

        .forgot-link:hover {
            color: #ffaa44;
            text-decoration: underline;
        }

        /* Login Button */
        .login-btn {
            background: #ff6600;
            color: #000000;
            border: none;
            padding: 0.9rem 2rem;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 40px;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .login-btn:hover {
            background: #ff8833;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255,102,0,0.3);
        }

        /* Register Link */
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #2a2a2a;
        }

        .register-link a {
            color: #ff6600;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        /* Footer */
        footer {
            background-color: #000000;
            text-align: center;
            padding: 35px 20px;
            margin-top: 60px;
            font-size: 0.85rem;
            letter-spacing: 1px;
            color: #888;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem 1.5rem;
                flex-direction: column;
                gap: 10px;
            }
            .nav-links a {
                margin-left: 1rem;
                margin-right: 1rem;
            }
            .hero-overlay h1 {
                font-size: 2rem;
            }
            .login-card {
                padding: 1.8rem;
            }
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<div class="navbar">
    <a href="index.html" class="logo">MAGIC HOTEL</a>
    <div class="nav-links">
        <a href="index.html">Home</a>
        <a href="services.html">Services</a>
        <a href="contact.html">Contact</a>
        
    </div>
</div>

<!-- Hero Section -->
<div class="hero-login">
    <div class="hero-overlay">
        <h1>WELCOME <span>BACK</span></h1>
        <p>Login to manage your bookings</p>
    </div>
</div>

<!-- Login Form Section -->
<div class="login-wrapper">
    <div class="login-container">
        <div class="login-card">
            <div class="form-title">
                <h2>Client Login</h2>
                <div class="divider"></div>
            </div>

            <!-- Error/Success Messages -->
            <?php if (!empty($error)): ?>
                <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="">
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="your@email.com" value="<?php echo htmlspecialchars($remembered_email); ?>" required autofocus>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>

                <div class="form-options">
                    <label class="checkbox-group">
                        <input type="checkbox" name="remember" <?php echo $remembered_email ? 'checked' : ''; ?>>
                        <label>Remember me</label>
                    </label>
                    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                </div>

                <button type="submit" name="login" class="login-btn">Sign In</button>
            </form>

            <div class="register-link">
                Don't have an account? <a href="register.php">Create an account</a>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer>
    © 2025 MAGIC HOTEL LTD — Where elegance meets comfort. All rights reserved.
</footer>

</body>
</html>