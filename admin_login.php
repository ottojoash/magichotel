<?php
// admin_login.php - MODIFIED FOR PLAIN TEXT PASSWORDS (DEVELOPMENT ONLY)
session_start();
require_once 'config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password']; // This is plain text
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Query admin from database
        $sql = "SELECT * FROM admins WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            // DIRECT COMPARISON - NO HASHING (NOT SECURE!)
            if ($password === $admin['password']) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_role'] = $admin['role'];
                
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "No admin account found with this email.";
        }
        $stmt->close();
    }
}
?>
<!-- Rest of your login page HTML remains the same -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Magic Hotel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .login-container {
            max-width: 450px;
            width: 100%;
        }
        .login-card {
            background: #111;
            border-radius: 28px;
            padding: 2.5rem;
            border: 1px solid #2a2a2a;
            text-align: center;
        }
        .login-card h1 {
            color: #ff6600;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .login-card p {
            color: #888;
            margin-bottom: 2rem;
        }
        .admin-badge {
            background: rgba(255,102,0,0.2);
            color: #ffaa66;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 1rem;
        }
        .input-group {
            margin-bottom: 1rem;
            text-align: left;
        }
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #ccc;
            font-size: 0.85rem;
        }
        .input-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 30px;
            color: white;
            font-size: 1rem;
        }
        .input-group input:focus {
            outline: none;
            border-color: #ff6600;
        }
        .btn {
            background: #ff6600;
            color: black;
            width: 100%;
            padding: 0.8rem;
            border: none;
            border-radius: 30px;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 1rem;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #ff8833;
            transform: translateY(-2px);
        }
        .alert-error {
            background: rgba(220,53,69,0.15);
            border-left: 4px solid #dc3545;
            padding: 0.8rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            color: #ff8888;
            text-align: center;
        }
        .back-link {
            display: block;
            margin-top: 1rem;
            color: #666;
            text-decoration: none;
            font-size: 0.8rem;
        }
        .back-link:hover {
            color: #ff6600;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h1>🔐 ADMIN</h1>
            <div class="admin-badge">Restricted Access</div>
            <p>Enter your credentials to access the dashboard</p>
            
            <?php if (!empty($error)): ?>
                <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="admin@magichotel.com" required autofocus>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" name="login" class="btn">Login as Admin</button>
            </form>
            <a href="index.html" class="back-link">← Back to Website</a>
        </div>
    </div>
</body>
</html>