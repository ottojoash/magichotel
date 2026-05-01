<?php
// dashboard.php - Magic Hotel Admin Dashboard
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    if (isset($_COOKIE['remember_email'])) {
        setcookie('remember_email', '', time() - 3600, "/");
    }
    header("Location: login.php");
    exit();
}

$user_email = $_SESSION['user_email'];
$login_time = $_SESSION['login_time'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magic Hotel - Dashboard</title>
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
        }

        .navbar {
            background: #000000;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #ff6600;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #ff6600;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 1.5rem;
        }

        .nav-links a:hover {
            color: #ff6600;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .welcome-card {
            background: #111;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 5px solid #ff6600;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #111;
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid #2a2a2a;
        }

        .stat-card:hover {
            border-color: #ff6600;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #ff6600;
        }

        .logout-btn {
            background: #ff6600;
            color: black;
            padding: 0.5rem 1.2rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
        }

        .logout-btn:hover {
            background: #ff8833;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo">MAGIC HOTEL</div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="feedback.php">Feedback</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome-card">
            <h1>Welcome back, <?php echo htmlspecialchars($user_email); ?>!</h1>
            <p>Logged in since: <?php echo htmlspecialchars($login_time); ?></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">150+</div>
                <p>Happy Guests</p>
            </div>
            <div class="stat-card">
                <div class="stat-number">4.8</div>
                <p>Average Rating</p>
            </div>
            <div class="stat-card">
                <div class="stat-number">24/7</div>
                <p>Support</p>
            </div>
        </div>
    </div>
</body>
</html>