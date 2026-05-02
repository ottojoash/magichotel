<?php

require_once 'config.php';
require_once 'hotel_helpers.php';

$settings = getHotelSettings($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Magic Hotel</title>
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
                linear-gradient(180deg, #050505, #0d0d0d 40%, #151515);
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
            color: #ececec;
            text-decoration: none;
        }

        .nav-links a:hover {
            color: #ffb27d;
        }

        .wrap {
            width: min(1120px, 100%);
            margin: 0 auto;
            padding: 54px 20px 60px;
        }

        .hero {
            margin-bottom: 22px;
            padding: 34px;
            border-radius: 32px;
            background:
                linear-gradient(135deg, rgba(255, 122, 26, 0.16), rgba(255, 122, 26, 0.03)),
                rgba(10, 10, 10, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .hero h1 {
            color: #ff7a1a;
            font-size: clamp(2.2rem, 3vw, 3.2rem);
            margin-bottom: 12px;
        }

        .hero p {
            max-width: 760px;
            color: #cbcbcb;
            line-height: 1.7;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
        }

        .card {
            padding: 22px;
            border-radius: 24px;
            background: rgba(10, 10, 10, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 22px 54px rgba(0, 0, 0, 0.24);
        }

        .card strong {
            display: block;
            margin-bottom: 10px;
            color: #ffcfaa;
            font-size: 0.84rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .card a,
        .card span,
        .card p {
            color: #f4f4f4;
            text-decoration: none;
            line-height: 1.7;
        }

        .card a:hover {
            color: #ffb27d;
        }

        .cta {
            margin-top: 24px;
            padding: 26px;
            border-radius: 28px;
            background: linear-gradient(135deg, rgba(255, 122, 26, 0.14), rgba(255, 122, 26, 0.03));
            border: 1px solid rgba(255, 122, 26, 0.18);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .cta h2 {
            margin-bottom: 8px;
        }

        .cta p {
            color: #d0d0d0;
            line-height: 1.7;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 18px;
            border-radius: 18px;
            text-decoration: none;
            font-weight: 700;
            background: linear-gradient(135deg, #ff7a1a, #ff9d52);
            color: #151515;
        }

        footer {
            padding: 28px 20px 40px;
            text-align: center;
            color: #989898;
        }

        @media (max-width: 640px) {
            .navbar {
                padding: 16px 18px;
                flex-direction: column;
                align-items: flex-start;
            }

            .hero,
            .card,
            .cta {
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
            <a href="booking.php">Booking</a>
            <a href="login.php">Client Login</a>
        </div>
    </nav>

    <main class="wrap">
        <section class="hero">
            <h1>Contact Magic Hotel</h1>
            <p>The contact details on this page are now managed from the admin dashboard, so the phone numbers, email addresses, and operating hours stay consistent across the whole project.</p>
        </section>

        <section class="grid">
            <article class="card">
                <strong>Primary Phone</strong>
                <a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $settings['primary_phone'])); ?>">
                    <?php echo htmlspecialchars($settings['primary_phone']); ?>
                </a>
            </article>

            <article class="card">
                <strong>Secondary Phone</strong>
                <a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $settings['secondary_phone'])); ?>">
                    <?php echo htmlspecialchars($settings['secondary_phone']); ?>
                </a>
            </article>

            <article class="card">
                <strong>WhatsApp</strong>
                <a href="https://wa.me/<?php echo htmlspecialchars($settings['whatsapp_number']); ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo htmlspecialchars($settings['secondary_phone']); ?>
                </a>
            </article>

            <article class="card">
                <strong>Email</strong>
                <a href="mailto:<?php echo htmlspecialchars($settings['email']); ?>">
                    <?php echo htmlspecialchars($settings['email']); ?>
                </a>
            </article>

            <article class="card">
                <strong>Reservations</strong>
                <a href="mailto:<?php echo htmlspecialchars($settings['reservation_email']); ?>">
                    <?php echo htmlspecialchars($settings['reservation_email']); ?>
                </a>
            </article>

            <article class="card">
                <strong>Address</strong>
                <p><?php echo nl2br(htmlspecialchars($settings['address'])); ?></p>
            </article>

            <article class="card">
                <strong>Front Desk</strong>
                <span><?php echo htmlspecialchars($settings['front_desk_hours']); ?></span>
            </article>

            <article class="card">
                <strong>Restaurant</strong>
                <span><?php echo htmlspecialchars($settings['restaurant_hours']); ?></span>
            </article>

            <article class="card">
                <strong>Bar</strong>
                <span><?php echo htmlspecialchars($settings['bar_hours']); ?></span>
            </article>
        </section>

        <section class="cta">
            <div>
                <h2>Need a reservation now?</h2>
                <p>Book rooms, meals, and bar items directly online or reach the hotel team through the details above.</p>
            </div>
            <a class="button" href="booking.php">Book Now</a>
        </section>
    </main>

    <footer>
        <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['hotel_name']); ?>. <?php echo htmlspecialchars($settings['tagline']); ?>
    </footer>
</body>
</html>

