<?php

session_start();
require_once 'config.php';
require_once 'hotel_helpers.php';

$message = '';
$error = '';
$catalog = getServiceCatalog($conn, true);
$categoryMeta = getCategoryMeta();
$settings = getHotelSettings($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $serviceIds = $_POST['service_id'] ?? [];
    $quantities = $_POST['qty'] ?? [];

    if ($name === '' || $email === '' || $phone === '') {
        $error = 'Please complete your contact details before continuing.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters.';
    } elseif (empty($serviceIds)) {
        $error = 'Please select at least one service to book.';
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $existingUser = $result->fetch_assoc();
        $stmt->close();

        if ($existingUser) {
            if (!password_verify($password, $existingUser['password'])) {
                $error = 'An account with that email already exists. Enter the correct password or log in first.';
            } else {
                $userId = (int) $existingUser['id'];
                $updateStmt = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
                $updateStmt->bind_param('ssi', $name, $phone, $userId);
                $updateStmt->execute();
                $updateStmt->close();
            }
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
            $insertStmt->bind_param('ssss', $name, $email, $phone, $hashedPassword);

            if ($insertStmt->execute()) {
                $userId = (int) $conn->insert_id;
            } else {
                $error = 'We could not create your account right now. Please try again.';
            }

            $insertStmt->close();
        }

        if ($error === '' && isset($userId)) {
            $created = createBookingsFromSelection($conn, $userId, $serviceIds, $quantities, $bookingError);

            if ($created > 0) {
                $message = 'Your booking request has been submitted successfully. We will contact you shortly.';
            } else {
                $error = $bookingError !== '' ? $bookingError : 'We could not create your booking.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book With Magic Hotel</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background:
                radial-gradient(circle at top, rgba(255, 122, 26, 0.12), transparent 30%),
                linear-gradient(180deg, #070707, #0d0d0d 36%, #121212);
            color: #f3f3f3;
        }

        .navbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 18px 28px;
            background: rgba(4, 4, 4, 0.96);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .logo {
            color: #ff7a1a;
            text-decoration: none;
            font-size: 1.45rem;
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
        }

        .nav-links a {
            color: #e7e7e7;
            text-decoration: none;
        }

        .nav-links a:hover {
            color: #ffae76;
        }

        .hero {
            padding: 72px 20px 48px;
        }

        .hero-card {
            width: min(1160px, 100%);
            margin: 0 auto;
            padding: 36px;
            border-radius: 32px;
            background:
                linear-gradient(135deg, rgba(255, 122, 26, 0.14), rgba(255, 122, 26, 0.02)),
                rgba(10, 10, 10, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.28);
        }

        .hero-card h1 {
            font-size: clamp(2.1rem, 3vw, 3.3rem);
            color: #ff7a1a;
            margin-bottom: 14px;
        }

        .hero-card p {
            max-width: 760px;
            color: #c8c8c8;
            line-height: 1.7;
        }

        .hero-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 28px;
        }

        .hero-stat {
            padding: 18px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .hero-stat strong {
            display: block;
            color: #ffcfaa;
            margin-bottom: 8px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .hero-stat span {
            color: #f5f5f5;
            line-height: 1.6;
        }

        .wrap {
            width: min(1160px, 100%);
            margin: 0 auto;
            padding: 0 20px 56px;
        }

        .alert {
            margin-bottom: 20px;
            padding: 16px 18px;
            border-radius: 18px;
            line-height: 1.6;
        }

        .alert-success {
            background: rgba(45, 166, 93, 0.16);
            border-left: 4px solid #33a35c;
            color: #c6f2d6;
        }

        .alert-error {
            background: rgba(220, 80, 80, 0.14);
            border-left: 4px solid #d9534f;
            color: #ffc2c2;
        }

        .layout {
            display: grid;
            grid-template-columns: 1.25fr 0.75fr;
            gap: 24px;
            align-items: start;
        }

        .panel {
            padding: 28px;
            border-radius: 28px;
            background: rgba(10, 10, 10, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.22);
        }

        .panel h2 {
            color: #ff7a1a;
            margin-bottom: 12px;
        }

        .panel p {
            color: #c2c2c2;
            line-height: 1.7;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-top: 22px;
        }

        .field {
            margin-bottom: 16px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.92rem;
            color: #dedede;
        }

        input,
        textarea {
            width: 100%;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid #2d2d2d;
            background: #0f0f0f;
            color: #f5f5f5;
            font: inherit;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: #ff7a1a;
            box-shadow: 0 0 0 3px rgba(255, 122, 26, 0.14);
        }

        .service-category {
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .category-heading {
            margin-bottom: 8px;
            color: #ffb27d;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-size: 0.86rem;
        }

        .category-title {
            font-size: 1.55rem;
            color: #ffffff;
            margin-bottom: 8px;
        }

        .section-label {
            margin: 18px 0 12px;
            color: #ffcfaa;
            font-size: 0.94rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .service-list {
            display: grid;
            gap: 12px;
        }

        .service-item {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 14px;
            align-items: start;
            padding: 16px;
            border-radius: 20px;
            background: #121212;
            border: 1px solid #242424;
        }

        .service-item:hover {
            border-color: rgba(255, 122, 26, 0.4);
        }

        .service-selector {
            margin-top: 4px;
        }

        .service-title {
            font-size: 1.02rem;
            color: #ffffff;
            margin-bottom: 6px;
        }

        .service-desc {
            color: #b8b8b8;
            line-height: 1.6;
            font-size: 0.94rem;
        }

        .service-meta {
            min-width: 140px;
            text-align: right;
        }

        .service-price {
            color: #ffb27d;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .qty-box {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #0a0a0a;
            border: 1px solid #2b2b2b;
            border-radius: 999px;
            padding: 8px 12px;
        }

        .qty-box input {
            width: 72px;
            padding: 6px 8px;
            background: transparent;
            border: none;
            text-align: center;
            box-shadow: none;
        }

        .total-box {
            margin-top: 26px;
            padding: 22px;
            border-radius: 24px;
            background: linear-gradient(135deg, rgba(255, 122, 26, 0.12), rgba(255, 122, 26, 0.02));
            border: 1px solid rgba(255, 122, 26, 0.2);
        }

        .total-box span {
            display: block;
            color: #cfcfcf;
        }

        .total-box strong {
            display: block;
            margin-top: 8px;
            font-size: 2rem;
            color: #ff7a1a;
        }

        .button {
            width: 100%;
            margin-top: 18px;
            padding: 15px 18px;
            border: none;
            border-radius: 18px;
            background: linear-gradient(135deg, #ff7a1a, #ff9d52);
            color: #171717;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
        }

        .sidebar-block + .sidebar-block {
            margin-top: 18px;
        }

        .sidebar-block h3 {
            color: #ffcfaa;
            margin-bottom: 10px;
        }

        .sidebar-block ul {
            list-style: none;
        }

        .sidebar-block li {
            color: #d1d1d1;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            line-height: 1.6;
        }

        .sidebar-block li:last-child {
            border-bottom: none;
        }

        .sidebar-note {
            margin-top: 20px;
            padding: 18px;
            border-radius: 20px;
            background: #111111;
            border: 1px solid #242424;
            color: #c8c8c8;
            line-height: 1.7;
        }

        footer {
            padding: 28px 20px 40px;
            text-align: center;
            color: #9a9a9a;
        }

        @media (max-width: 920px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .field-grid {
                grid-template-columns: 1fr;
            }

            .service-item {
                grid-template-columns: auto 1fr;
            }

            .service-meta {
                grid-column: 1 / -1;
                text-align: left;
            }
        }

        @media (max-width: 640px) {
            .navbar {
                padding: 16px 18px;
                flex-direction: column;
                align-items: flex-start;
            }

            .hero-card,
            .panel {
                padding: 22px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a class="logo" href="index.html"><?php echo htmlspecialchars($settings['hotel_name']); ?></a>
        <div class="nav-links">
            <a href="index.html">Home</a>
            <a href="services.php">Services</a>
            <a href="contact.php">Contact</a>
            <a href="login.php">Client Login</a>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-card">
            <h1>Book rooms, meals, and bar experiences in one place.</h1>
            <p>Magic Hotel now runs from a single service catalog, so the same restaurant menu, bar list, and wellness services appear in booking, guest accounts, and the management dashboard.</p>

            <div class="hero-meta">
                <div class="hero-stat">
                    <strong>Reservations</strong>
                    <span><?php echo htmlspecialchars($settings['reservation_email']); ?><br><?php echo htmlspecialchars($settings['primary_phone']); ?></span>
                </div>
                <div class="hero-stat">
                    <strong>Restaurant Hours</strong>
                    <span><?php echo htmlspecialchars($settings['restaurant_hours']); ?></span>
                </div>
                <div class="hero-stat">
                    <strong>Bar Hours</strong>
                    <span><?php echo htmlspecialchars($settings['bar_hours']); ?></span>
                </div>
            </div>
        </div>
    </section>

    <main class="wrap">
        <?php if ($message !== ''): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="layout">
            <section class="panel">
                <h2>Reservation Form</h2>
                <p>Create an account while booking, or use your existing guest account credentials if you have booked with us before.</p>

                <form method="POST" oninput="calculateTotal()">
                    <div class="field-grid">
                        <div class="field">
                            <label for="name">Full Name</label>
                            <input id="name" type="text" name="name" required>
                        </div>

                        <div class="field">
                            <label for="email">Email Address</label>
                            <input id="email" type="email" name="email" required>
                        </div>

                        <div class="field">
                            <label for="phone">Phone Number</label>
                            <input id="phone" type="tel" name="phone" required>
                        </div>

                        <div class="field">
                            <label for="password">Password</label>
                            <input id="password" type="password" name="password" required>
                        </div>

                        <div class="field full">
                            <label for="confirm_password">Confirm Password</label>
                            <input id="confirm_password" type="password" name="confirm_password" required>
                        </div>
                    </div>

                    <?php foreach ($catalog as $category => $sections): ?>
                        <div class="service-category">
                            <div class="category-heading"><?php echo htmlspecialchars($categoryMeta[$category]['label'] ?? ucfirst($category)); ?></div>
                            <div class="category-title"><?php echo htmlspecialchars($categoryMeta[$category]['label'] ?? ucfirst($category)); ?></div>
                            <p><?php echo htmlspecialchars($categoryMeta[$category]['summary'] ?? ''); ?></p>

                            <?php foreach ($sections as $sectionName => $services): ?>
                                <div class="section-label"><?php echo htmlspecialchars($sectionName); ?></div>
                                <div class="service-list">
                                    <?php foreach ($services as $service): ?>
                                        <label class="service-item">
                                            <div class="service-selector">
                                                <input
                                                    type="checkbox"
                                                    name="service_id[]"
                                                    value="<?php echo (int) $service['id']; ?>"
                                                    data-price="<?php echo (float) $service['price']; ?>"
                                                    onchange="calculateTotal()"
                                                >
                                            </div>

                                            <div>
                                                <div class="service-title"><?php echo htmlspecialchars($service['service_name']); ?></div>
                                                <div class="service-desc"><?php echo htmlspecialchars((string) $service['description']); ?></div>
                                            </div>

                                            <div class="service-meta">
                                                <div class="service-price">
                                                    <?php echo htmlspecialchars(formatUgx((float) $service['price'])); ?> / <?php echo htmlspecialchars($service['pricing_unit']); ?>
                                                </div>
                                                <div class="qty-box">
                                                    <span>Qty</span>
                                                    <input
                                                        type="number"
                                                        name="qty[<?php echo (int) $service['id']; ?>]"
                                                        min="1"
                                                        value="1"
                                                        onchange="calculateTotal()"
                                                    >
                                                </div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="total-box">
                        <span>Total Quote</span>
                        <strong id="totalDisplay">UGX 0</strong>
                    </div>

                    <button class="button" type="submit" name="submit">Submit Booking</button>
                </form>
            </section>

            <aside class="panel">
                <div class="sidebar-block">
                    <h3>What changed</h3>
                    <ul>
                        <li>The restaurant menu now includes priced items that the admin team can manage from the dashboard.</li>
                        <li>Bar items are part of the same service catalog, so guests can book them and staff can manage availability.</li>
                        <li>Contact details on the site now come from one place in the management dashboard.</li>
                    </ul>
                </div>

                <div class="sidebar-block">
                    <h3>Need help?</h3>
                    <ul>
                        <li>Front desk: <?php echo htmlspecialchars($settings['primary_phone']); ?></li>
                        <li>WhatsApp: <?php echo htmlspecialchars($settings['secondary_phone']); ?></li>
                        <li>Email: <?php echo htmlspecialchars($settings['email']); ?></li>
                        <li>Address: <?php echo htmlspecialchars($settings['address']); ?></li>
                    </ul>
                </div>

                <div class="sidebar-note">
                    If you already have a client account, use the same email and password you created before. We will attach the new booking to your existing profile.
                </div>
            </aside>
        </div>
    </main>

    <footer>
        <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['hotel_name']); ?>. <?php echo htmlspecialchars($settings['tagline']); ?>
    </footer>

    <script>
        function formatCurrency(value) {
            return "UGX " + value.toLocaleString();
        }

        function calculateTotal() {
            var total = 0;
            var selected = document.querySelectorAll('input[name="service_id[]"]:checked');

            selected.forEach(function (checkbox) {
                var serviceId = checkbox.value;
                var quantityInput = document.querySelector('input[name="qty[' + serviceId + ']"]');
                var quantity = quantityInput ? parseInt(quantityInput.value, 10) || 1 : 1;
                var price = parseFloat(checkbox.getAttribute('data-price')) || 0;
                total += price * quantity;
            });

            document.getElementById('totalDisplay').textContent = formatCurrency(total);
        }

        document.addEventListener('DOMContentLoaded', calculateTotal);
    </script>
</body>
</html>

