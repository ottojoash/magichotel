<?php

session_start();
require_once 'config.php';
require_once 'hotel_helpers.php';

$message = '';
$error = '';
$catalog = getServiceCatalog($conn, true);
$categoryMeta = getCategoryMeta();
$settings = getHotelSettings($conn);

$nameValue = '';
$emailValue = '';
$phoneValue = '';
$selectedServiceIds = [];
$qtyByService = [];

$categoryOrder = ['rooms', 'restaurant', 'bar', 'spa', 'gym'];
$bookingSteps = [
    ['key' => 'guest', 'title' => 'Guest Details', 'category' => null],
];

foreach ($categoryOrder as $categoryKey) {
    if (isset($catalog[$categoryKey])) {
        $bookingSteps[] = [
            'key' => $categoryKey,
            'title' => $categoryMeta[$categoryKey]['label'] ?? ucfirst($categoryKey),
            'category' => $categoryKey,
        ];
    }
}

$bookingSteps[] = ['key' => 'review', 'title' => 'Review & Submit', 'category' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $serviceIds = $_POST['service_id'] ?? [];
    $quantities = $_POST['qty'] ?? [];

    $nameValue = $name;
    $emailValue = $email;
    $phoneValue = $phone;
    $selectedServiceIds = array_values(array_unique(array_filter(array_map('intval', (array) $serviceIds), static function ($id) {
        return $id > 0;
    })));

    foreach ((array) $quantities as $serviceId => $qty) {
        $normalizedServiceId = (int) $serviceId;

        if ($normalizedServiceId > 0) {
            $qtyByService[$normalizedServiceId] = max(1, (int) $qty);
        }
    }

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
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap');

        :root {
            --bg: #040507;
            --panel: #0b0d11;
            --panel-soft: #10131a;
            --line: rgba(255, 255, 255, 0.1);
            --text: #f6f7f9;
            --muted: #afb2b9;
            --accent: #ff6a00;
            --accent-soft: #ff9a4f;
            --danger: #db4f4f;
            --success: #39b56b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 16% 10%, rgba(255, 106, 0, 0.14), transparent 30%),
                radial-gradient(circle at 84% 0%, rgba(255, 255, 255, 0.07), transparent 22%),
                linear-gradient(180deg, #020203, #05070b 44%, #0a0d12);
            min-height: 100vh;
        }

        .navbar {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding: 16px 24px;
            backdrop-filter: blur(10px);
            background: rgba(4, 5, 7, 0.86);
            border-bottom: 1px solid var(--line);
        }

        .logo {
            text-decoration: none;
            color: var(--accent);
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 1.35rem;
            letter-spacing: 0.07em;
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        .nav-links a {
            color: #e6e8ee;
            text-decoration: none;
            font-size: 0.96rem;
        }

        .nav-links a:hover {
            color: var(--accent-soft);
        }

        .hero {
            width: min(1120px, 100%);
            margin: 0 auto;
            padding: 56px 20px 12px;
        }

        .hero h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: clamp(1.8rem, 4vw, 2.8rem);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--accent);
        }

        .hero p {
            margin-top: 14px;
            max-width: 760px;
            color: #cacdd5;
            line-height: 1.75;
        }

        .wrap {
            width: min(1120px, 100%);
            margin: 0 auto;
            padding: 18px 20px 52px;
        }

        .alert {
            border-radius: 16px;
            padding: 14px 16px;
            margin-bottom: 16px;
            line-height: 1.6;
        }

        .alert-success {
            background: rgba(57, 181, 107, 0.14);
            border: 1px solid rgba(57, 181, 107, 0.4);
            color: #d1ffe2;
        }

        .alert-error {
            background: rgba(219, 79, 79, 0.12);
            border: 1px solid rgba(219, 79, 79, 0.4);
            color: #ffd7d7;
        }

        .wizard-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 20px;
            align-items: start;
        }

        .wizard {
            border-radius: 28px;
            padding: 24px;
            border: 1px solid var(--line);
            background: linear-gradient(165deg, rgba(18, 20, 26, 0.95), rgba(8, 10, 14, 0.95));
            box-shadow: 0 24px 52px rgba(0, 0, 0, 0.32);
        }

        .stepper {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .step-chip {
            border: 1px solid #292d36;
            background: #12151b;
            color: #c2c7d0;
            border-radius: 999px;
            padding: 10px 14px;
            font-size: 0.84rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .step-chip:hover {
            border-color: rgba(255, 106, 0, 0.5);
            color: #ffe4ce;
        }

        .step-chip.is-active {
            color: #1c1208;
            background: linear-gradient(135deg, var(--accent), var(--accent-soft));
            border-color: transparent;
            font-weight: 700;
        }

        .step-panel {
            display: none;
        }

        .step-panel.is-active {
            display: block;
            animation: appear 0.24s ease;
        }

        @keyframes appear {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .step-title {
            color: var(--accent);
            font-family: 'Montserrat', sans-serif;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .step-subtitle {
            color: var(--muted);
            margin-bottom: 18px;
            line-height: 1.7;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            color: #dbdde3;
            font-size: 0.88rem;
            letter-spacing: 0.03em;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            background: #0b0d12;
            color: #f3f6fc;
            border: 1px solid #272b33;
            border-radius: 14px;
            font: inherit;
        }

        input:focus {
            border-color: rgba(255, 106, 0, 0.75);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 106, 0, 0.16);
        }

        .section-label {
            margin: 12px 0 10px;
            font-size: 0.83rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #ffc496;
            border-left: 2px solid var(--accent);
            padding-left: 8px;
        }

        .service-list {
            display: grid;
            gap: 11px;
        }

        .service-item {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            border-radius: 18px;
            border: 1px solid #242832;
            background: linear-gradient(160deg, #141820, #11141b);
            padding: 14px;
            transition: border-color 0.2s ease;
        }

        .service-item.has-selected {
            border-color: rgba(255, 106, 0, 0.55);
        }

        .service-check {
            width: 20px;
            height: 20px;
            accent-color: var(--accent);
            margin-top: 2px;
        }

        .service-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 4px;
            color: #f5f8ff;
        }

        .service-desc {
            color: #aeb3bd;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .service-meta {
            min-width: 170px;
            text-align: right;
        }

        .service-price {
            color: #ffb57c;
            font-weight: 700;
            margin-bottom: 9px;
        }

        .qty-box {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 1px solid #2d323d;
            border-radius: 999px;
            padding: 6px 10px;
            background: #090b0f;
            color: #c9cbd1;
            font-size: 0.84rem;
        }

        .qty-box input {
            width: 62px;
            padding: 5px 6px;
            text-align: center;
            border-radius: 999px;
            border: 1px solid #303440;
            background: #040507;
        }

        .qty-box input:disabled {
            opacity: 0.45;
        }

        .total-box {
            margin-top: 14px;
            border-radius: 20px;
            padding: 18px;
            border: 1px solid rgba(255, 106, 0, 0.25);
            background: linear-gradient(130deg, rgba(255, 106, 0, 0.15), rgba(255, 106, 0, 0.04));
        }

        .total-box span {
            display: block;
            color: #d5d8de;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-size: 0.8rem;
        }

        .total-box strong {
            display: block;
            margin-top: 8px;
            font-size: clamp(1.7rem, 2vw, 2.2rem);
            color: var(--accent);
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
        }

        .summary-list {
            margin-top: 14px;
            border-radius: 16px;
            border: 1px solid #272c37;
            overflow: hidden;
        }

        .summary-empty,
        .summary-item {
            padding: 12px 14px;
            background: #0d1016;
            color: #c8ccd4;
            border-top: 1px solid #202530;
        }

        .summary-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
        }

        .summary-item strong {
            color: #f2f5fb;
            display: block;
        }

        .summary-item span {
            color: #ffbe8b;
            font-weight: 600;
        }

        .summary-list > :first-child {
            border-top: none;
        }

        .actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .button {
            border: none;
            border-radius: 999px;
            padding: 12px 22px;
            font: inherit;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            cursor: pointer;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button.secondary {
            background: #1a1e27;
            border: 1px solid #2e3340;
            color: #cfd3dc;
        }

        .button.primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-soft));
            color: #261506;
        }

        .button.submit {
            width: 100%;
            padding: 15px;
            font-size: 0.95rem;
        }

        .confirm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(2, 3, 5, 0.78);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 200;
            padding: 18px;
        }

        .confirm-overlay.is-open {
            display: flex;
        }

        .confirm-modal {
            width: min(560px, 100%);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: linear-gradient(165deg, #12151c, #0b0d12);
            box-shadow: 0 22px 48px rgba(0, 0, 0, 0.38);
            padding: 22px;
        }

        .confirm-modal h3 {
            color: #ffbc88;
            font-family: 'Montserrat', sans-serif;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 10px;
        }

        .confirm-modal p {
            color: #c7ccd6;
            line-height: 1.65;
            margin-bottom: 14px;
        }

        .confirm-total {
            border-radius: 14px;
            border: 1px solid rgba(255, 106, 0, 0.28);
            background: rgba(255, 106, 0, 0.11);
            padding: 12px 14px;
            color: #ffe8d3;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .confirm-items {
            border: 1px solid #2a2f3a;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 14px;
            max-height: 220px;
            overflow-y: auto;
        }

        .confirm-items .summary-empty,
        .confirm-items .summary-item {
            background: #0d1017;
        }

        .confirm-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .sidebar {
            border-radius: 24px;
            border: 1px solid var(--line);
            background: rgba(9, 11, 16, 0.94);
            padding: 18px;
        }

        .sidebar h3 {
            color: #ffd0a8;
            margin-bottom: 8px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.96rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .sidebar p,
        .sidebar li {
            color: #bdc2cc;
            font-size: 0.92rem;
            line-height: 1.65;
        }

        .sidebar ul {
            list-style: none;
            margin-bottom: 12px;
        }

        .sidebar li {
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .sidebar li:last-child {
            border-bottom: none;
        }

        footer {
            text-align: center;
            color: #99a0ad;
            padding: 26px 14px 34px;
            font-size: 0.92rem;
        }

        @media (max-width: 980px) {
            .wizard-layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                order: 2;
            }
        }

        @media (max-width: 760px) {
            .field-grid {
                grid-template-columns: 1fr;
            }

            .service-item {
                grid-template-columns: auto minmax(0, 1fr);
            }

            .service-meta {
                min-width: 0;
                grid-column: 1 / -1;
                text-align: left;
                padding-left: 32px;
            }
        }

        @media (max-width: 620px) {
            .navbar {
                padding: 14px 16px;
                flex-direction: column;
                align-items: flex-start;
            }

            .wizard {
                padding: 18px;
                border-radius: 22px;
            }

            .step-chip {
                font-size: 0.75rem;
                padding: 9px 11px;
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
        <h1>Reservation Form</h1>
        <p>Guests now book using guided steps like your shared design: contact details first, then room and service selection, and finally a review with total quote.</p>
    </section>

    <main class="wrap">
        <?php if ($message !== ''): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="wizard-layout">
            <section class="wizard">
                <form id="bookingWizard" method="POST">
                    <div class="stepper">
                        <?php foreach ($bookingSteps as $stepIndex => $step): ?>
                            <button
                                type="button"
                                class="step-chip<?php echo $stepIndex === 0 ? ' is-active' : ''; ?>"
                                data-step-target="<?php echo (int) $stepIndex; ?>"
                            >
                                <?php echo htmlspecialchars(($stepIndex + 1) . '. ' . $step['title']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <?php foreach ($bookingSteps as $stepIndex => $step): ?>
                        <section
                            class="step-panel<?php echo $stepIndex === 0 ? ' is-active' : ''; ?>"
                            data-step-panel="<?php echo (int) $stepIndex; ?>"
                            data-step-key="<?php echo htmlspecialchars($step['key']); ?>"
                        >
                            <?php if ($step['key'] === 'guest'): ?>
                                <h2 class="step-title">Guest Details</h2>
                                <p class="step-subtitle">Enter contact details and account password to continue booking.</p>
                                <div class="field-grid">
                                    <div class="field full">
                                        <label for="name">Full Name</label>
                                        <input id="name" type="text" name="name" required value="<?php echo htmlspecialchars($nameValue); ?>" placeholder="Enter your full name">
                                    </div>

                                    <div class="field full">
                                        <label for="email">Email Address</label>
                                        <input id="email" type="email" name="email" required value="<?php echo htmlspecialchars($emailValue); ?>" placeholder="you@email.com">
                                    </div>

                                    <div class="field full">
                                        <label for="phone">Phone Number</label>
                                        <input id="phone" type="tel" name="phone" required value="<?php echo htmlspecialchars($phoneValue); ?>" placeholder="+256 XXX XXX XXX">
                                    </div>

                                    <div class="field">
                                        <label for="password">Create Password</label>
                                        <input id="password" type="password" name="password" required placeholder="Enter password">
                                    </div>

                                    <div class="field">
                                        <label for="confirm_password">Confirm Password</label>
                                        <input id="confirm_password" type="password" name="confirm_password" required placeholder="Confirm password">
                                    </div>
                                </div>
                            <?php elseif ($step['key'] === 'review'): ?>
                                <h2 class="step-title">Review & Submit</h2>
                                <p class="step-subtitle">Check your selected items and proceed to finalize the reservation.</p>

                                <div class="total-box">
                                    <span>Total Price Quote</span>
                                    <strong id="totalDisplay">UGX 0</strong>
                                </div>

                                <div class="summary-list" id="selectedSummary">
                                    <div class="summary-empty">No services selected yet.</div>
                                </div>

                                <button class="button primary submit" type="button" id="proceedToBook">Proceed To Book</button>
                            <?php else: ?>
                                <?php $categoryKey = (string) $step['category']; ?>
                                <h2 class="step-title"><?php echo htmlspecialchars($categoryMeta[$categoryKey]['label'] ?? ucfirst($categoryKey)); ?></h2>
                                <p class="step-subtitle"><?php echo htmlspecialchars($categoryMeta[$categoryKey]['summary'] ?? 'Choose your preferred options below.'); ?></p>

                                <?php foreach ($catalog[$categoryKey] as $sectionName => $services): ?>
                                    <div class="section-label"><?php echo htmlspecialchars($sectionName); ?></div>
                                    <div class="service-list">
                                        <?php foreach ($services as $service): ?>
                                            <?php
                                            $serviceId = (int) $service['id'];
                                            $isChecked = in_array($serviceId, $selectedServiceIds, true);
                                            $qtyValue = $qtyByService[$serviceId] ?? 1;
                                            ?>
                                            <label class="service-item<?php echo $isChecked ? ' has-selected' : ''; ?>" data-service-card>
                                                <input
                                                    class="service-check"
                                                    type="checkbox"
                                                    name="service_id[]"
                                                    value="<?php echo $serviceId; ?>"
                                                    data-price="<?php echo (float) $service['price']; ?>"
                                                    data-name="<?php echo htmlspecialchars($service['service_name']); ?>"
                                                    data-unit="<?php echo htmlspecialchars($service['pricing_unit']); ?>"
                                                    <?php echo $isChecked ? 'checked' : ''; ?>
                                                >

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
                                                            name="qty[<?php echo $serviceId; ?>]"
                                                            min="1"
                                                            value="<?php echo (int) $qtyValue; ?>"
                                                            <?php echo $isChecked ? '' : 'disabled'; ?>
                                                        >
                                                    </div>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <div class="actions">
                                <?php if ($stepIndex > 0): ?>
                                    <button type="button" class="button secondary" data-back>Back</button>
                                <?php endif; ?>

                                <?php if ($stepIndex < count($bookingSteps) - 1): ?>
                                    <button type="button" class="button primary" data-next>Next Step</button>
                                <?php endif; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </form>
            </section>

            <aside class="sidebar">
                <h3>Need Help?</h3>
                <ul>
                    <li>Front desk: <?php echo htmlspecialchars($settings['primary_phone']); ?></li>
                    <li>WhatsApp: <?php echo htmlspecialchars($settings['secondary_phone']); ?></li>
                    <li>Email: <?php echo htmlspecialchars($settings['email']); ?></li>
                    <li>Address: <?php echo htmlspecialchars($settings['address']); ?></li>
                </ul>

                <h3>Reservation Notes</h3>
                <p>Returning guests can use the same email and password used in previous bookings. New bookings will be attached to the same profile automatically.</p>
            </aside>
        </div>
    </main>

    <div class="confirm-overlay" id="confirmOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
            <h3 id="confirmTitle">Confirm Reservation</h3>
            <p>Please review your booking summary one more time. Click confirm to submit your reservation request.</p>
            <div class="confirm-total" id="confirmTotal">Total: UGX 0</div>
            <div class="confirm-items" id="confirmItems">
                <div class="summary-empty">No services selected yet.</div>
            </div>
            <div class="confirm-actions">
                <button type="button" class="button secondary" id="cancelConfirm">Cancel</button>
                <button type="button" class="button primary" id="acceptConfirm">Confirm & Submit</button>
            </div>
        </div>
    </div>

    <footer>
        <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['hotel_name']); ?>. <?php echo htmlspecialchars($settings['tagline']); ?>
    </footer>

    <script>
        (function () {
            var wizard = document.getElementById('bookingWizard');
            var panels = Array.prototype.slice.call(document.querySelectorAll('[data-step-panel]'));
            var chips = Array.prototype.slice.call(document.querySelectorAll('[data-step-target]'));
            var proceedButton = document.getElementById('proceedToBook');
            var confirmOverlay = document.getElementById('confirmOverlay');
            var cancelConfirm = document.getElementById('cancelConfirm');
            var acceptConfirm = document.getElementById('acceptConfirm');
            var confirmTotal = document.getElementById('confirmTotal');
            var confirmItems = document.getElementById('confirmItems');
            var currentStep = 0;

            function formatCurrency(value) {
                return 'UGX ' + value.toLocaleString();
            }

            function setStep(stepIndex) {
                if (stepIndex < 0 || stepIndex >= panels.length) {
                    return;
                }

                currentStep = stepIndex;

                panels.forEach(function (panel, index) {
                    panel.classList.toggle('is-active', index === currentStep);
                });

                chips.forEach(function (chip, index) {
                    chip.classList.toggle('is-active', index === currentStep);
                });
            }

            function getSelectedItems() {
                var selected = [];
                var checked = document.querySelectorAll('input[name="service_id[]"]:checked');

                checked.forEach(function (checkbox) {
                    var serviceId = checkbox.value;
                    var quantityInput = document.querySelector('input[name="qty[' + serviceId + ']"]');
                    var quantity = quantityInput ? Math.max(1, parseInt(quantityInput.value, 10) || 1) : 1;
                    var price = parseFloat(checkbox.getAttribute('data-price')) || 0;
                    var name = checkbox.getAttribute('data-name') || 'Service';
                    var unit = checkbox.getAttribute('data-unit') || 'unit';

                    selected.push({
                        name: name,
                        unit: unit,
                        quantity: quantity,
                        price: price,
                        total: price * quantity
                    });
                });

                return selected;
            }

            function updateSummary() {
                var total = 0;
                var selectedItems = getSelectedItems();
                var totalDisplay = document.getElementById('totalDisplay');
                var summary = document.getElementById('selectedSummary');

                selectedItems.forEach(function (item) {
                    total += item.total;
                });

                totalDisplay.textContent = formatCurrency(total);

                if (!summary) {
                    return;
                }

                if (selectedItems.length === 0) {
                    summary.innerHTML = '<div class="summary-empty">No services selected yet.</div>';
                    return;
                }

                summary.innerHTML = selectedItems.map(function (item) {
                    return '<div class="summary-item">'
                        + '<div><strong>' + item.name + '</strong>Qty: ' + item.quantity + ' x ' + formatCurrency(item.price) + ' / ' + item.unit + '</div>'
                        + '<span>' + formatCurrency(item.total) + '</span>'
                        + '</div>';
                }).join('');
            }

            function validateGuestStep() {
                var guestPanel = document.querySelector('[data-step-key="guest"]');

                if (!guestPanel) {
                    return true;
                }

                var requiredFields = guestPanel.querySelectorAll('input[required]');

                for (var i = 0; i < requiredFields.length; i++) {
                    if (!requiredFields[i].checkValidity()) {
                        requiredFields[i].reportValidity();
                        return false;
                    }
                }

                return true;
            }

            function validateSelection() {
                var checked = document.querySelectorAll('input[name="service_id[]"]:checked');

                if (checked.length === 0) {
                    alert('Please select at least one service before continuing.');
                    return false;
                }

                return true;
            }

            function updateServiceCards() {
                var checkboxes = document.querySelectorAll('input[name="service_id[]"]');

                checkboxes.forEach(function (checkbox) {
                    var serviceId = checkbox.value;
                    var quantityInput = document.querySelector('input[name="qty[' + serviceId + ']"]');
                    var card = checkbox.closest('[data-service-card]');
                    var checked = checkbox.checked;

                    if (quantityInput) {
                        quantityInput.disabled = !checked;

                        if (!checked) {
                            quantityInput.value = 1;
                        }
                    }

                    if (card) {
                        card.classList.toggle('has-selected', checked);
                    }
                });

                updateSummary();
            }

            function openConfirmModal() {
                var selectedItems = getSelectedItems();
                var total = 0;

                selectedItems.forEach(function (item) {
                    total += item.total;
                });

                confirmTotal.textContent = 'Total: ' + formatCurrency(total);

                if (selectedItems.length === 0) {
                    confirmItems.innerHTML = '<div class="summary-empty">No services selected yet.</div>';
                } else {
                    confirmItems.innerHTML = selectedItems.map(function (item) {
                        return '<div class="summary-item">'
                            + '<div><strong>' + item.name + '</strong>Qty: ' + item.quantity + ' x ' + formatCurrency(item.price) + ' / ' + item.unit + '</div>'
                            + '<span>' + formatCurrency(item.total) + '</span>'
                            + '</div>';
                    }).join('');
                }

                confirmOverlay.classList.add('is-open');
                confirmOverlay.setAttribute('aria-hidden', 'false');
            }

            function closeConfirmModal() {
                confirmOverlay.classList.remove('is-open');
                confirmOverlay.setAttribute('aria-hidden', 'true');
            }

            function submitWizard() {
                var hiddenSubmit = document.createElement('input');
                hiddenSubmit.type = 'hidden';
                hiddenSubmit.name = 'submit';
                hiddenSubmit.value = '1';
                wizard.appendChild(hiddenSubmit);
                wizard.submit();
            }

            wizard.addEventListener('click', function (event) {
                var target = event.target;

                if (target.matches('[data-next]')) {
                    if (currentStep === 0 && !validateGuestStep()) {
                        return;
                    }

                    setStep(currentStep + 1);
                    return;
                }

                if (target.matches('[data-back]')) {
                    setStep(currentStep - 1);
                    return;
                }

                if (target.matches('[data-step-target]')) {
                    var requestedStep = parseInt(target.getAttribute('data-step-target'), 10) || 0;

                    if (requestedStep > currentStep && currentStep === 0 && !validateGuestStep()) {
                        return;
                    }

                    setStep(requestedStep);
                }
            });

            wizard.addEventListener('change', function (event) {
                if (event.target.matches('input[name="service_id[]"], input[name^="qty["]')) {
                    updateServiceCards();
                }
            });

            wizard.addEventListener('input', function (event) {
                if (event.target.matches('input[name^="qty["]')) {
                    updateSummary();
                }
            });

            if (proceedButton) {
                proceedButton.addEventListener('click', function () {
                    if (!validateGuestStep()) {
                        setStep(0);
                        return;
                    }

                    if (!validateSelection()) {
                        return;
                    }

                    openConfirmModal();
                });
            }

            if (cancelConfirm) {
                cancelConfirm.addEventListener('click', closeConfirmModal);
            }

            if (acceptConfirm) {
                acceptConfirm.addEventListener('click', submitWizard);
            }

            if (confirmOverlay) {
                confirmOverlay.addEventListener('click', function (event) {
                    if (event.target === confirmOverlay) {
                        closeConfirmModal();
                    }
                });
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && confirmOverlay.classList.contains('is-open')) {
                    closeConfirmModal();
                }
            });

            updateServiceCards();
            setStep(0);
        })();
    </script>
</body>
</html>

