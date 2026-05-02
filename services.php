<?php

require_once 'config.php';
require_once 'hotel_helpers.php';

$settings = getHotelSettings($conn);
$categoryMeta = getCategoryMeta();
$catalog = getServiceCatalog($conn, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - Magic Hotel</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background:
                radial-gradient(circle at top, rgba(255, 122, 26, 0.14), transparent 28%),
                linear-gradient(180deg, #050505, #0d0d0d 38%, #141414);
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

        .hero {
            width: min(1180px, 100%);
            margin: 0 auto;
            padding: 60px 20px 34px;
        }

        .hero-card {
            padding: 34px;
            border-radius: 32px;
            background:
                linear-gradient(135deg, rgba(255, 122, 26, 0.16), rgba(255, 122, 26, 0.03)),
                rgba(10, 10, 10, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .hero-card h1 {
            color: #ff7a1a;
            font-size: clamp(2.2rem, 3vw, 3.4rem);
            margin-bottom: 12px;
        }

        .hero-card p {
            max-width: 780px;
            color: #c9c9c9;
            line-height: 1.7;
        }

        .hero-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
            margin-top: 24px;
        }

        .hero-meta div {
            padding: 18px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            color: #f2f2f2;
        }

        .hero-meta strong {
            display: block;
            margin-bottom: 8px;
            color: #ffcfaa;
            font-size: 0.86rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .wrap {
            width: min(1180px, 100%);
            margin: 0 auto;
            padding: 0 20px 60px;
        }

        .category-card {
            margin-top: 22px;
            padding: 28px;
            border-radius: 28px;
            background: rgba(10, 10, 10, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 24px 56px rgba(0, 0, 0, 0.24);
        }

        .category-label {
            color: #ffb27d;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.84rem;
            margin-bottom: 8px;
        }

        .category-card h2 {
            color: #ffffff;
            margin-bottom: 10px;
            font-size: 1.9rem;
        }

        .category-card > p {
            color: #c5c5c5;
            line-height: 1.7;
            margin-bottom: 18px;
        }

        .section-title {
            margin: 18px 0 14px;
            color: #ffcfaa;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 14px;
        }

        .service-card {
            padding: 18px;
            border-radius: 22px;
            background: #111111;
            border: 1px solid #222222;
        }

        .service-card h3 {
            color: #ffffff;
            margin-bottom: 8px;
            font-size: 1.02rem;
        }

        .service-card p {
            color: #bababa;
            line-height: 1.6;
            font-size: 0.94rem;
            min-height: 74px;
        }

        .service-price {
            margin-top: 12px;
            color: #ffb27d;
            font-weight: 700;
        }

        .cta {
            margin-top: 28px;
            padding: 26px;
            border-radius: 28px;
            background: linear-gradient(135deg, rgba(255, 122, 26, 0.14), rgba(255, 122, 26, 0.03));
            border: 1px solid rgba(255, 122, 26, 0.18);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
        }

        .cta h3 {
            color: #ffffff;
            margin-bottom: 8px;
        }

        .cta p {
            color: #d3d3d3;
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
        }

        .button-primary {
            background: linear-gradient(135deg, #ff7a1a, #ff9d52);
            color: #151515;
        }

        .button-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: #f1f1f1;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        footer {
            padding: 28px 20px 40px;
            text-align: center;
            color: #969696;
        }

        @media (max-width: 640px) {
            .navbar {
                padding: 16px 18px;
                flex-direction: column;
                align-items: flex-start;
            }

            .hero-card,
            .category-card,
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
            <a href="booking.php">Booking</a>
            <a href="contact.php">Contact</a>
            <a href="login.php">Client Login</a>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-card">
            <h1>Services, menu items, and bar list</h1>
            <p>Every item below comes from the live hotel service catalog, including restaurant prices, bar items, room options, and wellness services. The admin team can manage these in one place and the updates appear here automatically.</p>

            <div class="hero-meta">
                <div>
                    <strong>Restaurant Hours</strong>
                    <span><?php echo htmlspecialchars($settings['restaurant_hours']); ?></span>
                </div>
                <div>
                    <strong>Bar Hours</strong>
                    <span><?php echo htmlspecialchars($settings['bar_hours']); ?></span>
                </div>
                <div>
                    <strong>Reservations</strong>
                    <span><?php echo htmlspecialchars($settings['reservation_email']); ?></span>
                </div>
            </div>
        </div>
    </section>

    <main class="wrap">
        <?php foreach ($catalog as $category => $sections): ?>
            <section class="category-card">
                <div class="category-label"><?php echo htmlspecialchars($categoryMeta[$category]['label'] ?? ucfirst($category)); ?></div>
                <h2><?php echo htmlspecialchars($categoryMeta[$category]['label'] ?? ucfirst($category)); ?></h2>
                <p><?php echo htmlspecialchars($categoryMeta[$category]['summary'] ?? ''); ?></p>

                <?php foreach ($sections as $sectionName => $services): ?>
                    <div class="section-title"><?php echo htmlspecialchars($sectionName); ?></div>
                    <div class="service-grid">
                        <?php foreach ($services as $service): ?>
                            <article class="service-card">
                                <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                                <p><?php echo htmlspecialchars((string) $service['description']); ?></p>
                                <div class="service-price">
                                    <?php echo htmlspecialchars(formatUgx((float) $service['price'])); ?> / <?php echo htmlspecialchars($service['pricing_unit']); ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>

        <section class="cta">
            <div>
                <h3>Ready to book?</h3>
                <p>Reserve rooms, order restaurant meals, and add bar items through the booking flow or your client dashboard.</p>
            </div>
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <a class="button button-primary" href="booking.php">Book Now</a>
                <a class="button button-secondary" href="contact.php">Contact Hotel</a>
            </div>
        </section>
    </main>

    <footer>
        <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['hotel_name']); ?>. <?php echo htmlspecialchars($settings['tagline']); ?>
    </footer>
</body>
</html>

