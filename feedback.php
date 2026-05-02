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
$message = '';
$error = '';
$catalog = getServiceCatalog($conn, false);
$categoryMeta = getCategoryMeta();
$settings = getHotelSettings($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $serviceId = (int) ($_POST['service_id'] ?? 0);
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = trim((string) ($_POST['comment'] ?? ''));
    $services = getServiceIndexById($conn, [$serviceId], false);
    $service = $services[$serviceId] ?? null;

    if (!$service) {
        $error = 'Please choose a valid service before submitting feedback.';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Please choose a rating from 1 to 5.';
    } elseif (mb_strlen($comment) < 5) {
        $error = 'Please write at least a short comment before submitting.';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO feedback (user_id, service_category, service_name, rating, comment, is_approved)
            VALUES (?, ?, ?, ?, ?, 0)
        ");
        $stmt->bind_param('issis', $userId, $service['category'], $service['service_name'], $rating, $comment);

        if ($stmt->execute()) {
            $message = 'Thanks for sharing your feedback. Our team will review it before publishing.';
        } else {
            $error = 'We could not save your feedback right now. Please try again.';
        }

        $stmt->close();
    }
}

$feedbackStmt = $conn->prepare("
    SELECT service_category, service_name, rating, comment, created_at, is_approved
    FROM feedback
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$feedbackStmt->bind_param('i', $userId);
$feedbackStmt->execute();
$feedbackResult = $feedbackStmt->get_result();
$recentFeedback = $feedbackResult->fetch_all(MYSQLI_ASSOC);
$feedbackStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Feedback - Magic Hotel</title>
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
                radial-gradient(circle at top, rgba(255, 122, 26, 0.14), transparent 28%),
                linear-gradient(180deg, #060606, #0d0d0d 40%, #151515);
            color: #f5f5f5;
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
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
        }

        .nav-links a {
            color: #e8e8e8;
            text-decoration: none;
        }

        .nav-links a:hover {
            color: #ffb27d;
        }

        .wrap {
            width: min(1080px, 100%);
            margin: 0 auto;
            padding: 48px 20px 60px;
        }

        .hero {
            margin-bottom: 24px;
            padding: 30px;
            border-radius: 28px;
            background:
                linear-gradient(135deg, rgba(255, 122, 26, 0.14), rgba(255, 122, 26, 0.02)),
                rgba(10, 10, 10, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .hero h1 {
            color: #ff7a1a;
            margin-bottom: 10px;
            font-size: clamp(2rem, 3vw, 3rem);
        }

        .hero p {
            color: #cccccc;
            line-height: 1.7;
            max-width: 760px;
        }

        .layout {
            display: grid;
            grid-template-columns: 0.95fr 1.05fr;
            gap: 24px;
            align-items: start;
        }

        .panel {
            padding: 28px;
            border-radius: 28px;
            background: rgba(10, 10, 10, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 24px 58px rgba(0, 0, 0, 0.24);
        }

        .panel h2 {
            color: #ffcfaa;
            margin-bottom: 10px;
        }

        .panel p {
            color: #c6c6c6;
            line-height: 1.7;
        }

        .alert {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 16px;
            line-height: 1.6;
        }

        .alert-success {
            background: rgba(45, 166, 93, 0.16);
            border-left: 4px solid #33a35c;
            color: #cbf2d7;
        }

        .alert-error {
            background: rgba(220, 80, 80, 0.14);
            border-left: 4px solid #d9534f;
            color: #ffc0c0;
        }

        .field {
            margin-top: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #dedede;
            font-size: 0.92rem;
        }

        select,
        textarea,
        input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid #2d2d2d;
            background: #101010;
            color: #f5f5f5;
            font: inherit;
        }

        select:focus,
        textarea:focus,
        input:focus {
            outline: none;
            border-color: #ff7a1a;
            box-shadow: 0 0 0 3px rgba(255, 122, 26, 0.16);
        }

        textarea {
            min-height: 150px;
            resize: vertical;
        }

        .rating-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-top: 10px;
        }

        .rating-row label {
            margin: 0;
            cursor: pointer;
        }

        .rating-row input {
            display: none;
        }

        .rating-pill {
            display: block;
            padding: 12px 10px;
            border-radius: 16px;
            text-align: center;
            background: #121212;
            border: 1px solid #2c2c2c;
            color: #d0d0d0;
            transition: 0.2s ease;
        }

        .rating-row input:checked + .rating-pill,
        .rating-pill:hover {
            border-color: #ff7a1a;
            background: rgba(255, 122, 26, 0.12);
            color: #ffcfaa;
        }

        .button {
            width: 100%;
            margin-top: 18px;
            padding: 15px 18px;
            border: none;
            border-radius: 18px;
            background: linear-gradient(135deg, #ff7a1a, #ff9d52);
            color: #151515;
            font-weight: 700;
            cursor: pointer;
        }

        .history-list {
            display: grid;
            gap: 14px;
            margin-top: 18px;
        }

        .history-item {
            padding: 18px;
            border-radius: 18px;
            background: #111111;
            border: 1px solid #222222;
        }

        .history-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .history-top strong {
            color: #ffffff;
        }

        .history-top span,
        .history-meta {
            color: #bdbdbd;
            font-size: 0.94rem;
        }

        .status-pill {
            display: inline-flex;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .status-approved {
            background: rgba(45, 166, 93, 0.16);
            color: #bcecc9;
        }

        .status-pending {
            background: rgba(255, 122, 26, 0.14);
            color: #ffcfaa;
        }

        .empty-state {
            margin-top: 18px;
            padding: 20px;
            border-radius: 18px;
            background: #111111;
            border: 1px solid #222222;
            color: #c7c7c7;
        }

        .support-box {
            margin-top: 20px;
            padding: 18px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(255, 122, 26, 0.1), rgba(255, 122, 26, 0.02));
            border: 1px solid rgba(255, 122, 26, 0.18);
            color: #d1d1d1;
            line-height: 1.7;
        }

        footer {
            text-align: center;
            color: #979797;
            padding: 30px 20px 40px;
        }

        @media (max-width: 920px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .navbar {
                padding: 16px 18px;
                flex-direction: column;
                align-items: flex-start;
            }

            .panel,
            .hero {
                padding: 22px;
            }

            .rating-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a class="logo" href="client_dashboard.php"><?php echo htmlspecialchars($settings['hotel_name']); ?></a>
        <div class="nav-links">
            <a href="client_dashboard.php">Dashboard</a>
            <a href="services.php">Services</a>
            <a href="contact.php">Contact</a>
            <a href="booking.php">Book More</a>
        </div>
    </nav>

    <main class="wrap">
        <section class="hero">
            <h1>Guest feedback for <?php echo htmlspecialchars($userName); ?></h1>
            <p>Tell us how your stay, meal, drink, or wellness service went. Your comments now feed directly into the admin management dashboard for review and follow-up.</p>
        </section>

        <div class="layout">
            <section class="panel">
                <h2>Share Feedback</h2>
                <p>Choose the service you want to review and leave a short note for the hotel team.</p>

                <?php if ($message !== ''): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="field">
                        <label for="service_id">Service</label>
                        <select id="service_id" name="service_id" required>
                            <option value="">Select a service</option>
                            <?php foreach ($catalog as $category => $sections): ?>
                                <optgroup label="<?php echo htmlspecialchars($categoryMeta[$category]['label'] ?? ucfirst($category)); ?>">
                                    <?php foreach ($sections as $sectionName => $services): ?>
                                        <?php foreach ($services as $service): ?>
                                            <option value="<?php echo (int) $service['id']; ?>">
                                                <?php echo htmlspecialchars($service['service_name'] . ' - ' . $sectionName); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label>Rating</label>
                        <div class="rating-row">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label>
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" required>
                                    <span class="rating-pill"><?php echo $i; ?> / 5</span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="field">
                        <label for="comment">Comment</label>
                        <textarea id="comment" name="comment" placeholder="What stood out, and what can we improve?" required></textarea>
                    </div>

                    <button class="button" type="submit" name="submit_feedback">Submit Feedback</button>
                </form>
            </section>

            <section class="panel">
                <h2>Recent Feedback</h2>
                <p>Your latest comments and whether they have already been approved for internal review reporting.</p>

                <?php if (!empty($recentFeedback)): ?>
                    <div class="history-list">
                        <?php foreach ($recentFeedback as $item): ?>
                            <article class="history-item">
                                <div class="history-top">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['service_name']); ?></strong>
                                        <div class="history-meta"><?php echo htmlspecialchars($categoryMeta[$item['service_category']]['label'] ?? ucfirst($item['service_category'])); ?> service</div>
                                    </div>
                                    <span><?php echo htmlspecialchars(date('d M Y', strtotime($item['created_at']))); ?></span>
                                </div>

                                <div class="history-meta">Rating: <?php echo (int) $item['rating']; ?>/5</div>
                                <p style="margin-top: 12px;"><?php echo nl2br(htmlspecialchars($item['comment'])); ?></p>
                                <div style="margin-top: 14px;">
                                    <span class="status-pill <?php echo ((int) $item['is_approved'] === 1) ? 'status-approved' : 'status-pending'; ?>">
                                        <?php echo ((int) $item['is_approved'] === 1) ? 'Approved' : 'Pending review'; ?>
                                    </span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">You have not submitted feedback yet.</div>
                <?php endif; ?>

                <div class="support-box">
                    Need direct help instead? Contact the team at <?php echo htmlspecialchars($settings['primary_phone']); ?> or <?php echo htmlspecialchars($settings['email']); ?>.
                </div>
            </section>
        </div>
    </main>

    <footer>
        <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['hotel_name']); ?>. <?php echo htmlspecialchars($settings['tagline']); ?>
    </footer>
</body>
</html>

