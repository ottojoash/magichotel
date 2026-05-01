
<?php
// booking.php - Magic Hotel Booking System with Database
session_start();
require_once 'config.php';

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 4) {
        $error = "Password must be at least 4 characters.";
    } elseif (!isset($_POST['service']) || empty($_POST['service'])) {
        $error = "Please select at least one service to book.";
    } else {
        // Check if user already exists
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // User exists, get user_id
            $user = $check_result->fetch_assoc();
            $user_id = $user['id'];
        } else {
            // Create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_user = "INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)";
            $user_stmt = $conn->prepare($insert_user);
            $user_stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);
            
            if ($user_stmt->execute()) {
                $user_id = $conn->insert_id;
            } else {
                $error = "Failed to create account. Please try again.";
            }
            $user_stmt->close();
        }
        $check_stmt->close();
        
        // Insert bookings if no error
        if (empty($error) && isset($user_id)) {
            $services = $_POST['service'];
            $qtys = $_POST['qty'];
            $prices = $_POST['price'];
            
            $booking_stmt = $conn->prepare("INSERT INTO bookings (user_id, service_name, quantity, price_per_unit, total_price, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            
            for ($i = 0; $i < count($services); $i++) {
                $service_name = $services[$i];
                $qty = intval($qtys[$i]);
                $price = floatval($prices[$i]);
                $total = $qty * $price;
                
                $booking_stmt->bind_param("isidd", $user_id, $service_name, $qty, $price, $total);
                $booking_stmt->execute();
            }
            $booking_stmt->close();
            
            $message = "Booking submitted successfully! We will contact you shortly.";
        }
    }
}
?>
<!-- Rest of your HTML remains the SAME as your existing booking.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magic Hotel - Booking</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
            background-color: #0a0a0a;
            color: #e0e0e0;
        }

        /* ===== STATIC NAVIGATION BAR ===== */
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

        /* ===== HERO SECTION ===== */
        .hero-booking {
            width: 100%;
            height: 45vh;
            min-height: 320px;
            background: linear-gradient(135deg, rgba(0,0,0,0.85), rgba(255,102,0,0.25)), url('https://images.pexels.com/photos/261102/pexels-photo-261102.jpeg?auto=compress&cs=tinysrgb&w=1600');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
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
            font-size: 3.5rem;
            font-weight: 700;
            letter-spacing: 4px;
            color: white;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .hero-overlay h1 span {
            color: #ff6600;
        }

        .hero-overlay p {
            font-size: 1rem;
            color: #ddd;
        }

        /* ===== MESSAGE ALERTS ===== */
        .message-container {
            max-width: 800px;
            margin: 2rem auto 0;
            padding: 0 1rem;
        }
        .alert-success {
            background: rgba(255, 102, 0, 0.15);
            border-left: 5px solid #ff6600;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            color: #ffaa66;
            text-align: center;
            font-weight: 500;
        }
        .alert-error {
            background: rgba(220, 53, 69, 0.15);
            border-left: 5px solid #dc3545;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            color: #ff8888;
            text-align: center;
        }

        /* ===== MAIN CONTENT ===== */
        main {
            padding: 40px 20px 60px;
        }

        .booking-wrapper {
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .booking-container {
            width: 100%;
            max-width: 800px;
        }

        /* Booking Card */
        .booking-card {
            background: #111111;
            border-radius: 28px;
            padding: 2.5rem;
            box-shadow: 0 25px 40px rgba(0,0,0,0.5);
            border: 1px solid #2a2a2a;
            transition: all 0.3s ease;
        }

        .booking-card:hover {
            border-color: #ff6600;
            box-shadow: 0 25px 40px rgba(255,102,0,0.1);
        }

        /* Form Title */
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

        /* Password Row */
        .password-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .password-row .input-group {
            flex: 1;
            min-width: 200px;
        }

        /* Services Section */
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

        .service-checkbox input[type="checkbox"] {
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

        /* Price Quote */
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

        /* Submit Button */
        .submit-btn {
            background: #ff6600;
            color: #000000;
            border: none;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: bold;
            border-radius: 40px;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .submit-btn:hover {
            background: #ff8833;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255,102,0,0.3);
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
            .booking-card {
                padding: 1.5rem;
            }
            .service-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .service-checkbox, .service-quantity, .service-price {
                width: 100%;
            }
            .form-title h2 {
                font-size: 1.5rem;
            }
            .password-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>

<!-- STATIC NAVIGATION BAR -->
<div class="navbar">
    <div class="logo">MAGIC HOTEL</div>
    <div class="nav-links">
        <a href="index.html">Home</a>
        <a href="services.html">Services</a>
        <a href="contact.php">Contact</a>
        <a href="feedback.php">Feedback</a>
    </div>
</div>

<!-- HERO SECTION -->
<div class="hero-booking">
    <div class="hero-overlay">
        <h1>BOOK <span>YOUR STAY</span></h1>
        <p>Reserve your magical experience today</p>
    </div>
</div>

<!-- MESSAGE DISPLAY -->
<?php if (!empty($message)): ?>
    <div class="message-container">
        <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
    </div>
<?php elseif (!empty($error)): ?>
    <div class="message-container">
        <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
    </div>
<?php endif; ?>

<!-- MAIN CONTENT -->
<main>
    <div class="booking-wrapper">
        <div class="booking-container">
            <div class="booking-card">
                <div class="form-title">
                    <h2>Reservation Form</h2>
                    <div class="divider"></div>
                </div>

                <form method="POST" oninput="calculateTotal()">
                    <!-- Personal Information -->
                    <div class="input-group">
                        <label>Full Name</label>
                        <input type="text" name="name" placeholder="Enter your full name" required>
                    </div>

                    <div class="input-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="your@email.com" required>
                    </div>

                    <div class="input-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" placeholder="+256 XXX XXX XXX" required>
                    </div>

                    <!-- Password Fields -->
                    <div class="password-row">
                        <div class="input-group">
                            <label>Create Password</label>
                            <input type="password" name="password" placeholder="Enter password" required>
                        </div>
                        <div class="input-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" placeholder="Confirm password" required>
                        </div>
                    </div>

                    <!-- ===== ROOMS CATEGORY ===== -->
                    <div class="service-category">
                        <h3 class="category-title"> ROOMS</h3>
                        
                        <div class="service-item">
                            <div class="service-checkbox">
                                <input type="checkbox" name="service[]" value="Single Room" onchange="calculateTotal()">
                                <label>Single Room</label>
                            </div>
                            <div class="service-quantity">
                                <label>Qty:</label>
                                <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotal()">
                            </div>
                            <div class="service-price">UGX 100,000 / night</div>
                            <input type="hidden" name="price[]" value="100000">
                        </div>

                        <div class="service-item">
                            <div class="service-checkbox">
                                <input type="checkbox" name="service[]" value="Double Room" onchange="calculateTotal()">
                                <label>Double Room</label>
                            </div>
                            <div class="service-quantity">
                                <label>Qty:</label>
                                <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotal()">
                            </div>
                            <div class="service-price">UGX 200,000 / night</div>
                            <input type="hidden" name="price[]" value="200000">
                        </div>
                    </div>

                    <!-- ===== RESTAURANT CATEGORY ===== -->
                    <div class="service-category">
                        <h3 class="category-title"> RESTAURANT</h3>
                        
                        <!-- Starters -->
                        <div style="margin-left: 0.5rem; margin-bottom: 0.5rem;">
                            <span style="color: #ffaa66; font-size: 0.9rem;">🍕 STARTERS</span>
                        </div>
                        
                        <div class="service-item">
                            <div class="service-checkbox">
                                <input type="checkbox" name="service[]" value="Crispy Calamari" onchange="calculateTotal()">
                                <label>Crispy Calamari</label>
                            </div>
                            <div class="service-quantity">
                                <label>Qty:</label>
                                <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotal()">
                            </div>
                            <div class="service-price">UGX 35,000</div>
                            <input type="hidden" name="price[]" value="35000">
                        </div>

                        <div class="service-item">
                            <div class="service-checkbox">
                                <input type="checkbox" name="service[]" value="Truffle Fries" onchange="calculateTotal()">
                                <label>Truffle Fries</label>
                            </div>
                            <div class="service-quantity">
                                <label>Qty:</label>
                                <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotal()">
                            </div>
                            <div class="service-price">UGX 22,000</div>
                            <input type="hidden" name="price[]" value="22000">
                        </div>

                        <div class="service-item">
                            <div class="service-checkbox">
                                <input type="checkbox" name="service[]" value="Stuffed Mushrooms" onchange="calculateTotal()">
                                <label>Stuffed Mushrooms</label>
                            </div>
                            <div class="service-quantity">
                                <label>Qty:</label>
                                <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotal()">
                            </div>
                            <div class="service-price">UGX 28,000</div>
                            <input type="hidden" name="price[]" value="28000">
                        </div>

                        <div class="service-item">
                            <div class="service-checkbox">
                                <input type="checkbox" name="service[]" value="Roasted Tomato Soup" onchange="calculateTotal()">
                                <label>Roasted Tomato Soup</label>
                            </div>
                            <div class="service-quantity">
                                <label>Qty:</label>
                                <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotal()">
                            </div>
                            <div class="service-price">UGX 25,000</div>
                            <input type="hidden" name="price[]" value="25000">
                        </div>

                        <!-- Main Courses -->
                        <div style="margin-left: 0.5rem; margin-top: 1rem; margin-bottom: 0.5rem;">
                            <span style="color: #ffaa66; font-size: 0.9rem;"> MAIN COURSES</span>
                        </div>

                        <div class="service-item">
                            <div class="service-checkbox">
                                <input type="checkbox" name="service[]" value="Pan-Seared Salmon" onchange="calculateTotal()">
                                <label>Pan-Seared Salmon</label>
                            </div>
                            <div class="service-quantity">
                                <label>Qty:</label>
                                <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotal()">
                            </div>
                            <div class="service-price">UGX 65,000</div>
                            <input type="hidden" name="price[]" value="65000">
                        </div>

                        <div class="service-item">
                            <div class="service-checkbox">
                                <input type="checkbox" name="service[]" value="Classic Ribeye Steak" onchange="calculateTotal()">
                                <label>Classic Ribeye Steak</label>
                            </div>
                            <div class="service-quantity">
                                <label>Qty:</label>
                                <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotal()">
                            </div>
                            <div class="service-price">UGX 75,000</div>
                            <input type="hidden" name="price[]" value="75000">
                        </div>

                        <div class="service-item">
                            <div class="service-checkbox">
                                <input type="checkbox" name="service[]" value="Wild Mushroom Risotto" onchange="calculateTotal()">
                                <label>Wild Mushroom Risotto</label>
                            </div>
                            <div class="service-quantity">
                                <label>Qty:</label>
                                <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotal()">
                            </div>
                            <div class="service-price">UGX 45,000</div>
                            <input type="hidden" name="price[]" value="45000">
                        </div>

                        <div class="service-item">
                            <div class="service-checkbox">
                                <input type="checkbox" name="service[]" value="Herb-Roasted Chicken" onchange="calculateTotal()">
                                <label>Herb-Roasted Chicken</label>
                            </div>
                            <div class="service-quantity">
                                <label>Qty:</label>
                                <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotal()">
                            </div>
                            <div class="service-price">UGX 50,000</div>
                            <input type="hidden" name="price[]" value="50000">
                        </div>

                        <!-- Desserts -->
                        <div style="margin-left: 0.5rem; margin-top: 1rem; margin-bottom: 0.5rem;">
                            <span style="color: #ffaa66; font-size: 0.9rem;"> DESSERTS</span>
                        </div>

                        <div class="service-item">
                            <div class="service-checkbox">
                                <input type="checkbox" name="service[]" value="Molten Lava Cake" onchange="calculateTotal()">
                                <label>Molten Lava Cake</label>
                            </div>
                            <div class="service-quantity">
                                <label>Qty:</label>
                                <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotal()">
                            </div>
                            <div class="service-price">UGX 25,000</div>
                            <input type="hidden" name="price[]" value="25000">
                        </div>

                        <div class="service-item">
                            <div class="service-checkbox">
                                <input type="checkbox" name="service[]" value="Lemon Tart" onchange="calculateTotal()">
                                <label>Lemon Tart</label>
                            </div>
                            <div class="service-quantity">
                                <label>Qty:</label>
                                <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotal()">
                            </div>
                            <div class="service-price">UGX 22,000</div>
                            <input type="hidden" name="price[]" value="22000">
                        </div>

                        <div class="service-item">
                            <div class="service-checkbox">
                                <input type="checkbox" name="service[]" value="New York Cheesecake" onchange="calculateTotal()">
                                <label>New York Cheesecake</label>
                            </div>
                            <div class="service-quantity">
                                <label>Qty:</label>
                                <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotal()">
                            </div>
                            <div class="service-price">UGX 28,000</div>
                            <input type="hidden" name="price[]" value="28000">
                        </div>
                    </div>

                    <!-- ===== SPA CATEGORY ===== -->
                    <div class="service-category">
                        <h3 class="category-title"> SPA & WELLNESS</h3>
                        
                        <div class="service-item">
                            <div class="service-checkbox">
                                <input type="checkbox" name="service[]" value="Facial Treatment" onchange="calculateTotal()">
                                <label>Facial Treatment</label>
                            </div>
                            <div class="service-quantity">
                                <label>Qty:</label>
                                <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotal()">
                            </div>
                            <div class="service-price">UGX 20,000</div>
                            <input type="hidden" name="price[]" value="20000">
                        </div>

                        <div class="service-item">
                            <div class="service-checkbox">
                                <input type="checkbox" name="service[]" value="Massage Therapy" onchange="calculateTotal()">
                                <label>Massage Therapy</label>
                            </div>
                            <div class="service-quantity">
                                <label>Qty:</label>
                                <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotal()">
                            </div>
                            <div class="service-price">UGX 100,000</div>
                            <input type="hidden" name="price[]" value="100000">
                        </div>

                        <div class="service-item">
                            <div class="service-checkbox">
                                <input type="checkbox" name="service[]" value="Body Treatment" onchange="calculateTotal()">
                                <label>Body Treatment (Scrub & Wrap)</label>
                            </div>
                            <div class="service-quantity">
                                <label>Qty:</label>
                                <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotal()">
                            </div>
                            <div class="service-price">UGX 80,000</div>
                            <input type="hidden" name="price[]" value="80000">
                        </div>

                        <div class="service-item">
                            <div class="service-checkbox">
                                <input type="checkbox" name="service[]" value="Salon Services" onchange="calculateTotal()">
                                <label>Salon Services (Hair & Nails)</label>
                            </div>
                            <div class="service-quantity">
                                <label>Qty:</label>
                                <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotal()">
                            </div>
                            <div class="service-price">UGX 50,000</div>
                            <input type="hidden" name="price[]" value="50000">
                        </div>
                    </div>

                    <!-- ===== GYM CATEGORY ===== -->
                    <div class="service-category">
                        <h3 class="category-title"> GYM & FITNESS</h3>
                        
                        <div class="service-item">
                            <div class="service-checkbox">
                                <input type="checkbox" name="service[]" value="Normal Workout" onchange="calculateTotal()">
                                <label>Normal Workout (Self-guided)</label>
                            </div>
                            <div class="service-quantity">
                                <label>Qty:</label>
                                <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotal()">
                            </div>
                            <div class="service-price">UGX 20,000 / session</div>
                            <input type="hidden" name="price[]" value="20000">
                        </div>

                        <div class="service-item">
                            <div class="service-checkbox">
                                <input type="checkbox" name="service[]" value="Workout with Trainer" onchange="calculateTotal()">
                                <label>Workout with Personal Trainer</label>
                            </div>
                            <div class="service-quantity">
                                <label>Qty:</label>
                                <input type="number" name="qty[]" value="1" min="1" onchange="calculateTotal()">
                            </div>
                            <div class="service-price">UGX 50,000 / session</div>
                            <input type="hidden" name="price[]" value="50000">
                        </div>
                    </div>

                    <!-- Price Quote -->
                    <div class="price-quote">
                        <p>Total Price Quote</p>
                        <span>UGX <span id="total">0</span></span>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" name="submit" class="submit-btn">PROCEED TO BOOK</button>
                </form>
            </div>
        </div>
    </div>
</main>

<!-- FOOTER -->
<footer>
    © 2025 MAGIC HOTEL LTD — Where elegance meets comfort. All rights reserved.
</footer>

<script>
function calculateTotal(){
    let prices = document.getElementsByName("price[]");
    let qtys = document.getElementsByName("qty[]");
    let checkboxes = document.getElementsByName("service[]");

    let total = 0;

    for(let i = 0; i < checkboxes.length; i++){
        if(checkboxes[i].checked){
            total += parseInt(prices[i].value) * parseInt(qtys[i].value);
        }
    }

    // Format the total with commas
    let formattedTotal = total.toLocaleString();
    document.getElementById("total").innerText = formattedTotal;
}

// Initialize total on page load
document.addEventListener("DOMContentLoaded", function() {
    calculateTotal();
});
</script>

</body>
</html>
