<?php

session_start();
require_once 'config.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        if (!$admin) {
            $error = 'No staff account was found with that email address.';
        } elseif (($admin['status'] ?? 'active') !== 'active') {
            $error = 'This staff account is inactive. Contact the system administrator.';
        } else {
            $storedPassword = (string) $admin['password'];
            $passwordMatches = password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);

            if ($passwordMatches) {
                if (!password_get_info($storedPassword)['algo']) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upgradeStmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
                    $upgradeStmt->bind_param('si', $newHash, $admin['id']);
                    $upgradeStmt->execute();
                    $upgradeStmt->close();
                }

                $_SESSION['admin_id'] = (int) $admin['id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_role'] = $admin['role'];

                $updateStmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $updateStmt->bind_param('i', $admin['id']);
                $updateStmt->execute();
                $updateStmt->close();

                header('Location: admin_dashboard.php');
                exit();
            }

            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Magic Hotel</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            font-family: "Segoe UI", Arial, sans-serif;
            background:
                radial-gradient(circle at top, rgba(255, 102, 0, 0.14), transparent 30%),
                linear-gradient(160deg, #060606, #141414 55%, #1d1d1d);
            color: #f4f4f4;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-shell {
            width: min(460px, 100%);
        }

        .login-card {
            background: rgba(10, 10, 10, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 28px;
            padding: 36px;
            box-shadow: 0 28px 60px rgba(0, 0, 0, 0.35);
        }

        .eyebrow {
            display: inline-flex;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 102, 0, 0.12);
            color: #ffb27d;
            font-size: 0.82rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 18px;
        }

        h1 {
            font-size: 2rem;
            color: #ff7a1a;
            margin-bottom: 10px;
        }

        p {
            color: #bdbdbd;
            line-height: 1.6;
            margin-bottom: 26px;
        }

        .alert {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-left: 4px solid #e04d4d;
            border-radius: 14px;
            background: rgba(224, 77, 77, 0.12);
            color: #ffb7b7;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #d7d7d7;
            font-size: 0.92rem;
        }

        .field {
            margin-bottom: 16px;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid #2f2f2f;
            background: #101010;
            color: #f5f5f5;
            font-size: 1rem;
        }

        input:focus {
            outline: none;
            border-color: #ff7a1a;
            box-shadow: 0 0 0 3px rgba(255, 122, 26, 0.18);
        }

        .button {
            width: 100%;
            border: none;
            border-radius: 18px;
            padding: 14px 16px;
            background: linear-gradient(135deg, #ff7a1a, #ff9d52);
            color: #171717;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
        }

        .button:hover {
            filter: brightness(1.05);
        }

        .help {
            margin-top: 22px;
            font-size: 0.92rem;
            color: #bdbdbd;
        }

        .help a {
            color: #ffb27d;
            text-decoration: none;
        }

        .demo-box {
            margin-top: 24px;
            padding: 18px;
            border-radius: 18px;
            background: #111111;
            border: 1px solid #242424;
        }

        .demo-box strong {
            color: #ffcfaa;
            display: block;
            margin-bottom: 8px;
        }

        .demo-box code {
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="login-card">
            <div class="eyebrow">Management Access</div>
            <h1>Magic Hotel Admin</h1>
            <p>Sign in to manage bookings, staff, restaurant and bar items, contact details, and guest feedback.</p>

            <?php if ($error !== ''): ?>
                <div class="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="field">
                    <label for="email">Email Address</label>
                    <input id="email" type="email" name="email" placeholder="admin@magichotel.com" required autofocus>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" placeholder="Enter your password" required>
                </div>

                <button class="button" type="submit" name="login">Log In</button>
            </form>

            <div class="demo-box">
                <strong>Default admin account</strong>
                <div><code>admin@magichotel.com</code> / <code>admin123</code></div>
            </div>

            <div class="help">
                <a href="index.html">Back to website</a>
            </div>
        </div>
    </div>
</body>
</html>

