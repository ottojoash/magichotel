<?php
// admin_dashboard.php - Complete Admin Dashboard with Database Integration
session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'];
$admin_role = $_SESSION['admin_role'];
$admin_id = $_SESSION['admin_id'];

// Handle Cancel Booking
if (isset($_GET['cancel_booking'])) {
    $booking_id = intval($_GET['cancel_booking']);
    $update_sql = "UPDATE bookings SET status = 'cancelled' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $booking_id);
    if ($stmt->execute()) {
        $success_message = "Booking #$booking_id has been cancelled.";
    } else {
        $error_message = "Failed to cancel booking.";
    }
    $stmt->close();
    header("Location: admin_dashboard.php?msg=" . urlencode($success_message ?? $error_message ?? ""));
    exit();
}

// Handle Confirm Booking
if (isset($_GET['confirm_booking'])) {
    $booking_id = intval($_GET['confirm_booking']);
    $update_sql = "UPDATE bookings SET status = 'confirmed' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php?msg=Booking confirmed");
    exit();
}

// Handle Delete User
if (isset($_GET['delete_user'])) {
    $user_id = intval($_GET['delete_user']);
    $delete_sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php?msg=User deleted successfully");
    exit();
}

// Handle Approve Feedback
if (isset($_GET['approve_feedback'])) {
    $feedback_id = intval($_GET['approve_feedback']);
    $update_sql = "UPDATE feedback SET is_approved = 1 WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $feedback_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php?msg=Feedback approved");
    exit();
}

// Handle Delete Feedback
if (isset($_GET['delete_feedback'])) {
    $feedback_id = intval($_GET['delete_feedback']);
    $delete_sql = "DELETE FROM feedback WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $feedback_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php?msg=Feedback deleted");
    exit();
}

// Get Statistics from Database
$stats = [];

// Total clients
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Total bookings
$result = $conn->query("SELECT COUNT(*) as count FROM bookings");
$stats['total_bookings'] = $result->fetch_assoc()['count'];

// Pending bookings
$result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
$stats['pending_bookings'] = $result->fetch_assoc()['count'];

// Confirmed bookings
$result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'confirmed'");
$stats['confirmed_bookings'] = $result->fetch_assoc()['count'];

// Cancelled bookings
$result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'cancelled'");
$stats['cancelled_bookings'] = $result->fetch_assoc()['count'];

// Total revenue
$result = $conn->query("SELECT SUM(total_price) as total FROM bookings");
$stats['total_revenue'] = $result->fetch_assoc()['total'] ?? 0;

// Pending feedback (not approved)
$result = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE is_approved = 0 OR is_approved IS NULL");
$stats['pending_feedback'] = $result->fetch_assoc()['count'];

// Average rating (only approved)
$result = $conn->query("SELECT AVG(rating) as avg FROM feedback WHERE is_approved = 1");
$stats['avg_rating'] = round($result->fetch_assoc()['avg'] ?? 0, 1);

// Get all users with their booking counts
$users_sql = "SELECT u.*, 
              COUNT(b.id) as booking_count, 
              COALESCE(SUM(b.total_price), 0) as total_spent 
              FROM users u 
              LEFT JOIN bookings b ON u.id = b.user_id 
              GROUP BY u.id 
              ORDER BY u.created_at DESC";
$users_result = $conn->query($users_sql);
$users = [];
if ($users_result) {
    $users = $users_result->fetch_all(MYSQLI_ASSOC);
}

// Get all bookings with user details - SHOW ALL BOOKINGS
$bookings_sql = "SELECT b.*, u.name, u.email, u.phone 
                 FROM bookings b 
                 JOIN users u ON b.user_id = u.id 
                 ORDER BY b.booking_date DESC";
$bookings_result = $conn->query($bookings_sql);
$bookings = [];
if ($bookings_result) {
    $bookings = $bookings_result->fetch_all(MYSQLI_ASSOC);
}

// Get ALL feedback - including unapproved ones
$feedback_sql = "SELECT f.*, u.name, u.email 
                 FROM feedback f 
                 JOIN users u ON f.user_id = u.id 
                 ORDER BY f.created_at DESC";
$feedback_result = $conn->query($feedback_sql);
$feedbacks = [];
if ($feedback_result) {
    $feedbacks = $feedback_result->fetch_all(MYSQLI_ASSOC);
}

// Get message from URL
$message = isset($_GET['msg']) ? urldecode($_GET['msg']) : '';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit();
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
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            border-bottom: 2px solid #ff6600;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ff6600;
            text-decoration: none;
        }

        .admin-info {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .admin-badge {
            background: #ff6600;
            color: #000;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .logout-btn {
            background: #ff6600;
            color: #000;
            padding: 0.5rem 1.2rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
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

        /* Alert Message */
        .alert-success {
            background: rgba(40, 167, 69, 0.15);
            border-left: 4px solid #28a745;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            color: #5cb85c;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.15);
            border-left: 4px solid #dc3545;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            color: #ff8888;
        }

        /* Welcome Card */
        .welcome-card {
            background: linear-gradient(135deg, #1a1a1a, #0f0f0f);
            border-radius: 28px;
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 5px solid #ff6600;
        }

        .welcome-card h1 {
            color: #ff6600;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            transform: translateY(-3px);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #ff6600;
        }

        .stat-label {
            color: #aaa;
            margin-top: 0.5rem;
            font-size: 0.85rem;
        }

        /* Section Tabs */
        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid #2a2a2a;
            flex-wrap: wrap;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            cursor: pointer;
            color: #aaa;
            transition: all 0.3s;
            border-radius: 30px;
        }

        .tab-btn.active {
            background: #ff6600;
            color: #000;
        }

        .tab-btn:hover {
            color: #ff6600;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Tables */
        .data-table {
            width: 100%;
            background: #111;
            border-radius: 20px;
            overflow-x: auto;
            border: 1px solid #2a2a2a;
        }

        .data-table table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #2a2a2a;
        }

        .data-table th {
            background: #1a1a1a;
            color: #ff6600;
            font-weight: 600;
        }

        .data-table tr:hover {
            background: #1a1a1a;
        }

        /* Status Badges */
        .status-pending {
            background: rgba(255, 102, 0, 0.2);
            color: #ffaa66;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            display: inline-block;
        }

        .status-confirmed {
            background: rgba(40, 167, 69, 0.2);
            color: #5cb85c;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            display: inline-block;
        }

        .status-cancelled {
            background: rgba(220, 53, 69, 0.2);
            color: #ff8888;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            display: inline-block;
        }

        .status-completed {
            background: rgba(23, 162, 184, 0.2);
            color: #5bc0de;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            display: inline-block;
        }

        /* Action Buttons */
        .action-btn {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            text-decoration: none;
            font-size: 0.7rem;
            margin: 0 0.2rem;
            display: inline-block;
            transition: all 0.2s;
        }

        .btn-confirm {
            background: #28a745;
            color: white;
        }

        .btn-cancel {
            background: #dc3545;
            color: white;
        }

        .btn-delete {
            background: #6c757d;
            color: white;
        }

        .btn-approve {
            background: #17a2b8;
            color: white;
        }

        .btn-view {
            background: #ff6600;
            color: black;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            opacity: 0.9;
        }

        /* Rating Stars */
        .rating-stars {
            color: #ffdd00;
        }

        /* Full Comment Preview */
        .comment-preview {
            max-width: 300px;
            white-space: normal;
            word-wrap: break-word;
        }

        /* Footer */
        .footer {
            background: #000;
            text-align: center;
            padding: 2rem;
            margin-top: 3rem;
            border-top: 1px solid #2a2a2a;
            color: #888;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                text-align: center;
            }
            .container {
                padding: 1rem;
            }
            .tab-btn {
                padding: 0.5rem 1rem;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="admin_dashboard.php" class="logo">🏨 MAGIC HOTEL ADMIN</a>
        <div class="admin-info">
            <span class="admin-badge"><?php echo ucfirst($admin_role); ?></span>
            <span>👋 <?php echo htmlspecialchars($admin_name); ?></span>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </nav>

    <div class="container">
        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert-success">✅ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="welcome-card">
            <h1>Welcome back, <?php echo htmlspecialchars($admin_name); ?>!</h1>
            <p>Here's what's happening with your hotel today.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total Clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_bookings']; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_bookings']; ?></div>
                <div class="stat-label">Pending Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['confirmed_bookings']; ?></div>
                <div class="stat-label">Confirmed Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['cancelled_bookings']; ?></div>
                <div class="stat-label">Cancelled Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">UGX <?php echo number_format($stats['total_revenue']); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_feedback']; ?></div>
                <div class="stat-label">Pending Feedback</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['avg_rating']; ?> ⭐</div>
                <div class="stat-label">Average Rating</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('bookings')">📅 Bookings</button>
            <button class="tab-btn" onclick="showTab('clients')">👥 Clients</button>
            <button class="tab-btn" onclick="showTab('feedback')">⭐ Feedback (<?php echo count($feedbacks); ?>)</button>
        </div>

        <!-- Bookings Tab -->
        <div id="bookings" class="tab-content active">
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client Name</th>
                            <th>Service</th>
                            <th>Quantity</th>
                            <th>Total Price</th>
                            <th>Booking Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($bookings) > 0): ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo $booking['id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                    <td><?php echo $booking['quantity']; ?></td>
                                    <td>UGX <?php echo number_format($booking['total_price']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></td>
                                    <td>
                                        <span class="status-<?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($booking['status'] == 'pending'): ?>
                                            <a href="?confirm_booking=<?php echo $booking['id']; ?>" class="action-btn btn-confirm" onclick="return confirm('Confirm this booking?')">Confirm</a>
                                        <?php endif; ?>
                                        <?php if ($booking['status'] != 'cancelled' && $booking['status'] != 'completed'): ?>
                                            <a href="?cancel_booking=<?php echo $booking['id']; ?>" class="action-btn btn-cancel" onclick="return confirm('Cancel this booking?')">Cancel</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 2rem;">No bookings found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Clients Tab -->
        <div id="clients" class="tab-content">
            <div class="data-table">
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>#<?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td><?php echo $user['booking_count']; ?></td>
                                    <td>UGX <?php echo number_format($user['total_spent'] ?? 0); ?></td>
                                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="?delete_user=<?php echo $user['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this user? All their bookings will also be deleted.')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 2rem;">No clients found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Feedback Tab - Shows ALL feedback including unapproved -->
        <div id="feedback" class="tab-content">
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($feedbacks) > 0): ?>
                            <?php foreach ($feedbacks as $feedback): ?>
                                <tr>
                                    <td>#<?php echo $feedback['id']; ?></td>
                                    <td><?php echo htmlspecialchars($feedback['name']); ?></td>
                                    <td><?php echo htmlspecialchars($feedback['service_name']); ?></td>
                                    <td class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php echo $i <= $feedback['rating'] ? '★' : '☆'; ?>
                                        <?php endfor; ?>
                                    </td>
                                    <td class="comment-preview"><?php echo nl2br(htmlspecialchars($feedback['comment'])); ?></td>
                                    <td><?php echo date('d M Y', strtotime($feedback['created_at'])); ?></td>
                                    <td>
                                        <?php if ($feedback['is_approved'] == 1): ?>
                                            <span style="color: #5cb85c;">✓ Approved</span>
                                        <?php else: ?>
                                            <span style="color: #ffaa66;">⏳ Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($feedback['is_approved'] != 1): ?>
                                            <a href="?approve_feedback=<?php echo $feedback['id']; ?>" class="action-btn btn-approve" onclick="return confirm('Approve this feedback?')">Approve</a>
                                        <?php endif; ?>
                                        <a href="?delete_feedback=<?php echo $feedback['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this feedback?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 2rem;">No feedback found. When customers submit feedback, it will appear here.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="footer">
        © 2025 MAGIC HOTEL LTD — Admin Dashboard | Logged in as <?php echo htmlspecialchars($admin_role); ?>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            var bookingsTab = document.getElementById('bookings');
            var clientsTab = document.getElementById('clients');
            var feedbackTab = document.getElementById('feedback');
            
            if (bookingsTab) bookingsTab.classList.remove('active');
            if (clientsTab) clientsTab.classList.remove('active');
            if (feedbackTab) feedbackTab.classList.remove('active');
            
            // Remove active class from buttons
            var buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(function(btn) {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            var selectedTab = document.getElementById(tabName);
            if (selectedTab) selectedTab.classList.add('active');
            
            // Add active class to clicked button
            if (event && event.target) {
                event.target.classList.add('active');
            }
        }
    </script>

</body>
</html>