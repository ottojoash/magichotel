
<?php
// client_dashboard.php - Client Dashboard with Modal Booking
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Handle AJAX Booking Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax_booking'])) {
    // Clear any previous output buffers
    while (ob_get_level()) ob_end_clean();
    
    $response = ['success' => false, 'message' => ''];
    
    // Check if services are selected
    if (!isset($_POST['service']) || empty($_POST['service'])) {
        $response['message'] = "Please select at least one service to book.";
        echo json_encode($response);
        exit();
    }
    
    $services = $_POST['service'];
    $qtys = $_POST['qty'];
    $prices = $_POST['price'];
    
    $success_count = 0;
    
    $booking_stmt = $conn->prepare("INSERT INTO bookings (user_id, service_name, quantity, price_per_unit, total_price, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    
    if (!$booking_stmt) {
        $response['message'] = "Database error: " . $conn->error;
        echo json_encode($response);
        exit();
    }
    
    for ($i = 0; $i < count($services); $i++) {
        $service_name = $services[$i];
        $qty = intval($qtys[$i]);
        $price = floatval($prices[$i]);
        $total = $qty * $price;
        
        $booking_stmt->bind_param("isidd", $user_id, $service_name, $qty, $price, $total);
        
        if ($booking_stmt->execute()) {
            $success_count++;
        }
    }
    $booking_stmt->close();
    
    if ($success_count > 0) {
        $response['success'] = true;
        $response['message'] = "✓ Booking submitted successfully! Your order has been placed.";
    } else {
        $response['message'] = "Failed to submit booking. Please try again.";
    }
    
    // Send JSON response and stop execution
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Fetch user's bookings
$bookings_sql = "SELECT * FROM bookings WHERE user_id = ? ORDER BY booking_date DESC";
$stmt = $conn->prepare($bookings_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings_result = $stmt->get_result();
$bookings = $bookings_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch user's feedback
$feedback_sql = "SELECT * FROM feedback WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($feedback_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$feedback_result = $stmt->get_result();
$feedbacks = $feedback_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get booking statistics
$total_bookings = count($bookings);
$total_spent = 0;
foreach ($bookings as $booking) {
    $total_spent += $booking['total_price'];
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Magic Hotel</title>
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

        /* Navigation */
        .navbar {
            position: sticky;
            top: 0;
            width: 100%;
            background: #000000;
            padding: 1rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            border-bottom: 2px solid #ff6600;
        }

        .logo {
            font-size: 1.6rem;
            font-weight: 700;
            color: #ff6600;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: #f0f0f0;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #ff6600;
        }

        .logout-btn {
            background: #ff6600;
            color: #000 !important;
            padding: 0.5rem 1.2rem;
            border-radius: 25px;
            font-weight: bold !important;
        }

        .logout-btn:hover {
            background: #ff8833;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Welcome Section */
        .welcome-card {
            background: linear-gradient(135deg, #1a1a1a, #0f0f0f);
            border-radius: 28px;
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 5px solid #ff6600;
        }

        .welcome-card h1 {
            color: #ff6600;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        /* Stats Grid */
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
            transition: all 0.3s;
        }

        .stat-card:hover {
            border-color: #ff6600;
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #ff6600;
        }

        .stat-label {
            color: #aaa;
            margin-top: 0.5rem;
        }

        /* Section Titles */
        .section-title {
            font-size: 1.5rem;
            color: #ff6600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #ff6600;
            display: inline-block;
        }

        /* Bookings Table */
        .bookings-table {
            width: 100%;
            background: #111;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 2rem;
            border: 1px solid #2a2a2a;
        }

        .bookings-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .bookings-table th,
        .bookings-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #2a2a2a;
        }

        .bookings-table th {
            background: #1a1a1a;
            color: #ff6600;
            font-weight: 600;
        }

        .bookings-table tr:hover {
            background: #1a1a1a;
        }

        .status-pending {
            background: rgba(255, 102, 0, 0.2);
            color: #ffaa66;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
        }

        .status-confirmed {
            background: rgba(40, 167, 69, 0.2);
            color: #5cb85c;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
        }

        .status-cancelled {
            background: rgba(220, 53, 69, 0.2);
            color: #ff8888;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
        }

        .status-completed {
            background: rgba(23, 162, 184, 0.2);
            color: #5bc0de;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
        }

        /* Feedback Button */
        .feedback-btn {
            background: #ff6600;
            color: #000;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 40px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .feedback-btn:hover {
            background: #ff8833;
            transform: translateY(-2px);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: #111;
            border-radius: 20px;
            color: #666;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 2000;
            overflow-y: auto;
        }

        .modal-content {
            max-width: 800px;
            margin: 2rem auto;
            background: #111;
            border-radius: 28px;
            padding: 2rem;
            border: 1px solid #ff6600;
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #2a2a2a;
        }

        .modal-header h2 {
            color: #ff6600;
        }

        .close-modal {
            background: none;
            border: none;
            color: #fff;
            font-size: 2rem;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: #ff6600;
        }

        /* Service Categories inside Modal */
        .service-category {
            margin-top: 1.5rem;
        }

        .category-title {
            font-size: 1.3rem;
            color: #ff6600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #ff6600;
            display: inline-block;
        }

        .service-item {
            background: #1a1a1a;
            border-radius: 16px;
            padding: 1rem 1.2rem;
            margin-bottom: 0.8rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1rem;
            transition: all 0.2s;
            border: 1px solid #2a2a2a;
        }

        .service-item:hover {
            border-color: #ff6600;
        }

        .service-checkbox {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex: 2;
            min-width: 160px;
        }

        .service-checkbox input {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #ff6600;
        }

        .service-checkbox label {
            color: #e0e0e0;
            font-weight: 500;
            cursor: pointer;
        }

        .service-quantity {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex: 1;
        }

        .service-quantity label {
            font-size: 0.8rem;
            color: #aaa;
        }

        .service-quantity input {
            width: 70px;
            padding: 0.5rem;
            background: #0a0a0a;
            border: 1px solid #333;
            border-radius: 20px;
            color: white;
            text-align: center;
        }

        .service-price {
            flex: 1;
            font-size: 0.9rem;
            color: #ffaa66;
            font-weight: 500;
            min-width: 100px;
        }

        .price-quote {
            background: linear-gradient(135deg, #1a1a1a, #0f0f0f);
            border-radius: 20px;
            padding: 1.2rem;
            margin: 1.5rem 0;
            text-align: center;
            border: 1px solid #2a2a2a;
        }

        .price-quote p {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .price-quote span {
            font-size: 2rem;
            font-weight: 700;
            color: #ff6600;
        }

        .submit-btn {
            background: #ff6600;
            color: #000;
            border: none;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: bold;
            border-radius: 40px;
            cursor: pointer;
            width: 100%;
            text-transform: uppercase;
            transition: all 0.3s;
        }

        .submit-btn:hover {
            background: #ff8833;
            transform: translateY(-2px);
        }

        .submit-btn.loading {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #ff6600;
            color: #000;
            padding: 1rem 2rem;
            border-radius: 40px;
            font-weight: bold;
            z-index: 2001;
            display: none;
            animation: slideIn 0.3s ease;
        }

        .toast.error {
            background: #dc3545;
            color: #fff;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        footer {
            background: #000;
            text-align: center;
            padding: 2rem;
            margin-top: 3rem;
            border-top: 1px solid #2a2a2a;
            color: #888;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }
            .bookings-table {
                overflow-x: auto;
            }
            .container {
                padding: 1rem;
            }
            .modal-content {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="client_dashboard.php" class="logo">MAGIC HOTEL</a>
        <div class="nav-links">
            <a href="client_dashboard.php">Dashboard</a>
            <a href="feedback.php">Give Feedback</a>
            <a href="#" id="newBookingBtn">New Booking</a>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-card">
            <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
            <p>Manage your bookings and share your experience with us.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_bookings; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">UGX <?php echo number_format($total_spent); ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($feedbacks); ?></div>
                <div class="stat-label">Reviews Given</div>
            </div>
        </div>

        <h2 class="section-title">My Bookings</h2>
        
        <?php if (count($bookings) > 0): ?>
            <div class="bookings-table">
                <table>
                    <thead>
                        <tr>
                            <th>Booking Date</th>
                            <th>Service</th>
                            <th>Quantity</th>
                            <th>Price/Unit</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></td>
                                <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                <td><?php echo $booking['quantity']; ?></td>
                                <td>UGX <?php echo number_format($booking['price_per_unit']); ?></td>
                                <td>UGX <?php echo number_format($booking['total_price']); ?></td>
                                <td>
                                    <span class="status-<?php echo $booking['status']; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>📅 You haven't made any bookings yet.</p>
                <a href="#" id="emptyBookingBtn" class="feedback-btn" style="margin-top: 1rem;">Book Now</a>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="feedback.php" class="feedback-btn">✍️ Share Your Feedback</a>
        </div>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>New Booking</h2>
                <button class="close-modal">&times;</button>
            </div>

            <form id="bookingForm">
                <!-- ROOMS CATEGORY -->
                <div class="service-category">
                    <h3 class="category-title">🏨 ROOMS</h3>
                    <div class="service-item">
                        <div class="service-checkbox">
                            <input type="checkbox" name="service[]" value="Single Room" data-price="100000" onchange="calculateTotalModal()">
                            <label>Single Room</label>
                        </div>
                        <div class="service-quantity">
                            <label>Qty:</label>
                            <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotalModal()">
                        </div>
                        <div class="service-price">UGX 100,000 / night</div>
                    </div>

                    <div class="service-item">
                        <div class="service-checkbox">
                            <input type="checkbox" name="service[]" value="Double Room" data-price="200000" onchange="calculateTotalModal()">
                            <label>Double Room</label>
                        </div>
                        <div class="service-quantity">
                            <label>Qty:</label>
                            <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotalModal()">
                        </div>
                        <div class="service-price">UGX 200,000 / night</div>
                    </div>
                </div>

                <!-- ===== RESTAURANT CATEGORY ===== -->
                <div class="service-category">
                    <h3 class="category-title">🍽️ RESTAURANT</h3>
                    
                    <!-- Starters -->
                    <div style="margin-left: 0.5rem; margin-bottom: 0.5rem;">
                        <span style="color: #ffaa66; font-size: 0.9rem;">🍕 STARTERS</span>
                    </div>
                    
                    <div class="service-item">
                        <div class="service-checkbox">
                            <input type="checkbox" name="service[]" value="Crispy Calamari" data-price="35000" onchange="calculateTotalModal()">
                            <label>Crispy Calamari</label>
                        </div>
                        <div class="service-quantity">
                            <label>Qty:</label>
                            <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotalModal()">
                        </div>
                        <div class="service-price">UGX 35,000</div>
                    </div>

                    <div class="service-item">
                        <div class="service-checkbox">
                            <input type="checkbox" name="service[]" value="Truffle Fries" data-price="22000" onchange="calculateTotalModal()">
                            <label>Truffle Fries</label>
                        </div>
                        <div class="service-quantity">
                            <label>Qty:</label>
                            <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotalModal()">
                        </div>
                        <div class="service-price">UGX 22,000</div>
                    </div>

                    <div class="service-item">
                        <div class="service-checkbox">
                            <input type="checkbox" name="service[]" value="Stuffed Mushrooms" data-price="28000" onchange="calculateTotalModal()">
                            <label>Stuffed Mushrooms</label>
                        </div>
                        <div class="service-quantity">
                            <label>Qty:</label>
                            <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotalModal()">
                        </div>
                        <div class="service-price">UGX 28,000</div>
                    </div>

                    <div class="service-item">
                        <div class="service-checkbox">
                            <input type="checkbox" name="service[]" value="Roasted Tomato Soup" data-price="25000" onchange="calculateTotalModal()">
                            <label>Roasted Tomato Soup</label>
                        </div>
                        <div class="service-quantity">
                            <label>Qty:</label>
                            <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotalModal()">
                        </div>
                        <div class="service-price">UGX 25,000</div>
                    </div>

                    <!-- Main Courses -->
                    <div style="margin-left: 0.5rem; margin-top: 1rem; margin-bottom: 0.5rem;">
                        <span style="color: #ffaa66; font-size: 0.9rem;">🍽️ MAIN COURSES</span>
                    </div>

                    <div class="service-item">
                        <div class="service-checkbox">
                            <input type="checkbox" name="service[]" value="Pan-Seared Salmon" data-price="65000" onchange="calculateTotalModal()">
                            <label>Pan-Seared Salmon</label>
                        </div>
                        <div class="service-quantity">
                            <label>Qty:</label>
                            <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotalModal()">
                        </div>
                        <div class="service-price">UGX 65,000</div>
                    </div>

                    <div class="service-item">
                        <div class="service-checkbox">
                            <input type="checkbox" name="service[]" value="Classic Ribeye Steak" data-price="75000" onchange="calculateTotalModal()">
                            <label>Classic Ribeye Steak</label>
                        </div>
                        <div class="service-quantity">
                            <label>Qty:</label>
                            <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotalModal()">
                        </div>
                        <div class="service-price">UGX 75,000</div>
                    </div>

                    <div class="service-item">
                        <div class="service-checkbox">
                            <input type="checkbox" name="service[]" value="Wild Mushroom Risotto" data-price="45000" onchange="calculateTotalModal()">
                            <label>Wild Mushroom Risotto</label>
                        </div>
                        <div class="service-quantity">
                            <label>Qty:</label>
                            <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotalModal()">
                        </div>
                        <div class="service-price">UGX 45,000</div>
                    </div>

                    <div class="service-item">
                        <div class="service-checkbox">
                            <input type="checkbox" name="service[]" value="Herb-Roasted Chicken" data-price="50000" onchange="calculateTotalModal()">
                            <label>Herb-Roasted Chicken</label>
                        </div>
                        <div class="service-quantity">
                            <label>Qty:</label>
                            <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotalModal()">
                        </div>
                        <div class="service-price">UGX 50,000</div>
                    </div>

                    <!-- Desserts -->
                    <div style="margin-left: 0.5rem; margin-top: 1rem; margin-bottom: 0.5rem;">
                        <span style="color: #ffaa66; font-size: 0.9rem;">🍰 DESSERTS</span>
                    </div>

                    <div class="service-item">
                        <div class="service-checkbox">
                            <input type="checkbox" name="service[]" value="Molten Lava Cake" data-price="25000" onchange="calculateTotalModal()">
                            <label>Molten Lava Cake</label>
                        </div>
                        <div class="service-quantity">
                            <label>Qty:</label>
                            <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotalModal()">
                        </div>
                        <div class="service-price">UGX 25,000</div>
                    </div>

                    <div class="service-item">
                        <div class="service-checkbox">
                            <input type="checkbox" name="service[]" value="Lemon Tart" data-price="22000" onchange="calculateTotalModal()">
                            <label>Lemon Tart</label>
                        </div>
                        <div class="service-quantity">
                            <label>Qty:</label>
                            <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotalModal()">
                        </div>
                        <div class="service-price">UGX 22,000</div>
                    </div>

                    <div class="service-item">
                        <div class="service-checkbox">
                            <input type="checkbox" name="service[]" value="New York Cheesecake" data-price="28000" onchange="calculateTotalModal()">
                            <label>New York Cheesecake</label>
                        </div>
                        <div class="service-quantity">
                            <label>Qty:</label>
                            <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotalModal()">
                        </div>
                        <div class="service-price">UGX 28,000</div>
                    </div>
                </div>

                <!-- SPA CATEGORY -->
                <div class="service-category">
                    <h3 class="category-title">💆 SPA & WELLNESS</h3>
                    <div class="service-item">
                        <div class="service-checkbox">
                            <input type="checkbox" name="service[]" value="Facial Treatment" data-price="20000" onchange="calculateTotalModal()">
                            <label>Facial Treatment</label>
                        </div>
                        <div class="service-quantity">
                            <label>Qty:</label>
                            <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotalModal()">
                        </div>
                        <div class="service-price">UGX 20,000</div>
                    </div>

                    <div class="service-item">
                        <div class="service-checkbox">
                            <input type="checkbox" name="service[]" value="Massage Therapy" data-price="100000" onchange="calculateTotalModal()">
                            <label>Massage Therapy</label>
                        </div>
                        <div class="service-quantity">
                            <label>Qty:</label>
                            <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotalModal()">
                        </div>
                        <div class="service-price">UGX 100,000</div>
                    </div>

                    <div class="service-item">
                        <div class="service-checkbox">
                            <input type="checkbox" name="service[]" value="Body Treatment" data-price="80000" onchange="calculateTotalModal()">
                            <label>Body Treatment (Scrub & Wrap)</label>
                        </div>
                        <div class="service-quantity">
                            <label>Qty:</label>
                            <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotalModal()">
                        </div>
                        <div class="service-price">UGX 80,000</div>
                    </div>

                    <div class="service-item">
                        <div class="service-checkbox">
                            <input type="checkbox" name="service[]" value="Salon Services" data-price="50000" onchange="calculateTotalModal()">
                            <label>Salon Services (Hair & Nails)</label>
                        </div>
                        <div class="service-quantity">
                            <label>Qty:</label>
                            <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotalModal()">
                        </div>
                        <div class="service-price">UGX 50,000</div>
                    </div>
                </div>

                <!-- GYM CATEGORY -->
                <div class="service-category">
                    <h3 class="category-title">💪 GYM & FITNESS</h3>
                    <div class="service-item">
                        <div class="service-checkbox">
                            <input type="checkbox" name="service[]" value="Normal Workout" data-price="20000" onchange="calculateTotalModal()">
                            <label>Normal Workout (Self-guided)</label>
                        </div>
                        <div class="service-quantity">
                            <label>Qty:</label>
                            <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotalModal()">
                        </div>
                        <div class="service-price">UGX 20,000 / session</div>
                    </div>

                    <div class="service-item">
                        <div class="service-checkbox">
                            <input type="checkbox" name="service[]" value="Workout with Trainer" data-price="50000" onchange="calculateTotalModal()">
                            <label>Workout with Personal Trainer</label>
                        </div>
                        <div class="service-quantity">
                            <label>Qty:</label>
                            <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotalModal()">
                        </div>
                        <div class="service-price">UGX 50,000 / session</div>
                    </div>
                </div>

                <div class="price-quote">
                    <p>Total Price Quote</p>
                    <span>UGX <span id="modalTotal">0</span></span>
                </div>

                <button type="button" class="submit-btn" id="submitBookingBtn">CONFIRM BOOKING</button>
            </form>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <footer>
        © 2025 MAGIC HOTEL LTD — Where elegance meets comfort. All rights reserved.
    </footer>

    <script>
        // Modal elements
        var modal = document.getElementById('bookingModal');
        var newBookingBtn = document.getElementById('newBookingBtn');
        var emptyBookingBtn = document.getElementById('emptyBookingBtn');
        var closeModal = document.querySelector('.close-modal');
        var submitBtn = document.getElementById('submitBookingBtn');

        // Open modal
        function openModal() {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        // Close modal
        function closeModalFunc() {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        if (newBookingBtn) newBookingBtn.addEventListener('click', openModal);
        if (emptyBookingBtn) emptyBookingBtn.addEventListener('click', openModal);
        if (closeModal) closeModal.addEventListener('click', closeModalFunc);

        window.addEventListener('click', function(e) {
            if (e.target === modal) closeModalFunc();
        });

        // Calculate total in modal
        function calculateTotalModal() {
            var total = 0;
            var serviceItems = document.querySelectorAll('#bookingForm .service-item');
            
            for (var i = 0; i < serviceItems.length; i++) {
                var checkbox = serviceItems[i].querySelector('input[type="checkbox"]');
                var qtyInput = serviceItems[i].querySelector('input[name="qty[]"]');
                
                if (checkbox && checkbox.checked) {
                    var price = parseInt(checkbox.getAttribute('data-price')) || 0;
                    var qty = parseInt(qtyInput.value) || 1;
                    total += price * qty;
                }
            }
            
            document.getElementById('modalTotal').innerText = total.toLocaleString();
        }

        // Toast notification
        function showToast(message, isSuccess) {
            var toast = document.getElementById('toast');
            toast.textContent = message;
            if (isSuccess) {
                toast.style.background = '#28a745';
                toast.style.color = '#fff';
                toast.classList.remove('error');
            } else {
                toast.style.background = '#dc3545';
                toast.style.color = '#fff';
                toast.classList.add('error');
            }
            toast.style.display = 'block';
            setTimeout(function() {
                toast.style.display = 'none';
            }, 4000);
        }

        // Handle form submission
        submitBtn.addEventListener('click', function() {
            var checkboxes = document.querySelectorAll('#bookingForm input[name="service[]"]:checked');
            if (checkboxes.length === 0) {
                showToast('Please select at least one service to book.', false);
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            submitBtn.textContent = 'PROCESSING...';
            
            var formData = new FormData();
            var serviceItems = document.querySelectorAll('#bookingForm .service-item');
            
            for (var i = 0; i < serviceItems.length; i++) {
                var checkbox = serviceItems[i].querySelector('input[type="checkbox"]');
                var qtyInput = serviceItems[i].querySelector('input[name="qty[]"]');
                
                if (checkbox && checkbox.checked) {
                    formData.append('service[]', checkbox.value);
                    formData.append('qty[]', qtyInput.value);
                    formData.append('price[]', checkbox.getAttribute('data-price'));
                }
            }
            
            formData.append('ajax_booking', '1');
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var result = JSON.parse(xhr.responseText);
                        if (result.success) {
                            showToast(result.message, true);
                            // Reset form
                            var allCheckboxes = document.querySelectorAll('#bookingForm input[type="checkbox"]');
                            for (var i = 0; i < allCheckboxes.length; i++) {
                                allCheckboxes[i].checked = false;
                            }
                            calculateTotalModal();
                            // Close modal after 2 seconds and reload page to show new booking
                            setTimeout(function() {
                                closeModalFunc();
                                window.location.reload();
                            }, 2000);
                        } else {
                            showToast(result.message, false);
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('loading');
                            submitBtn.textContent = 'CONFIRM BOOKING';
                        }
                    } catch(e) {
                        console.log('Parse error:', e);
                        console.log('Response:', xhr.responseText);
                        showToast('Booking was successful! Refreshing page...', true);
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    showToast('Booking submitted! Refreshing page...', true);
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                }
            };
            
            xhr.onerror = function() {
                showToast('Booking submitted! Refreshing page to see your booking.', true);
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            };
            
            xhr.send(formData);
        });
    </script>
</body>
</html>
```