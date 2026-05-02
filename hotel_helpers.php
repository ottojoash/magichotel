<?php

function getCategoryMeta(): array
{
    return [
        'rooms' => [
            'label' => 'Rooms',
            'summary' => 'Comfort-focused accommodation for business and leisure guests.'
        ],
        'restaurant' => [
            'label' => 'Restaurant',
            'summary' => 'Freshly prepared meals from breakfast through dessert.'
        ],
        'bar' => [
            'label' => 'Bar',
            'summary' => 'Cocktails, wines, mocktails, coffee, and evening lounge favourites.'
        ],
        'spa' => [
            'label' => 'Spa & Wellness',
            'summary' => 'Relaxation, beauty, and self-care sessions for every schedule.'
        ],
        'gym' => [
            'label' => 'Gym & Fitness',
            'summary' => 'Fitness access, training support, and wellness sessions.'
        ],
    ];
}

function formatUgx(float $amount): string
{
    return 'UGX ' . number_format($amount, 0);
}

function getHotelSettingDefaults(): array
{
    return [
        'hotel_name' => 'Magic Hotel',
        'tagline' => 'Where elegance meets comfort.',
        'primary_phone' => '+256 700 000 234',
        'secondary_phone' => '+256 774 070 756',
        'whatsapp_number' => '256774070756',
        'email' => 'contact@magichotel.com',
        'reservation_email' => 'reservations@magichotel.com',
        'address' => 'Plot 12, Sunset Avenue, Kampala, Uganda',
        'front_desk_hours' => 'Open 24/7',
        'restaurant_hours' => '6:30 AM - 11:00 PM',
        'bar_hours' => '4:00 PM - 1:00 AM',
    ];
}

function getHotelSettings(mysqli $conn): array
{
    $settings = getHotelSettingDefaults();
    $result = $conn->query("SELECT setting_key, setting_value FROM hotel_settings");

    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        $result->free();
    }

    return $settings;
}

function getServiceCatalog(mysqli $conn, bool $availableOnly = true): array
{
    $filters = $availableOnly ? "WHERE is_available = 1" : "";
    $sql = "
        SELECT id, category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available
        FROM services
        $filters
        ORDER BY
            FIELD(category, 'rooms', 'restaurant', 'bar', 'spa', 'gym'),
            sort_order ASC,
            service_name ASC
    ";

    $result = $conn->query($sql);
    $catalog = [];

    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $category = $row['category'];
            $section = trim((string) $row['menu_section']) !== '' ? $row['menu_section'] : 'General';

            if (!isset($catalog[$category])) {
                $catalog[$category] = [];
            }

            if (!isset($catalog[$category][$section])) {
                $catalog[$category][$section] = [];
            }

            $catalog[$category][$section][] = $row;
        }

        $result->free();
    }

    return $catalog;
}

function getServiceIndexById(mysqli $conn, array $serviceIds, bool $availableOnly = true): array
{
    $serviceIds = array_values(array_unique(array_filter(array_map('intval', $serviceIds), static function ($id) {
        return $id > 0;
    })));

    if (empty($serviceIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
    $availabilitySql = $availableOnly ? " AND is_available = 1" : "";
    $sql = "
        SELECT id, category, menu_section, service_name, description, price, pricing_unit, sort_order, is_available
        FROM services
        WHERE id IN ($placeholders)$availabilitySql
    ";

    $stmt = $conn->prepare($sql);
    $types = str_repeat('i', count($serviceIds));
    $stmt->bind_param($types, ...$serviceIds);
    $stmt->execute();
    $result = $stmt->get_result();

    $services = [];
    while ($row = $result->fetch_assoc()) {
        $services[(int) $row['id']] = $row;
    }

    $stmt->close();

    return $services;
}

function createBookingsFromSelection(mysqli $conn, int $userId, array $serviceIds, array $quantities, string &$errorMessage = ''): int
{
    $normalizedServiceIds = array_values(array_unique(array_filter(array_map('intval', $serviceIds), static function ($id) {
        return $id > 0;
    })));
    $selectedServices = getServiceIndexById($conn, $normalizedServiceIds, true);

    if (empty($selectedServices)) {
        $errorMessage = 'Please select at least one available service.';
        return 0;
    }

    if (count($selectedServices) !== count($normalizedServiceIds)) {
        $errorMessage = 'One or more selected services are no longer available. Please refresh and try again.';
        return 0;
    }

    $insertSql = "
        INSERT INTO bookings (user_id, service_name, quantity, price_per_unit, total_price, status, payment_status)
        VALUES (?, ?, ?, ?, ?, 'pending', 'unpaid')
    ";
    $stmt = $conn->prepare($insertSql);

    if (!$stmt) {
        $errorMessage = 'Unable to prepare the booking request.';
        return 0;
    }

    $createdCount = 0;

    foreach ($selectedServices as $serviceId => $service) {
        $quantity = isset($quantities[$serviceId]) ? max(1, (int) $quantities[$serviceId]) : 1;
        $price = (float) $service['price'];
        $total = $price * $quantity;
        $serviceName = $service['service_name'];

        $stmt->bind_param('isidd', $userId, $serviceName, $quantity, $price, $total);

        if ($stmt->execute()) {
            $createdCount++;
        }
    }

    $stmt->close();

    if ($createdCount === 0 && $errorMessage === '') {
        $errorMessage = 'No bookings were created. Please try again.';
    }

    return $createdCount;
}

function upsertHotelSetting(mysqli $conn, string $key, string $value): bool
{
    $stmt = $conn->prepare("
        INSERT INTO hotel_settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    $stmt->bind_param('ss', $key, $value);
    $success = $stmt->execute();
    $stmt->close();

    return $success;
}

function getCategoryOptions(): array
{
    return array_keys(getCategoryMeta());
}

function getRoleOptions(): array
{
    return [
        'admin',
        'manager',
        'front desk',
        'restaurant supervisor',
        'chef',
        'bartender',
        'housekeeping',
        'spa coordinator',
        'gym instructor',
        'concierge',
        'staff',
    ];
}
