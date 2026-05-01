
<?php
// feedback.php - Magic Hotel Feedback System
session_start();

// Database configuration (optional - for future implementation)
// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_NAME', 'magic_hotel');

// Services array
$services = [
    'rooms' => 'Rooms',
    'spa' => 'Spa',
    'gym' => 'Gym',
    'restaurant' => 'Restaurant',
    'bar' => 'Bar'
];

// Initialize variables
$message = "";
$error = "";
$selected_service = "";
$selected_rating = 0;
$user_comment = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit_feedback'])) {
        $selected_service = isset($_POST['service']) ? trim($_POST['service']) : '';
        $selected_rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $user_comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
        
        // Validation
        if (empty($selected_service)) {
            $error = "Please select a service to review.";
        } elseif ($selected_rating < 1 || $selected_rating > 5) {
            $error = "Please select a star rating (1-5).";
        } elseif (empty($user_comment)) {
            $error = "Please write your feedback comment.";
        } elseif (strlen($user_comment) < 5) {
            $error = "Please provide more detailed feedback (at least 5 characters).";
        } else {
            // In a real application, you would save to database here
            // For demonstration, we'll store in session and display success
            
            // Save feedback to session array (simulating database)
            if (!isset($_SESSION['feedbacks'])) {
                $_SESSION['feedbacks'] = [];
            }
            
            $feedback_data = [
                'service' => $selected_service,
                'service_name' => $services[$selected_service],
                'rating' => $selected_rating,
                'comment' => htmlspecialchars($user_comment),
                'date' => date('Y-m-d H:i:s'),
                'ip' => $_SERVER['REMOTE_ADDR']
            ];
            
            array_unshift($_SESSION['feedbacks'], $feedback_data);
            
            // Keep only last 50 feedbacks
            if (count($_SESSION['feedbacks']) > 50) {
                array_pop($_SESSION['feedbacks']);
            }
            
            $message = "Thank you for your valuable feedback on " . $services[$selected_service] . "! Your rating: " . $selected_rating . "/5 stars.";
            
            // Clear form after successful submission
            $selected_service = "";
            $selected_rating = 0;
            $user_comment = "";
        }
    }
    
    // Handle delete feedback (admin feature)
    if (isset($_POST['delete_feedback']) && isset($_POST['delete_index'])) {
        $delete_index = intval($_POST['delete_index']);
        if (isset($_SESSION['feedbacks'][$delete_index])) {
            array_splice($_SESSION['feedbacks'], $delete_index, 1);
            $message = "Feedback has been removed.";
        }
    }
    
    // Clear all feedbacks
    if (isset($_POST['clear_all'])) {
        $_SESSION['feedbacks'] = [];
        $message = "All feedback has been cleared.";
    }
}

// Get recent feedbacks for display
$recent_feedbacks = isset($_SESSION['feedbacks']) ? $_SESSION['feedbacks'] : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magic Hotel - Customer Feedback</title>
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

        /* ===== HERO SECTION - Expanded Height ===== */
        .hero-feedback {
            width: 100%;
            height: 75vh;
            min-height: 500px;
            background: linear-gradient(135deg, rgba(0,0,0,0.85), rgba(255,102,0,0.25)), url('https://images.pexels.com/photos/164595/pexels-photo-164595.jpeg?auto=compress&cs=tinysrgb&w=1600');
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
            margin-bottom: 1rem;
        }

        .hero-overlay h1 span {
            color: #ff6600;
        }

        .hero-overlay p {
            font-size: 1.1rem;
            color: #ddd;
        }

        /* ===== MESSAGE ALERTS ===== */
        .message-container {
            max-width: 900px;
            margin: 2rem auto 0;
            padding: 0 1rem;
        }
        .alert-success {
            background: rgba(255, 102, 0, 0.2);
            border-left: 5px solid #ff6600;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            color: #ffaa66;
            text-align: center;
            font-weight: 500;
        }
        .alert-error {
            background: rgba(220, 53, 69, 0.2);
            border-left: 5px solid #dc3545;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            color: #ff8888;
            text-align: center;
        }

        /* ===== MAIN CONTENT ===== */
        main {
            padding: 50px 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Center the form */
        .feedback-form-col {
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
        }

        /* ===== FEEDBACK FORM - Orange Background ===== */
        .form-container {
            background: #ff6600;
            border-radius: 28px;
            padding: 2rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        }

        .form-container h2 {
            color: #000000;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .form-container p {
            color: #1a1a1a;
            margin-bottom: 1.8rem;
            font-size: 0.9rem;
        }

        .input-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.6rem;
            font-size: 0.85rem;
            letter-spacing: 1px;
            color: #1a1a1a;
            font-weight: 600;
        }

        /* Service Select */
        .input-group select {
            width: 100%;
            padding: 0.9rem 1.2rem;
            background: #ffffff;
            border: none;
            border-radius: 30px;
            font-size: 1rem;
            color: #111;
            font-family: inherit;
            cursor: pointer;
        }

        .input-group select:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,0,0,0.2);
        }

        /* Star Rating */
        .rating-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .rating-group label {
            display: block;
            margin-bottom: 0.6rem;
            font-size: 0.85rem;
            letter-spacing: 1px;
            color: #1a1a1a;
            font-weight: 600;
        }

        .stars {
            display: flex;
            gap: 0.5rem;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .stars input {
            display: none;
        }

        .stars label {
            font-size: 2.2rem;
            color: #555;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 0;
        }

        .stars label:hover,
        .stars label:hover ~ label,
        .stars input:checked ~ label {
            color: #ffdd00;
            text-shadow: 0 0 5px rgba(0,0,0,0.3);
        }

        /* Comment Textarea */
        .input-group textarea {
            width: 100%;
            padding: 0.9rem 1.2rem;
            background: #ffffff;
            border: none;
            border-radius: 20px;
            font-size: 1rem;
            color: #111;
            transition: 0.2s;
            font-family: inherit;
            resize: vertical;
        }

        .input-group textarea:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,0,0,0.2);
        }

        .submit-btn {
            background: #000000;
            color: #ff6600;
            border: none;
            padding: 0.9rem 2rem;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 40px;
            cursor: pointer;
            transition: 0.2s;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .submit-btn:hover {
            background: #1a1a1a;
            color: #ff8833;
            transform: translateY(-2px);
        }

        /* Footer - No border line */
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
        @media (max-width: 900px) {
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
            main {
                padding: 30px 15px;
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
        <a href="contact.html">Contact</a>
    </div>
</div>

<!-- HERO SECTION - Expanded Height -->
<div class="hero-feedback">
    <div class="hero-overlay">
        <h1>YOUR <span>FEEDBACK</span></h1>
        <p>Your voice matters — help us create magical experiences</p>
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

<main>
    <div class="feedback-form-col">
        <div class="form-container">
            <h2>Share Your Experience</h2>
            <p>Tell us about your stay at Magic Hotel</p>
            
            <form method="POST" action="">
                <!-- Service Selection -->
                <div class="input-group">
                    <label>Select Service</label>
                    <select name="service" required>
                        <option value="">-- Choose a service --</option>
                        <?php foreach ($services as $key => $name): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($selected_service == $key) ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Star Rating -->
                <div class="rating-group">
                    <label>Your Rating</label>
                    <div class="stars">
                        <input type="radio" name="rating" id="star5" value="5" <?php echo ($selected_rating == 5) ? 'checked' : ''; ?>>
                        <label for="star5">★</label>
                        <input type="radio" name="rating" id="star4" value="4" <?php echo ($selected_rating == 4) ? 'checked' : ''; ?>>
                        <label for="star4">★</label>
                        <input type="radio" name="rating" id="star3" value="3" <?php echo ($selected_rating == 3) ? 'checked' : ''; ?>>
                        <label for="star3">★</label>
                        <input type="radio" name="rating" id="star2" value="2" <?php echo ($selected_rating == 2) ? 'checked' : ''; ?>>
                        <label for="star2">★</label>
                        <input type="radio" name="rating" id="star1" value="1" <?php echo ($selected_rating == 1) ? 'checked' : ''; ?>>
                        <label for="star1">★</label>
                    </div>
                </div>
                
                <!-- Comment -->
                <div class="input-group">
                    <label>Your Feedback / Comments</label>
                    <textarea name="comment" rows="4" placeholder="Tell us what you loved or how we can improve..."><?php echo htmlspecialchars($user_comment); ?></textarea>
                </div>
                
                <button type="submit" name="submit_feedback" class="submit-btn">Submit Feedback</button>
            </form>
        </div>
    </div>
</main>

<footer>
    © 2025 MAGIC HOTEL LTD — Where elegance meets comfort. All rights reserved.
</footer>

</body>
</html>
```