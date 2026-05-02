<?php

session_start();
require_once 'config.php';
require_once 'hotel_helpers.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$adminId = (int) $_SESSION['admin_id'];
$adminName = $_SESSION['admin_name'];
$adminRole = $_SESSION['admin_role'];

function redirectWithBanner(string $key, string $message): void
{
    header('Location: admin_dashboard.php?' . http_build_query([$key => $message]));
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_booking_status') {
        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        $allowedStatuses = ['pending', 'confirmed', 'cancelled', 'completed'];

        if ($bookingId > 0 && in_array($status, $allowedStatuses, true)) {
            $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $status, $bookingId);
            $stmt->execute();
            $stmt->close();
            redirectWithBanner('msg', 'Booking updated successfully.');
        }

        redirectWithBanner('error', 'Invalid booking update request.');
    }

    if ($action === 'delete_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId > 0) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
            redirectWithBanner('msg', 'Client account deleted successfully.');
        }

        redirectWithBanner('error', 'Invalid client record.');
    }

    if ($action === 'approve_feedback') {
        $feedbackId = (int) ($_POST['feedback_id'] ?? 0);

        if ($feedbackId > 0) {
            $stmt = $conn->prepare("UPDATE feedback SET is_approved = 1 WHERE id = ?");
            $stmt->bind_param('i', $feedbackId);
            $stmt->execute();
            $stmt->close();
            redirectWithBanner('msg', 'Feedback approved.');
        }

        redirectWithBanner('error', 'Invalid feedback record.');
    }

    if ($action === 'delete_feedback') {
        $feedbackId = (int) ($_POST['feedback_id'] ?? 0);

        if ($feedbackId > 0) {
            $stmt = $conn->prepare("DELETE FROM feedback WHERE id = ?");
            $stmt->bind_param('i', $feedbackId);
            $stmt->execute();
            $stmt->close();
            redirectWithBanner('msg', 'Feedback deleted.');
        }

        redirectWithBanner('error', 'Invalid feedback record.');
    }

    if ($action === 'add_staff') {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = trim((string) ($_POST['role'] ?? 'staff'));
        $department = trim((string) ($_POST['department'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $shift = trim((string) ($_POST['shift_schedule'] ?? ''));

        if ($fullName === '' || $email === '' || $password === '') {
            redirectWithBanner('error', 'Full name, email, and password are required for new staff accounts.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectWithBanner('error', 'Please enter a valid staff email address.');
        }

        $allowedRoles = getRoleOptions();
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'staff';
        }

        $checkStmt = $conn->prepare("SELECT id FROM admins WHERE email = ? LIMIT 1");
        $checkStmt->bind_param('s', $email);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($exists) {
            redirectWithBanner('error', 'A staff account with that email already exists.');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insertStmt = $conn->prepare("
            INSERT INTO admins (full_name, email, password, role, department, phone, shift_schedule, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        $insertStmt->bind_param('sssssss', $fullName, $email, $hashedPassword, $role, $department, $phone, $shift);

        if ($insertStmt->execute()) {
            $insertStmt->close();
            redirectWithBanner('msg', 'Staff account created successfully.');
        }

        $insertStmt->close();
        redirectWithBanner('error', 'Failed to create the staff account.');
    }

    if ($action === 'toggle_staff_status') {
        $staffId = (int) ($_POST['staff_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? 'inactive');

        if ($staffId === $adminId && $status !== 'active') {
            redirectWithBanner('error', 'You cannot deactivate the account you are currently using.');
        }

        if ($staffId > 0 && in_array($status, ['active', 'inactive'], true)) {
            $stmt = $conn->prepare("UPDATE admins SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $status, $staffId);
            $stmt->execute();
            $stmt->close();
            redirectWithBanner('msg', 'Staff status updated.');
        }

        redirectWithBanner('error', 'Invalid staff update request.');
    }

    if ($action === 'delete_staff') {
        $staffId = (int) ($_POST['staff_id'] ?? 0);

        if ($staffId === $adminId) {
            redirectWithBanner('error', 'You cannot delete the account you are currently using.');
        }

        if ($staffId > 0) {
            $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->bind_param('i', $staffId);
            $stmt->execute();
            $stmt->close();
            redirectWithBanner('msg', 'Staff record deleted.');
        }

        redirectWithBanner('error', 'Invalid staff record.');
    }

    if ($action === 'save_service') {
        $category = trim((string) ($_POST['category'] ?? ''));
        $menuSection = trim((string) ($_POST['menu_section'] ?? 'General'));
        $serviceName = trim((string) ($_POST['service_name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $price = (float) ($_POST['price'] ?? 0);
        $pricingUnit = trim((string) ($_POST['pricing_unit'] ?? 'item'));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isAvailable = isset($_POST['is_available']) ? 1 : 0;

        if ($serviceName === '' || $category === '' || $price <= 0) {
            redirectWithBanner('error', 'Category, service name, and a valid price are required.');
        }

        if (!in_array($category, getCategoryOptions(), true)) {
            redirectWithBanner('error', 'Please choose a valid service category.');
        }

        $checkStmt = $conn->prepare("SELECT id FROM services WHERE category = ? AND service_name = ? LIMIT 1");
        $checkStmt->bind_param('ss', $category, $serviceName);
        $checkStmt->execute();
        $existingService = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($existingService) {
            $serviceId = (int) $existingService['id'];
            $updateStmt = $conn->prepare("
                UPDATE services
                SET menu_section = ?, description = ?, price = ?, pricing_unit = ?, sort_order = ?, is_available = ?
                WHERE id = ?
            ");
            $updateStmt->bind_param('ssdsiii', $menuSection, $description, $price, $pricingUnit, $sortOrder, $isAvailable, $serviceId);
            $success = $updateStmt->execute();
            $updateStmt->close();

            if ($success) {
                redirectWithBanner('msg', 'Service updated successfully.');
            }
        } else {
            $insertStmt = $conn->prepare("
                INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->bind_param('ssssdsii', $category, $menuSection, $serviceName, $description, $price, $pricingUnit, $sortOrder, $isAvailable);
            $success = $insertStmt->execute();
            $insertStmt->close();

            if ($success) {
                redirectWithBanner('msg', 'Service added successfully.');
            }
        }

        redirectWithBanner('error', 'Unable to save the service item.');
    }

    if ($action === 'toggle_service') {
        $serviceId = (int) ($_POST['service_id'] ?? 0);
        $isAvailable = (int) ($_POST['is_available'] ?? 0);

        if ($serviceId > 0) {
            $stmt = $conn->prepare("UPDATE services SET is_available = ? WHERE id = ?");
            $stmt->bind_param('ii', $isAvailable, $serviceId);
            $stmt->execute();
            $stmt->close();
            redirectWithBanner('msg', 'Service availability updated.');
        }

        redirectWithBanner('error', 'Invalid service update request.');
    }

    if ($action === 'delete_service') {
        $serviceId = (int) ($_POST['service_id'] ?? 0);

        if ($serviceId > 0) {
            $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
            $stmt->bind_param('i', $serviceId);
            $stmt->execute();
            $stmt->close();
            redirectWithBanner('msg', 'Service deleted from the catalog.');
        }

        redirectWithBanner('error', 'Invalid service record.');
    }

    if ($action === 'update_contact') {
        $fields = [
            'hotel_name',
            'tagline',
            'primary_phone',
            'secondary_phone',
            'whatsapp_number',
            'email',
            'reservation_email',
            'address',
            'front_desk_hours',
            'restaurant_hours',
            'bar_hours',
        ];

        foreach ($fields as $field) {
            $value = trim((string) ($_POST[$field] ?? ''));
            upsertHotelSetting($conn, $field, $value);
        }

        redirectWithBanner('msg', 'Contact and property details updated.');
    }
}

$message = trim((string) ($_GET['msg'] ?? ''));
$errorMessage = trim((string) ($_GET['error'] ?? ''));
$settings = getHotelSettings($conn);
$categoryMeta = getCategoryMeta();

$stats = [];
$stats['total_users'] = (int) ($conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'] ?? 0);
$stats['total_bookings'] = (int) ($conn->query("SELECT COUNT(*) AS total FROM bookings")->fetch_assoc()['total'] ?? 0);
$stats['total_revenue'] = (float) ($conn->query("SELECT COALESCE(SUM(total_price), 0) AS total FROM bookings WHERE status <> 'cancelled'")->fetch_assoc()['total'] ?? 0);
$stats['pending_feedback'] = (int) ($conn->query("SELECT COUNT(*) AS total FROM feedback WHERE is_approved = 0")->fetch_assoc()['total'] ?? 0);
$stats['total_staff'] = (int) ($conn->query("SELECT COUNT(*) AS total FROM admins")->fetch_assoc()['total'] ?? 0);
$stats['active_services'] = (int) ($conn->query("SELECT COUNT(*) AS total FROM services WHERE is_available = 1")->fetch_assoc()['total'] ?? 0);
$stats['restaurant_items'] = (int) ($conn->query("SELECT COUNT(*) AS total FROM services WHERE category = 'restaurant'")->fetch_assoc()['total'] ?? 0);
$stats['bar_items'] = (int) ($conn->query("SELECT COUNT(*) AS total FROM services WHERE category = 'bar'")->fetch_assoc()['total'] ?? 0);

$users = [];
$usersResult = $conn->query("
    SELECT u.*, COUNT(b.id) AS booking_count, COALESCE(SUM(b.total_price), 0) AS total_spent
    FROM users u
    LEFT JOIN bookings b ON b.user_id = u.id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
if ($usersResult instanceof mysqli_result) {
    $users = $usersResult->fetch_all(MYSQLI_ASSOC);
    $usersResult->free();
}

$bookings = [];
$bookingsResult = $conn->query("
    SELECT b.*, u.name, u.email, s.category
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    LEFT JOIN services s ON s.service_name = b.service_name
    ORDER BY b.booking_date DESC
");
if ($bookingsResult instanceof mysqli_result) {
    $bookings = $bookingsResult->fetch_all(MYSQLI_ASSOC);
    $bookingsResult->free();
}

$feedback = [];
$feedbackResult = $conn->query("
    SELECT f.*, u.name, u.email
    FROM feedback f
    JOIN users u ON u.id = f.user_id
    ORDER BY f.created_at DESC
");
if ($feedbackResult instanceof mysqli_result) {
    $feedback = $feedbackResult->fetch_all(MYSQLI_ASSOC);
    $feedbackResult->free();
}

$staff = [];
$staffResult = $conn->query("
    SELECT *
    FROM admins
    ORDER BY FIELD(status, 'active', 'inactive'), full_name ASC
");
if ($staffResult instanceof mysqli_result) {
    $staff = $staffResult->fetch_all(MYSQLI_ASSOC);
    $staffResult->free();
}

$services = [];
$servicesResult = $conn->query("
    SELECT *
    FROM services
    ORDER BY FIELD(category, 'rooms', 'restaurant', 'bar', 'spa', 'gym'), sort_order ASC, service_name ASC
");
if ($servicesResult instanceof mysqli_result) {
    $services = $servicesResult->fetch_all(MYSQLI_ASSOC);
    $servicesResult->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Magic Hotel</title>
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
                radial-gradient(circle at top, rgba(255, 122, 26, 0.12), transparent 30%),
                linear-gradient(180deg, #050505, #0d0d0d 40%, #151515);
            color: #f5f5f5;
        }

        .navbar {
            position: sticky;
            top: 0;
            z-index: 20;
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
        }

        .admin-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            color: #d8d8d8;
        }

        .badge {
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255, 122, 26, 0.14);
            color: #ffcfaa;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .logout-link {
            color: #f1f1f1;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.05);
        }

        .wrap {
            width: min(1280px, 100%);
            margin: 0 auto;
            padding: 36px 20px 60px;
        }

        .hero {
            padding: 30px;
            border-radius: 28px;
            background:
                linear-gradient(135deg, rgba(255, 122, 26, 0.16), rgba(255, 122, 26, 0.03)),
                rgba(10, 10, 10, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 22px;
        }

        .hero h1 {
            color: #ff7a1a;
            font-size: clamp(2rem, 3vw, 3rem);
            margin-bottom: 10px;
        }

        .hero p {
            color: #cccccc;
            line-height: 1.7;
            max-width: 840px;
        }

        .banner {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 16px;
            line-height: 1.6;
        }

        .banner-success {
            background: rgba(45, 166, 93, 0.16);
            border-left: 4px solid #33a35c;
            color: #cbf2d7;
        }

        .banner-error {
            background: rgba(220, 80, 80, 0.14);
            border-left: 4px solid #d9534f;
            color: #ffc0c0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            padding: 22px;
            border-radius: 22px;
            background: rgba(10, 10, 10, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 20px 48px rgba(0, 0, 0, 0.22);
        }

        .stat-card strong {
            display: block;
            margin-bottom: 10px;
            color: #ffcfaa;
            font-size: 0.84rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .stat-card span {
            font-size: 1.95rem;
            font-weight: 700;
            color: #ffffff;
        }

        .tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
        }

        .tab-button {
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 999px;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.04);
            color: #f0f0f0;
            cursor: pointer;
        }

        .tab-button.active {
            background: linear-gradient(135deg, #ff7a1a, #ff9d52);
            color: #151515;
            border-color: transparent;
        }

        .tab-panel {
            display: none;
            padding: 26px;
            border-radius: 28px;
            background: rgba(10, 10, 10, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 24px 56px rgba(0, 0, 0, 0.22);
        }

        .tab-panel.active {
            display: block;
        }

        .panel-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 18px;
        }

        .panel-head h2 {
            color: #ffcfaa;
        }

        .panel-head p {
            color: #c7c7c7;
            line-height: 1.6;
        }

        .table-shell {
            overflow-x: auto;
            border-radius: 20px;
            border: 1px solid #242424;
        }

        table {
            width: 100%;
            min-width: 760px;
            border-collapse: collapse;
            background: #101010;
        }

        th,
        td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #202020;
            vertical-align: top;
        }

        th {
            color: #ffb27d;
            background: #141414;
            font-size: 0.9rem;
        }

        td {
            color: #e2e2e2;
        }

        .status-pill {
            display: inline-flex;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .status-pending {
            background: rgba(255, 122, 26, 0.14);
            color: #ffcfaa;
        }

        .status-confirmed,
        .status-approved,
        .status-active,
        .status-available {
            background: rgba(45, 166, 93, 0.16);
            color: #cbf2d7;
        }

        .status-cancelled,
        .status-inactive,
        .status-unavailable {
            background: rgba(220, 80, 80, 0.14);
            color: #ffc0c0;
        }

        .status-completed {
            background: rgba(64, 163, 215, 0.16);
            color: #c4e8ff;
        }

        .status-review {
            background: rgba(255, 122, 26, 0.14);
            color: #ffcfaa;
        }

        .action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .inline-form {
            display: inline-flex;
        }

        .action-button {
            border: none;
            border-radius: 14px;
            padding: 8px 12px;
            font-size: 0.84rem;
            cursor: pointer;
        }

        .action-primary {
            background: rgba(45, 166, 93, 0.18);
            color: #d4ffe1;
        }

        .action-warn {
            background: rgba(255, 122, 26, 0.16);
            color: #ffcfaa;
        }

        .action-danger {
            background: rgba(220, 80, 80, 0.16);
            color: #ffc4c4;
        }

        .action-muted {
            background: rgba(255, 255, 255, 0.06);
            color: #f1f1f1;
        }

        .two-column {
            display: grid;
            grid-template-columns: 0.95fr 1.05fr;
            gap: 20px;
            margin-bottom: 22px;
        }

        .card {
            padding: 22px;
            border-radius: 24px;
            background: #101010;
            border: 1px solid #222222;
        }

        .card h3 {
            color: #ffcfaa;
            margin-bottom: 8px;
        }

        .card p {
            color: #c7c7c7;
            line-height: 1.7;
            margin-bottom: 14px;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .field {
            margin-bottom: 14px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #dddddd;
            font-size: 0.92rem;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 13px 14px;
            border-radius: 16px;
            border: 1px solid #2c2c2c;
            background: #0b0b0b;
            color: #f4f4f4;
            font: inherit;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #ff7a1a;
            box-shadow: 0 0 0 3px rgba(255, 122, 26, 0.14);
        }

        .submit-button {
            border: none;
            border-radius: 18px;
            padding: 14px 18px;
            background: linear-gradient(135deg, #ff7a1a, #ff9d52);
            color: #151515;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
        }

        .note {
            color: #b9b9b9;
            font-size: 0.92rem;
            line-height: 1.6;
            margin-top: 10px;
        }

        footer {
            padding: 28px 20px 40px;
            text-align: center;
            color: #989898;
        }

        @media (max-width: 960px) {
            .two-column {
                grid-template-columns: 1fr;
            }

            .field-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .navbar {
                padding: 16px 18px;
                flex-direction: column;
                align-items: flex-start;
            }

            .hero,
            .tab-panel {
                padding: 22px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a class="logo" href="admin_dashboard.php"><?php echo htmlspecialchars($settings['hotel_name']); ?> Admin</a>
        <div class="admin-meta">
            <span class="badge"><?php echo htmlspecialchars($adminRole); ?></span>
            <span><?php echo htmlspecialchars($adminName); ?></span>
            <a class="logout-link" href="?logout=1">Log Out</a>
        </div>
    </nav>

    <main class="wrap">
        <section class="hero">
            <h1>Operations and management dashboard</h1>
            <p>Manage bookings, guest records, restaurant and bar catalog items, hotel contact information, and staff access from one control center.</p>
        </section>

        <?php if ($message !== ''): ?>
            <div class="banner banner-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <div class="banner banner-error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <section class="stats-grid">
            <div class="stat-card">
                <strong>Total Clients</strong>
                <span><?php echo $stats['total_users']; ?></span>
            </div>
            <div class="stat-card">
                <strong>Total Bookings</strong>
                <span><?php echo $stats['total_bookings']; ?></span>
            </div>
            <div class="stat-card">
                <strong>Total Revenue</strong>
                <span><?php echo htmlspecialchars(formatUgx($stats['total_revenue'])); ?></span>
            </div>
            <div class="stat-card">
                <strong>Pending Feedback</strong>
                <span><?php echo $stats['pending_feedback']; ?></span>
            </div>
            <div class="stat-card">
                <strong>Total Staff</strong>
                <span><?php echo $stats['total_staff']; ?></span>
            </div>
            <div class="stat-card">
                <strong>Active Services</strong>
                <span><?php echo $stats['active_services']; ?></span>
            </div>
            <div class="stat-card">
                <strong>Restaurant Items</strong>
                <span><?php echo $stats['restaurant_items']; ?></span>
            </div>
            <div class="stat-card">
                <strong>Bar Items</strong>
                <span><?php echo $stats['bar_items']; ?></span>
            </div>
        </section>

        <section class="tabs">
            <button class="tab-button active" data-tab="bookings">Bookings</button>
            <button class="tab-button" data-tab="clients">Clients</button>
            <button class="tab-button" data-tab="feedback">Feedback</button>
            <button class="tab-button" data-tab="staff">Staff</button>
            <button class="tab-button" data-tab="services">Services</button>
            <button class="tab-button" data-tab="contact">Contact Info</button>
        </section>

        <section id="bookings" class="tab-panel active">
            <div class="panel-head">
                <div>
                    <h2>Booking Operations</h2>
                    <p>Review room, restaurant, bar, spa, and gym orders and update their current status.</p>
                </div>
            </div>

            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Guest</th>
                            <th>Service</th>
                            <th>Category</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bookings)): ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo (int) $booking['id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['name']); ?><br><span style="color:#a9a9a9;"><?php echo htmlspecialchars($booking['email']); ?></span></td>
                                    <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                    <td><?php echo htmlspecialchars($categoryMeta[$booking['category']]['label'] ?? 'General'); ?></td>
                                    <td><?php echo (int) $booking['quantity']; ?></td>
                                    <td><?php echo htmlspecialchars(formatUgx((float) $booking['total_price'])); ?></td>
                                    <td><?php echo htmlspecialchars(date('d M Y', strtotime($booking['booking_date']))); ?></td>
                                    <td>
                                        <span class="status-pill status-<?php echo htmlspecialchars($booking['status']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-row">
                                            <form class="inline-form" method="POST">
                                                <input type="hidden" name="action" value="update_booking_status">
                                                <input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>">
                                                <input type="hidden" name="status" value="confirmed">
                                                <button class="action-button action-primary" type="submit">Confirm</button>
                                            </form>
                                            <form class="inline-form" method="POST">
                                                <input type="hidden" name="action" value="update_booking_status">
                                                <input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>">
                                                <input type="hidden" name="status" value="completed">
                                                <button class="action-button action-muted" type="submit">Complete</button>
                                            </form>
                                            <form class="inline-form" method="POST">
                                                <input type="hidden" name="action" value="update_booking_status">
                                                <input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>">
                                                <input type="hidden" name="status" value="cancelled">
                                                <button class="action-button action-danger" type="submit">Cancel</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9">No bookings found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="clients" class="tab-panel">
            <div class="panel-head">
                <div>
                    <h2>Client Accounts</h2>
                    <p>Track guest profiles, spending, and booking volume.</p>
                </div>
            </div>

            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Bookings</th>
                            <th>Total Spent</th>
                            <th>Joined</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>#<?php echo (int) $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td><?php echo (int) $user['booking_count']; ?></td>
                                    <td><?php echo htmlspecialchars(formatUgx((float) $user['total_spent'])); ?></td>
                                    <td><?php echo htmlspecialchars(date('d M Y', strtotime($user['created_at']))); ?></td>
                                    <td>
                                        <form class="inline-form" method="POST" onsubmit="return confirm('Delete this client account and all related bookings?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                            <button class="action-button action-danger" type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8">No client records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="feedback" class="tab-panel">
            <div class="panel-head">
                <div>
                    <h2>Guest Feedback</h2>
                    <p>Review guest comments and approve them for internal reporting.</p>
                </div>
            </div>

            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Guest</th>
                            <th>Service</th>
                            <th>Category</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($feedback)): ?>
                            <?php foreach ($feedback as $item): ?>
                                <tr>
                                    <td>#<?php echo (int) $item['id']; ?></td>
                                    <td><?php echo htmlspecialchars($item['name']); ?><br><span style="color:#a9a9a9;"><?php echo htmlspecialchars($item['email']); ?></span></td>
                                    <td><?php echo htmlspecialchars($item['service_name']); ?></td>
                                    <td><?php echo htmlspecialchars($categoryMeta[$item['service_category']]['label'] ?? ucfirst($item['service_category'])); ?></td>
                                    <td><?php echo (int) $item['rating']; ?>/5</td>
                                    <td style="max-width: 340px; white-space: normal;"><?php echo nl2br(htmlspecialchars($item['comment'])); ?></td>
                                    <td>
                                        <span class="status-pill <?php echo ((int) $item['is_approved'] === 1) ? 'status-approved' : 'status-review'; ?>">
                                            <?php echo ((int) $item['is_approved'] === 1) ? 'Approved' : 'Pending'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-row">
                                            <?php if ((int) $item['is_approved'] !== 1): ?>
                                                <form class="inline-form" method="POST">
                                                    <input type="hidden" name="action" value="approve_feedback">
                                                    <input type="hidden" name="feedback_id" value="<?php echo (int) $item['id']; ?>">
                                                    <button class="action-button action-primary" type="submit">Approve</button>
                                                </form>
                                            <?php endif; ?>
                                            <form class="inline-form" method="POST" onsubmit="return confirm('Delete this feedback item?');">
                                                <input type="hidden" name="action" value="delete_feedback">
                                                <input type="hidden" name="feedback_id" value="<?php echo (int) $item['id']; ?>">
                                                <button class="action-button action-danger" type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8">No feedback has been submitted yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="staff" class="tab-panel">
            <div class="panel-head">
                <div>
                    <h2>Staff Management</h2>
                    <p>Create new staff accounts and control who can access the management system.</p>
                </div>
            </div>

            <div class="two-column">
                <div class="card">
                    <h3>Add Staff Member</h3>
                    <p>New staff accounts can log in from the admin portal immediately after creation.</p>

                    <form method="POST">
                        <input type="hidden" name="action" value="add_staff">

                        <div class="field-grid">
                            <div class="field">
                                <label for="full_name">Full Name</label>
                                <input id="full_name" type="text" name="full_name" required>
                            </div>

                            <div class="field">
                                <label for="staff_email">Email Address</label>
                                <input id="staff_email" type="email" name="email" required>
                            </div>

                            <div class="field">
                                <label for="staff_password">Password</label>
                                <input id="staff_password" type="password" name="password" required>
                            </div>

                            <div class="field">
                                <label for="staff_role">Role</label>
                                <select id="staff_role" name="role">
                                    <?php foreach (getRoleOptions() as $role): ?>
                                        <option value="<?php echo htmlspecialchars($role); ?>"><?php echo htmlspecialchars(ucwords($role)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="field">
                                <label for="staff_department">Department</label>
                                <input id="staff_department" type="text" name="department" placeholder="Front Office">
                            </div>

                            <div class="field">
                                <label for="staff_phone">Phone</label>
                                <input id="staff_phone" type="text" name="phone" placeholder="+256...">
                            </div>

                            <div class="field full">
                                <label for="staff_shift">Shift or Schedule</label>
                                <input id="staff_shift" type="text" name="shift_schedule" placeholder="Day Shift">
                            </div>
                        </div>

                        <button class="submit-button" type="submit">Create Staff Account</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Management Notes</h3>
                    <p>Use roles to separate front office, restaurant, bar, housekeeping, and other operational areas.</p>
                    <div class="note">
                        Tip: staff passwords are stored as secure hashes. If an older record still uses a plain password, it will be upgraded automatically after the next successful login.
                    </div>
                </div>
            </div>

            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Shift</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($staff)): ?>
                            <?php foreach ($staff as $member): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['full_name']); ?><br><span style="color:#a9a9a9;"><?php echo htmlspecialchars($member['phone'] ?? ''); ?></span></td>
                                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td><?php echo htmlspecialchars(ucwords($member['role'])); ?></td>
                                    <td><?php echo htmlspecialchars($member['department'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($member['shift_schedule'] ?? ''); ?></td>
                                    <td>
                                        <span class="status-pill status-<?php echo htmlspecialchars($member['status']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($member['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $member['last_login'] ? htmlspecialchars(date('d M Y H:i', strtotime($member['last_login']))) : 'Never'; ?></td>
                                    <td>
                                        <div class="action-row">
                                            <form class="inline-form" method="POST">
                                                <input type="hidden" name="action" value="toggle_staff_status">
                                                <input type="hidden" name="staff_id" value="<?php echo (int) $member['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $member['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                <button class="action-button action-warn" type="submit">
                                                    <?php echo $member['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                            <form class="inline-form" method="POST" onsubmit="return confirm('Delete this staff account?');">
                                                <input type="hidden" name="action" value="delete_staff">
                                                <input type="hidden" name="staff_id" value="<?php echo (int) $member['id']; ?>">
                                                <button class="action-button action-danger" type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8">No staff records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="services" class="tab-panel">
            <div class="panel-head">
                <div>
                    <h2>Service Catalog</h2>
                    <p>Add or update hotel, restaurant, and bar items. Reusing the same category and service name updates the existing item.</p>
                </div>
            </div>

            <div class="two-column">
                <div class="card">
                    <h3>Add or Update Service</h3>

                    <form method="POST">
                        <input type="hidden" name="action" value="save_service">

                        <div class="field-grid">
                            <div class="field">
                                <label for="category">Category</label>
                                <select id="category" name="category" required>
                                    <?php foreach (getCategoryOptions() as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($categoryMeta[$category]['label']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="field">
                                <label for="menu_section">Menu or Section</label>
                                <input id="menu_section" type="text" name="menu_section" placeholder="Starters or Accommodation" required>
                            </div>

                            <div class="field full">
                                <label for="service_name">Service Name</label>
                                <input id="service_name" type="text" name="service_name" required>
                            </div>

                            <div class="field full">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" placeholder="Short service description"></textarea>
                            </div>

                            <div class="field">
                                <label for="price">Price</label>
                                <input id="price" type="number" name="price" min="0" step="0.01" required>
                            </div>

                            <div class="field">
                                <label for="pricing_unit">Pricing Unit</label>
                                <input id="pricing_unit" type="text" name="pricing_unit" placeholder="plate, night, glass" required>
                            </div>

                            <div class="field">
                                <label for="sort_order">Sort Order</label>
                                <input id="sort_order" type="number" name="sort_order" min="0" value="0">
                            </div>

                            <div class="field">
                                <label style="margin-bottom: 12px;">Availability</label>
                                <label style="display:flex; gap:10px; align-items:center; margin:0;">
                                    <input type="checkbox" name="is_available" checked style="width:auto;">
                                    <span>Available for booking</span>
                                </label>
                            </div>
                        </div>

                        <button class="submit-button" type="submit">Save Service</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Catalog Notes</h3>
                    <p>The booking page, guest dashboard, services page, and feedback form all use this same catalog.</p>
                    <div class="note">
                        This means price updates, new bar items, and service availability changes will show up consistently across the site.
                    </div>
                </div>
            </div>

            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Section</th>
                            <th>Price</th>
                            <th>Unit</th>
                            <th>Sort</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($services)): ?>
                            <?php foreach ($services as $service): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($service['service_name']); ?><br><span style="color:#a9a9a9;"><?php echo htmlspecialchars($service['description']); ?></span></td>
                                    <td><?php echo htmlspecialchars($categoryMeta[$service['category']]['label'] ?? ucfirst($service['category'])); ?></td>
                                    <td><?php echo htmlspecialchars($service['menu_section']); ?></td>
                                    <td><?php echo htmlspecialchars(formatUgx((float) $service['price'])); ?></td>
                                    <td><?php echo htmlspecialchars($service['pricing_unit']); ?></td>
                                    <td><?php echo (int) $service['sort_order']; ?></td>
                                    <td>
                                        <span class="status-pill <?php echo ((int) $service['is_available'] === 1) ? 'status-available' : 'status-unavailable'; ?>">
                                            <?php echo ((int) $service['is_available'] === 1) ? 'Available' : 'Unavailable'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-row">
                                            <form class="inline-form" method="POST">
                                                <input type="hidden" name="action" value="toggle_service">
                                                <input type="hidden" name="service_id" value="<?php echo (int) $service['id']; ?>">
                                                <input type="hidden" name="is_available" value="<?php echo ((int) $service['is_available'] === 1) ? 0 : 1; ?>">
                                                <button class="action-button action-warn" type="submit">
                                                    <?php echo ((int) $service['is_available'] === 1) ? 'Hide' : 'Show'; ?>
                                                </button>
                                            </form>
                                            <form class="inline-form" method="POST" onsubmit="return confirm('Delete this service item?');">
                                                <input type="hidden" name="action" value="delete_service">
                                                <input type="hidden" name="service_id" value="<?php echo (int) $service['id']; ?>">
                                                <button class="action-button action-danger" type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8">No service items found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="contact" class="tab-panel">
            <div class="panel-head">
                <div>
                    <h2>Contact and Property Details</h2>
                    <p>These values are reused across the public contact page, booking experience, and guest dashboard.</p>
                </div>
            </div>

            <div class="card">
                <form method="POST">
                    <input type="hidden" name="action" value="update_contact">

                    <div class="field-grid">
                        <div class="field">
                            <label for="hotel_name">Hotel Name</label>
                            <input id="hotel_name" type="text" name="hotel_name" value="<?php echo htmlspecialchars($settings['hotel_name']); ?>">
                        </div>

                        <div class="field">
                            <label for="tagline">Tagline</label>
                            <input id="tagline" type="text" name="tagline" value="<?php echo htmlspecialchars($settings['tagline']); ?>">
                        </div>

                        <div class="field">
                            <label for="primary_phone">Primary Phone</label>
                            <input id="primary_phone" type="text" name="primary_phone" value="<?php echo htmlspecialchars($settings['primary_phone']); ?>">
                        </div>

                        <div class="field">
                            <label for="secondary_phone">Secondary Phone</label>
                            <input id="secondary_phone" type="text" name="secondary_phone" value="<?php echo htmlspecialchars($settings['secondary_phone']); ?>">
                        </div>

                        <div class="field">
                            <label for="whatsapp_number">WhatsApp Number</label>
                            <input id="whatsapp_number" type="text" name="whatsapp_number" value="<?php echo htmlspecialchars($settings['whatsapp_number']); ?>">
                        </div>

                        <div class="field">
                            <label for="email">General Email</label>
                            <input id="email" type="email" name="email" value="<?php echo htmlspecialchars($settings['email']); ?>">
                        </div>

                        <div class="field">
                            <label for="reservation_email">Reservation Email</label>
                            <input id="reservation_email" type="email" name="reservation_email" value="<?php echo htmlspecialchars($settings['reservation_email']); ?>">
                        </div>

                        <div class="field">
                            <label for="front_desk_hours">Front Desk Hours</label>
                            <input id="front_desk_hours" type="text" name="front_desk_hours" value="<?php echo htmlspecialchars($settings['front_desk_hours']); ?>">
                        </div>

                        <div class="field">
                            <label for="restaurant_hours">Restaurant Hours</label>
                            <input id="restaurant_hours" type="text" name="restaurant_hours" value="<?php echo htmlspecialchars($settings['restaurant_hours']); ?>">
                        </div>

                        <div class="field">
                            <label for="bar_hours">Bar Hours</label>
                            <input id="bar_hours" type="text" name="bar_hours" value="<?php echo htmlspecialchars($settings['bar_hours']); ?>">
                        </div>

                        <div class="field full">
                            <label for="address">Address</label>
                            <textarea id="address" name="address"><?php echo htmlspecialchars($settings['address']); ?></textarea>
                        </div>
                    </div>

                    <button class="submit-button" type="submit">Save Contact Details</button>
                </form>
            </div>
        </section>
    </main>

    <footer>
        <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['hotel_name']); ?> management dashboard.
    </footer>

    <script>
        var tabButtons = document.querySelectorAll('.tab-button');
        var tabPanels = document.querySelectorAll('.tab-panel');

        tabButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var target = button.getAttribute('data-tab');

                tabButtons.forEach(function (item) {
                    item.classList.remove('active');
                });

                tabPanels.forEach(function (panel) {
                    panel.classList.remove('active');
                });

                button.classList.add('active');
                document.getElementById(target).classList.add('active');
            });
        });
    </script>
</body>
</html>
