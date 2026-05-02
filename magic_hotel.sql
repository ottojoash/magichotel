CREATE DATABASE IF NOT EXISTS magic_hotel
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE magic_hotel;

CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
    id INT(11) NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'staff',
    department VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    shift_schedule VARCHAR(100) DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_email (email),
    KEY idx_admins_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
    id INT(11) NOT NULL AUTO_INCREMENT,
    category VARCHAR(50) NOT NULL,
    menu_section VARCHAR(100) NOT NULL DEFAULT 'General',
    service_name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    pricing_unit VARCHAR(50) NOT NULL DEFAULT 'item',
    sort_order INT(11) NOT NULL DEFAULT 0,
    is_available TINYINT(1) DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_services_category (category),
    KEY idx_services_availability (is_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    service_name VARCHAR(255) NOT NULL,
    quantity INT(11) NOT NULL DEFAULT 1,
    price_per_unit DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    booking_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'paid', 'refunded') DEFAULT 'unpaid',
    PRIMARY KEY (id),
    KEY idx_bookings_user_id (user_id),
    CONSTRAINT bookings_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS feedback (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    service_category ENUM('rooms', 'spa', 'gym', 'restaurant', 'bar') NOT NULL,
    service_name VARCHAR(255) NOT NULL,
    rating INT(11) NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_approved TINYINT(1) DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_feedback_user_id (user_id),
    KEY idx_feedback_category (service_category),
    KEY idx_feedback_rating (rating),
    CONSTRAINT feedback_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT chk_feedback_rating CHECK (rating >= 1 AND rating <= 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hotel_settings (
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE admins MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'staff';
ALTER TABLE admins ADD COLUMN IF NOT EXISTS department VARCHAR(100) DEFAULT NULL AFTER role;
ALTER TABLE admins ADD COLUMN IF NOT EXISTS phone VARCHAR(50) DEFAULT NULL AFTER department;
ALTER TABLE admins ADD COLUMN IF NOT EXISTS shift_schedule VARCHAR(100) DEFAULT NULL AFTER phone;
ALTER TABLE admins ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') NOT NULL DEFAULT 'active' AFTER shift_schedule;

ALTER TABLE services ADD COLUMN IF NOT EXISTS menu_section VARCHAR(100) NOT NULL DEFAULT 'General' AFTER category;
ALTER TABLE services ADD COLUMN IF NOT EXISTS pricing_unit VARCHAR(50) NOT NULL DEFAULT 'item' AFTER price;
ALTER TABLE services ADD COLUMN IF NOT EXISTS sort_order INT(11) NOT NULL DEFAULT 0 AFTER pricing_unit;

UPDATE admins
SET role = 'front desk',
    department = COALESCE(NULLIF(department, ''), 'Front Office'),
    shift_schedule = COALESCE(NULLIF(shift_schedule, ''), 'Day Shift'),
    status = COALESCE(status, 'active')
WHERE email = 'staff@magichotel.com';

UPDATE services
SET menu_section = 'Accommodation',
    pricing_unit = 'night',
    sort_order = CASE service_name
        WHEN 'Single Room' THEN 10
        WHEN 'Double Room' THEN 20
        ELSE sort_order
    END
WHERE category = 'rooms';

UPDATE services
SET menu_section = 'Treatments',
    pricing_unit = 'session',
    sort_order = CASE service_name
        WHEN 'Facial Treatment' THEN 10
        WHEN 'Massage Therapy' THEN 20
        WHEN 'Body Treatment' THEN 30
        WHEN 'Salon Services' THEN 40
        ELSE sort_order
    END
WHERE category = 'spa';

UPDATE services
SET menu_section = 'Fitness',
    pricing_unit = 'session',
    sort_order = CASE service_name
        WHEN 'Normal Workout' THEN 10
        WHEN 'Workout with Trainer' THEN 20
        ELSE sort_order
    END
WHERE category = 'gym';

INSERT IGNORE INTO users (name, email, phone, password, created_at)
VALUES
    ('Guest Client', 'guest@magichotel.com', '+256700000123', '$2y$10$PLaIVm.YFZRIQ7m.RdAhNOnJegWyNUZ2NOy53FxRygcxJQC786TLy', CURRENT_TIMESTAMP),
    ('Demo Client', 'client@magichotel.com', '+256774000700', '$2y$10$brWC4UovfnX/IqgbhXX3.e7YD12z/siw.bJFZLFJyxr7N.9pDf7Fm', CURRENT_TIMESTAMP);

INSERT IGNORE INTO admins (full_name, email, password, role, department, phone, shift_schedule, status, created_at)
VALUES
    ('Super Admin', 'admin@magichotel.com', '$2y$10$fMTyT2bDwpwUaL70q87dJefNh1mR.fUIq6crov0ulDwtr0xfXGdta', 'admin', 'Executive', '+256700000001', 'Full Access', 'active', CURRENT_TIMESTAMP),
    ('Hotel Manager', 'manager@magichotel.com', '$2y$10$j759.3ljkJUhySbGXKddi.wOYrSb26K.sJkt4kTMPYvcTDeUVizBC', 'manager', 'Operations', '+256700000002', 'Day Shift', 'active', CURRENT_TIMESTAMP),
    ('Front Desk Staff', 'staff@magichotel.com', '$2y$10$5te2p/PlM6h77hJy0iXQeu9i0IY.QIoeE8gQZsiQBCxlUZkn64xqu', 'front desk', 'Front Office', '+256700000003', 'Day Shift', 'active', CURRENT_TIMESTAMP),
    ('Head Chef', 'chef@magichotel.com', '$2y$10$fMCsxs.FSkFqI7/AF2m/3.Z76ktFa2pUIofzGm2/m9Uif1M0YojSW', 'chef', 'Kitchen', '+256700000004', 'Morning Shift', 'active', CURRENT_TIMESTAMP),
    ('Bar Supervisor', 'bar@magichotel.com', '$2y$10$9tPz3X4p87j0qahy1P/MsOZxAmIzqVrr2eeiTlpbt.barumYQgn2m', 'bartender', 'Bar', '+256700000005', 'Evening Shift', 'active', CURRENT_TIMESTAMP),
    ('Housekeeping Lead', 'housekeeping@magichotel.com', '$2y$10$SoUMsRkNMCxrKbKGW0r5FOfOgpLKLih5KR2M5GhqMmyg3k5z8NDSC', 'housekeeping', 'Housekeeping', '+256700000006', 'Day Shift', 'active', CURRENT_TIMESTAMP);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'rooms', 'Accommodation', 'Single Room', 'Comfortable single room with city view and breakfast access.', 100000.00, 'night', 10, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'rooms' AND service_name = 'Single Room'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'rooms', 'Accommodation', 'Double Room', 'Spacious double room with premium amenities and workspace.', 200000.00, 'night', 20, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'rooms' AND service_name = 'Double Room'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'rooms', 'Accommodation', 'Executive Suite', 'Executive suite with lounge space, bathtub, and airport transfer support.', 350000.00, 'night', 30, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'rooms' AND service_name = 'Executive Suite'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'restaurant', 'Starters', 'Crispy Calamari', 'Lightly battered calamari served with tangy marinara sauce.', 35000.00, 'plate', 110, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'restaurant' AND service_name = 'Crispy Calamari'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'restaurant', 'Starters', 'Truffle Fries', 'Golden fries finished with truffle oil, parmesan, and herbs.', 22000.00, 'plate', 120, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'restaurant' AND service_name = 'Truffle Fries'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'restaurant', 'Starters', 'Stuffed Mushrooms', 'Baked mushrooms filled with cream cheese, garlic, and herbs.', 28000.00, 'plate', 130, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'restaurant' AND service_name = 'Stuffed Mushrooms'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'restaurant', 'Starters', 'Roasted Tomato Soup', 'Creamy roasted tomato soup with basil and buttered croutons.', 25000.00, 'bowl', 140, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'restaurant' AND service_name = 'Roasted Tomato Soup'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'restaurant', 'Main Courses', 'Pan-Seared Salmon', 'Fresh salmon with lemon butter sauce and seasonal vegetables.', 65000.00, 'plate', 210, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'restaurant' AND service_name = 'Pan-Seared Salmon'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'restaurant', 'Main Courses', 'Classic Ribeye Steak', 'Juicy ribeye steak with rosemary potatoes and pepper sauce.', 75000.00, 'plate', 220, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'restaurant' AND service_name = 'Classic Ribeye Steak'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'restaurant', 'Main Courses', 'Wild Mushroom Risotto', 'Creamy arborio rice with mushrooms and a parmesan finish.', 45000.00, 'plate', 230, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'restaurant' AND service_name = 'Wild Mushroom Risotto'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'restaurant', 'Main Courses', 'Herb-Roasted Chicken', 'Roasted chicken with garlic herbs and buttery mash.', 50000.00, 'plate', 240, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'restaurant' AND service_name = 'Herb-Roasted Chicken'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'restaurant', 'Desserts', 'Molten Lava Cake', 'Warm chocolate cake with a molten centre and vanilla cream.', 25000.00, 'plate', 310, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'restaurant' AND service_name = 'Molten Lava Cake'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'restaurant', 'Desserts', 'Lemon Tart', 'Zesty lemon tart in a buttery pastry shell.', 22000.00, 'slice', 320, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'restaurant' AND service_name = 'Lemon Tart'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'restaurant', 'Desserts', 'New York Cheesecake', 'Classic creamy cheesecake served with berry compote.', 28000.00, 'slice', 330, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'restaurant' AND service_name = 'New York Cheesecake'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'bar', 'Signature Cocktails', 'Classic Mojito', 'Fresh mint, lime, and house white rum.', 25000.00, 'glass', 410, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'bar' AND service_name = 'Classic Mojito'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'bar', 'Signature Cocktails', 'Old Fashioned', 'Bourbon, bitters, citrus zest, and cane syrup.', 30000.00, 'glass', 420, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'bar' AND service_name = 'Old Fashioned'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'bar', 'Wines', 'House Red Wine', 'Smooth dry red poured by the glass.', 28000.00, 'glass', 510, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'bar' AND service_name = 'House Red Wine'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'bar', 'Wines', 'Sauvignon Blanc', 'Crisp white wine with tropical fruit notes.', 32000.00, 'glass', 520, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'bar' AND service_name = 'Sauvignon Blanc'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'bar', 'Soft Drinks', 'Tropical Punch', 'House tropical punch served chilled.', 18000.00, 'glass', 610, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'bar' AND service_name = 'Tropical Punch'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'bar', 'Soft Drinks', 'Fresh Passion Juice', 'Freshly blended passion juice with no artificial syrups.', 12000.00, 'glass', 620, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'bar' AND service_name = 'Fresh Passion Juice'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'bar', 'Coffee & Tea', 'Espresso', 'Double-shot espresso for a quick energy lift.', 9000.00, 'cup', 710, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'bar' AND service_name = 'Espresso'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'spa', 'Treatments', 'Facial Treatment', 'Rejuvenating facial with organic skincare products.', 20000.00, 'session', 810, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'spa' AND service_name = 'Facial Treatment'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'spa', 'Treatments', 'Massage Therapy', 'Full-body massage designed to release stress and tension.', 100000.00, 'session', 820, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'spa' AND service_name = 'Massage Therapy'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'spa', 'Treatments', 'Body Treatment', 'Exfoliating scrub and body wrap for skin renewal.', 80000.00, 'session', 830, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'spa' AND service_name = 'Body Treatment'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'spa', 'Treatments', 'Salon Services', 'Hair styling, grooming, and nail care support.', 50000.00, 'session', 840, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'spa' AND service_name = 'Salon Services'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'gym', 'Fitness', 'Normal Workout', 'Self-guided gym access with locker support.', 20000.00, 'session', 910, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'gym' AND service_name = 'Normal Workout'
);

INSERT INTO services (category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available)
SELECT 'gym', 'Fitness', 'Workout with Trainer', 'One-on-one session with a personal trainer.', 50000.00, 'session', 920, 1
WHERE NOT EXISTS (
    SELECT 1 FROM services WHERE category = 'gym' AND service_name = 'Workout with Trainer'
);

INSERT INTO hotel_settings (setting_key, setting_value)
VALUES
    ('hotel_name', 'Magic Hotel'),
    ('tagline', 'Where elegance meets comfort.'),
    ('primary_phone', '+256 700 000 234'),
    ('secondary_phone', '+256 774 070 756'),
    ('whatsapp_number', '256774070756'),
    ('email', 'contact@magichotel.com'),
    ('reservation_email', 'reservations@magichotel.com'),
    ('address', 'Plot 12, Sunset Avenue, Kampala, Uganda'),
    ('front_desk_hours', 'Open 24/7'),
    ('restaurant_hours', '6:30 AM - 11:00 PM'),
    ('bar_hours', '4:00 PM - 1:00 AM')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO bookings (user_id, service_name, quantity, price_per_unit, total_price, booking_date, status, payment_status)
SELECT u.id, 'Double Room', 2, 200000.00, 400000.00, CURRENT_TIMESTAMP, 'confirmed', 'paid'
FROM users u
WHERE u.email = 'guest@magichotel.com'
  AND NOT EXISTS (SELECT 1 FROM bookings LIMIT 1);

INSERT INTO bookings (user_id, service_name, quantity, price_per_unit, total_price, booking_date, status, payment_status)
SELECT u.id, 'Classic Mojito', 2, 25000.00, 50000.00, CURRENT_TIMESTAMP, 'pending', 'unpaid'
FROM users u
WHERE u.email = 'client@magichotel.com'
  AND NOT EXISTS (SELECT 1 FROM bookings WHERE service_name = 'Classic Mojito');

INSERT INTO feedback (user_id, service_category, service_name, rating, comment, created_at, is_approved)
SELECT u.id, 'rooms', 'Double Room', 5, 'The room was clean, comfortable, and worth the stay.', CURRENT_TIMESTAMP, 1
FROM users u
WHERE u.email = 'guest@magichotel.com'
  AND NOT EXISTS (SELECT 1 FROM feedback LIMIT 1);

INSERT INTO feedback (user_id, service_category, service_name, rating, comment, created_at, is_approved)
SELECT u.id, 'restaurant', 'Pan-Seared Salmon', 4, 'The restaurant menu was impressive and the service was quick.', CURRENT_TIMESTAMP, 1
FROM users u
WHERE u.email = 'client@magichotel.com'
  AND NOT EXISTS (SELECT 1 FROM feedback WHERE service_name = 'Pan-Seared Salmon');
