<?php

session_start();
require_once 'config.php';
require_once 'hotel_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
$settings = getHotelSettings($conn);
$categoryMeta = getCategoryMeta();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_booking'])) {
    header('Content-Type: application/json');

    $serviceIds = $_POST['service_id'] ?? [];
    $quantities = $_POST['qty'] ?? [];
    $created = createBookingsFromSelection($conn, $userId, $serviceIds, $quantities, $bookingError);

    if ($created > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Your booking request has been added to your dashboard.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $bookingError !== '' ? $bookingError : 'We could not create your booking.'
        ]);
    }

    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$catalog = getServiceCatalog($conn, true);

$bookingStmt = $conn->prepare("
    SELECT b.*, s.category
    FROM bookings b
    LEFT JOIN services s ON s.service_name = b.service_name
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC
");
$bookingStmt->bind_param('i', $userId);
$bookingStmt->execute();
$bookingResult = $bookingStmt->get_result();
$bookings = $bookingResult->fetch_all(MYSQLI_ASSOC);
$bookingStmt->close();

$feedbackStmt = $conn->prepare("
    SELECT service_category, service_name, rating, comment, created_at, is_approved
    FROM feedback
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 6
");
$feedbackStmt->bind_param('i', $userId);
$feedbackStmt->execute();
$feedbackResult = $feedbackStmt->get_result();
$feedback = $feedbackResult->fetch_all(MYSQLI_ASSOC);
$feedbackStmt->close();

$totalBookings = count($bookings);
$totalSpent = 0.0;
$pendingBookings = 0;
$confirmedBookings = 0;

foreach ($bookings as $booking) {
    $totalSpent += (float) $booking['total_price'];

    if ($booking['status'] === 'pending') {
        $pendingBookings++;
    }

    if ($booking['status'] === 'confirmed') {
        $confirmedBookings++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Magic Hotel</title>
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
                linear-gradient(180deg, #060606, #0c0c0c 38%, #151515);
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

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: center;
        }

        .nav-links a {
            color: #e8e8e8;
            text-decoration: none;
        }

        .nav-links a:hover {
            color: #ffb27d;
        }

        .logout-link {
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255, 122, 26, 0.14);
            color: #ffcfaa;
        }

        .wrap {
            width: min(1180px, 100%);
            margin: 0 auto;
            padding: 40px 20px 60px;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .hero-card,
        .panel {
            padding: 28px;
            border-radius: 28px;
            background: rgba(10, 10, 10, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 24px 56px rgba(0, 0, 0, 0.24);
        }

        .hero-card h1 {
            font-size: clamp(2rem, 3vw, 3rem);
            color: #ff7a1a;
            margin-bottom: 10px;
        }

        .hero-card p,
        .panel p {
            color: #c7c7c7;
            line-height: 1.7;
        }

        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 22px;
        }

        .button,
        .button-secondary {
            border: none;
            border-radius: 18px;
            padding: 14px 18px;
            font-weight: 700;
            font-size: 0.98rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .button {
            background: linear-gradient(135deg, #ff7a1a, #ff9d52);
            color: #151515;
        }

        .button-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: #f1f1f1;
            border: 1px solid rgba(255, 255, 255, 0.08);
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
            background: #101010;
            border: 1px solid #232323;
        }

        .stat-card strong {
            display: block;
            color: #ffcfaa;
            font-size: 0.86rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 10px;
        }

        .stat-card span {
            font-size: 2rem;
            color: #ffffff;
            font-weight: 700;
        }

        .sections {
            display: grid;
            gap: 24px;
        }

        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .section-title h2 {
            color: #ffcfaa;
        }

        .table-shell {
            overflow-x: auto;
            border-radius: 20px;
            border: 1px solid #242424;
        }

        table {
            width: 100%;
            min-width: 700px;
            border-collapse: collapse;
            background: #101010;
        }

        th,
        td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #202020;
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

        .status-confirmed {
            background: rgba(45, 166, 93, 0.16);
            color: #cbf2d7;
        }

        .status-cancelled {
            background: rgba(220, 80, 80, 0.14);
            color: #ffc0c0;
        }

        .status-completed {
            background: rgba(64, 163, 215, 0.16);
            color: #c4e8ff;
        }

        .feedback-list {
            display: grid;
            gap: 14px;
        }

        .feedback-item {
            padding: 18px;
            border-radius: 18px;
            background: #111111;
            border: 1px solid #232323;
        }

        .feedback-item strong {
            color: #ffffff;
        }

        .feedback-meta {
            color: #bcbcbc;
            margin-top: 6px;
            font-size: 0.94rem;
        }

        .empty-state {
            padding: 24px;
            border-radius: 22px;
            background: #101010;
            border: 1px solid #232323;
            color: #d1d1d1;
            line-height: 1.7;
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 30;
            background: rgba(0, 0, 0, 0.84);
            overflow-y: auto;
            padding: 26px 18px;
        }

        .modal-card {
            width: min(980px, 100%);
            margin: 0 auto;
            padding: 26px;
            border-radius: 28px;
            background: #0d0d0d;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .modal-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 18px;
        }

        .modal-head h2 {
            color: #ff7a1a;
        }

        .close-button {
            background: transparent;
            border: none;
            color: #f5f5f5;
            font-size: 2rem;
            cursor: pointer;
        }

        .service-category {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .service-category:first-of-type {
            margin-top: 0;
            padding-top: 0;
            border-top: none;
        }

        .category-label {
            color: #ffb27d;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.86rem;
            margin-bottom: 8px;
        }

        .category-title {
            color: #ffffff;
            font-size: 1.45rem;
            margin-bottom: 8px;
        }

        .section-label {
            margin: 18px 0 12px;
            color: #ffcfaa;
            font-size: 0.92rem;
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

        .service-title {
            color: #ffffff;
            margin-bottom: 6px;
        }

        .service-desc {
            color: #bcbcbc;
            line-height: 1.6;
            font-size: 0.94rem;
        }

        .service-side {
            min-width: 150px;
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
            padding: 8px 12px;
            border-radius: 999px;
            background: #0b0b0b;
            border: 1px solid #262626;
        }

        .qty-box input {
            width: 68px;
            background: transparent;
            border: none;
            color: #f5f5f5;
            text-align: center;
            padding: 6px 8px;
        }

        .modal-total {
            margin-top: 22px;
            padding: 22px;
            border-radius: 24px;
            background: linear-gradient(135deg, rgba(255, 122, 26, 0.12), rgba(255, 122, 26, 0.02));
            border: 1px solid rgba(255, 122, 26, 0.18);
        }

        .modal-total strong {
            display: block;
            color: #ff7a1a;
            font-size: 2rem;
            margin-top: 8px;
        }

        .toast {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 40;
            min-width: 280px;
            padding: 14px 18px;
            border-radius: 16px;
            background: #1f1f1f;
            color: #f5f5f5;
            display: none;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.3);
        }

        .toast.success {
            border-left: 4px solid #33a35c;
        }

        .toast.error {
            border-left: 4px solid #d9534f;
        }

        footer {
            padding: 28px 20px 40px;
            text-align: center;
            color: #989898;
        }

        @media (max-width: 920px) {
            .hero {
                grid-template-columns: 1fr;
            }

            .service-item {
                grid-template-columns: auto 1fr;
            }

            .service-side {
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
            .panel,
            .modal-card {
                padding: 22px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a class="logo" href="client_dashboard.php"><?php echo htmlspecialchars($settings['hotel_name']); ?></a>
        <div class="nav-links">
            <a href="services.php">Services</a>
            <a href="contact.php">Contact</a>
            <a href="feedback.php">Feedback</a>
            <a class="logout-link" href="?logout=1">Log Out</a>
        </div>
    </nav>

    <main class="wrap">
        <section class="hero">
            <div class="hero-card">
                <h1>Welcome back, <?php echo htmlspecialchars($userName); ?></h1>
                <p>Manage your stay, book restaurant and bar items, and keep track of your hotel activity from one guest dashboard.</p>

                <div class="quick-actions">
                    <button id="openBookingModal" class="button" type="button">New Booking</button>
                    <a class="button-secondary" href="feedback.php">Share Feedback</a>
                    <a class="button-secondary" href="contact.php">Contact Hotel</a>
                </div>
            </div>

            <div class="hero-card">
                <h1 style="font-size: 1.8rem;">Guest Profile</h1>
                <p><?php echo htmlspecialchars($userEmail); ?></p>
                <div class="quick-actions" style="margin-top: 18px;">
                    <div class="button-secondary" style="cursor: default;">Restaurant: <?php echo htmlspecialchars($settings['restaurant_hours']); ?></div>
                    <div class="button-secondary" style="cursor: default;">Bar: <?php echo htmlspecialchars($settings['bar_hours']); ?></div>
                </div>
            </div>
        </section>

        <section class="stats-grid">
            <div class="stat-card">
                <strong>Total Bookings</strong>
                <span><?php echo $totalBookings; ?></span>
            </div>
            <div class="stat-card">
                <strong>Total Spent</strong>
                <span><?php echo htmlspecialchars(formatUgx($totalSpent)); ?></span>
            </div>
            <div class="stat-card">
                <strong>Pending</strong>
                <span><?php echo $pendingBookings; ?></span>
            </div>
            <div class="stat-card">
                <strong>Confirmed</strong>
                <span><?php echo $confirmedBookings; ?></span>
            </div>
        </section>

        <section class="sections">
            <div class="panel">
                <div class="section-title">
                    <h2>Booking History</h2>
                    <a class="button-secondary" href="booking.php">Full Booking Page</a>
                </div>

                <?php if (!empty($bookings)): ?>
                    <div class="table-shell">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Service</th>
                                    <th>Category</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td>#<?php echo (int) $booking['id']; ?></td>
                                        <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                        <td><?php echo htmlspecialchars($categoryMeta[$booking['category']]['label'] ?? 'General'); ?></td>
                                        <td><?php echo (int) $booking['quantity']; ?></td>
                                        <td><?php echo htmlspecialchars(formatUgx((float) $booking['price_per_unit'])); ?></td>
                                        <td><?php echo htmlspecialchars(formatUgx((float) $booking['total_price'])); ?></td>
                                        <td><?php echo htmlspecialchars(date('d M Y', strtotime($booking['booking_date']))); ?></td>
                                        <td>
                                            <span class="status-pill status-<?php echo htmlspecialchars($booking['status']); ?>">
                                                <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        You have not made any bookings yet. Use the new booking button above to book rooms, meals, bar items, or wellness services.
                    </div>
                <?php endif; ?>
            </div>

            <div class="panel">
                <div class="section-title">
                    <h2>Recent Feedback</h2>
                    <a class="button-secondary" href="feedback.php">Add Feedback</a>
                </div>

                <?php if (!empty($feedback)): ?>
                    <div class="feedback-list">
                        <?php foreach ($feedback as $entry): ?>
                            <article class="feedback-item">
                                <strong><?php echo htmlspecialchars($entry['service_name']); ?></strong>
                                <div class="feedback-meta">
                                    <?php echo htmlspecialchars($categoryMeta[$entry['service_category']]['label'] ?? ucfirst($entry['service_category'])); ?>
                                    | Rating <?php echo (int) $entry['rating']; ?>/5
                                    | <?php echo htmlspecialchars(date('d M Y', strtotime($entry['created_at']))); ?>
                                </div>
                                <p style="margin-top: 10px;"><?php echo nl2br(htmlspecialchars($entry['comment'])); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        You have not submitted feedback yet. Share your thoughts after your next booking.
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <div id="bookingModal" class="modal" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-head">
                <h2>Create a New Booking</h2>
                <button class="close-button" type="button" aria-label="Close">&times;</button>
            </div>

            <p style="color: #c6c6c6; line-height: 1.7;">Select any available room, restaurant item, bar item, or wellness service below. Pricing comes from the live service catalog managed by the hotel team.</p>

            <form id="bookingForm">
                <?php foreach ($catalog as $category => $sections): ?>
                    <div class="service-category">
                        <div class="category-label"><?php echo htmlspecialchars($categoryMeta[$category]['label'] ?? ucfirst($category)); ?></div>
                        <div class="category-title"><?php echo htmlspecialchars($categoryMeta[$category]['label'] ?? ucfirst($category)); ?></div>
                        <p style="color: #bfbfbf;"><?php echo htmlspecialchars($categoryMeta[$category]['summary'] ?? ''); ?></p>

                        <?php foreach ($sections as $sectionName => $services): ?>
                            <div class="section-label"><?php echo htmlspecialchars($sectionName); ?></div>
                            <div class="service-list">
                                <?php foreach ($services as $service): ?>
                                    <label class="service-item">
                                        <div style="margin-top: 4px;">
                                            <input
                                                type="checkbox"
                                                name="service_id[]"
                                                value="<?php echo (int) $service['id']; ?>"
                                                data-price="<?php echo (float) $service['price']; ?>"
                                                onchange="calculateModalTotal()"
                                            >
                                        </div>

                                        <div>
                                            <div class="service-title"><?php echo htmlspecialchars($service['service_name']); ?></div>
                                            <div class="service-desc"><?php echo htmlspecialchars((string) $service['description']); ?></div>
                                        </div>

                                        <div class="service-side">
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
                                                    onchange="calculateModalTotal()"
                                                >
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <div class="modal-total">
                    <span style="color: #cccccc;">Total quote</span>
                    <strong id="modalTotal">UGX 0</strong>
                </div>

                <button id="submitBookingBtn" class="button" type="button" style="width: 100%; margin-top: 18px;">Confirm Booking</button>
            </form>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <footer>
        <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['hotel_name']); ?>. <?php echo htmlspecialchars($settings['tagline']); ?>
    </footer>

    <script>
        var bookingModal = document.getElementById('bookingModal');
        var openBookingModalButton = document.getElementById('openBookingModal');
        var closeBookingModalButton = document.querySelector('.close-button');
        var submitBookingButton = document.getElementById('submitBookingBtn');

        function formatCurrency(value) {
            return 'UGX ' + value.toLocaleString();
        }

        function showToast(message, isSuccess) {
            var toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + (isSuccess ? 'success' : 'error');
            toast.style.display = 'block';

            setTimeout(function () {
                toast.style.display = 'none';
            }, 4000);
        }

        function openBookingModal() {
            bookingModal.style.display = 'block';
            bookingModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeBookingModal() {
            bookingModal.style.display = 'none';
            bookingModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        function calculateModalTotal() {
            var total = 0;
            var selected = document.querySelectorAll('#bookingForm input[name="service_id[]"]:checked');

            selected.forEach(function (checkbox) {
                var serviceId = checkbox.value;
                var quantityInput = document.querySelector('#bookingForm input[name="qty[' + serviceId + ']"]');
                var quantity = quantityInput ? parseInt(quantityInput.value, 10) || 1 : 1;
                var price = parseFloat(checkbox.getAttribute('data-price')) || 0;
                total += price * quantity;
            });

            document.getElementById('modalTotal').textContent = formatCurrency(total);
        }

        function resetBookingForm() {
            document.querySelectorAll('#bookingForm input[name="service_id[]"]').forEach(function (checkbox) {
                checkbox.checked = false;
            });

            document.querySelectorAll('#bookingForm input[type="number"]').forEach(function (input) {
                input.value = 1;
            });

            calculateModalTotal();
        }

        openBookingModalButton.addEventListener('click', openBookingModal);
        closeBookingModalButton.addEventListener('click', closeBookingModal);

        window.addEventListener('click', function (event) {
            if (event.target === bookingModal) {
                closeBookingModal();
            }
        });

        submitBookingButton.addEventListener('click', function () {
            var selected = document.querySelectorAll('#bookingForm input[name="service_id[]"]:checked');

            if (selected.length === 0) {
                showToast('Please select at least one service first.', false);
                return;
            }

            var formData = new FormData();
            selected.forEach(function (checkbox) {
                var serviceId = checkbox.value;
                var quantityInput = document.querySelector('#bookingForm input[name="qty[' + serviceId + ']"]');
                formData.append('service_id[]', serviceId);
                formData.append('qty[' + serviceId + ']', quantityInput ? quantityInput.value : '1');
            });
            formData.append('ajax_booking', '1');

            submitBookingButton.disabled = true;
            submitBookingButton.textContent = 'Processing...';

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (result) {
                    showToast(result.message, result.success);

                    if (result.success) {
                        resetBookingForm();
                        setTimeout(function () {
                            closeBookingModal();
                            window.location.reload();
                        }, 1200);
                    }
                })
                .catch(function () {
                    showToast('Something went wrong while saving your booking.', false);
                })
                .finally(function () {
                    submitBookingButton.disabled = false;
                    submitBookingButton.textContent = 'Confirm Booking';
                });
        });

        document.addEventListener('DOMContentLoaded', calculateModalTotal);
    </script>
</body>
</html>

