<?php
session_start();
require __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Замените ссылку на вашу страницу оплаты.
$paymentLink = 'https://i.pinimg.com/originals/6b/28/96/6b2896aadaaf5848b9136fa0e96c97fd.jpg';
$qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . rawurlencode($paymentLink);
$userId = (int) $_SESSION['user_id'];
$bookingIds = $_SESSION['payment_booking_ids'] ?? [];
$bookingIds = is_array($bookingIds)
    ? array_values(array_filter(array_map('intval', $bookingIds)))
    : [];

$bookings = [];

if ($bookingIds) {
    $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
    $stmt = $pdo->prepare("
        SELECT
            booking.id,
            booking.seat_row,
            booking.seat_number,
            films.title,
            films.price,
            sessions.session_date,
            sessions.session_time,
            sessions.hall_name
        FROM booking
        JOIN sessions ON booking.session_id = sessions.id
        JOIN films ON sessions.film_id = films.id
        WHERE booking.client_id = ? AND booking.id IN ($placeholders)
        ORDER BY booking.seat_row, booking.seat_number
    ");
    $stmt->execute(array_merge([$userId], $bookingIds));
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$ticketCount = count($bookings);
$ticketPrice = $ticketCount > 0 ? (float) $bookings[0]['price'] : 0;
$totalPrice = $ticketPrice * $ticketCount;
$seatList = array_map(
    fn($booking) => 'Ряд ' . $booking['seat_row'] . ', место ' . $booking['seat_number'],
    $bookings
);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оплата бронирования</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="topbar">
    <h1>Оплата бронирования</h1>
    <nav>
        <a href="index.php">На главную</a>
        <a href="my_bookings.php">Мои бронирования</a>
        <a href="logout.php">Выйти</a>
    </nav>
</header>

<main class="container payment-page">
    <?php if ($bookings): ?>
        <section class="payment-card">
            <div class="payment-info">
                <p class="payment-kicker">Бронь создана</p>
                <h2><?= htmlspecialchars($bookings[0]['title']) ?></h2>

                <div class="payment-details">
                    <div><strong>Дата:</strong> <?= htmlspecialchars($bookings[0]['session_date']) ?></div>
                    <div><strong>Время:</strong> <?= htmlspecialchars($bookings[0]['session_time']) ?></div>
                    <div><strong>Зал:</strong> <?= htmlspecialchars($bookings[0]['hall_name']) ?></div>
                    <div><strong>Места:</strong> <?= htmlspecialchars(implode(', ', $seatList)) ?></div>
                    <div><strong>Билетов:</strong> <?= $ticketCount ?></div>
                    <div><strong>К оплате:</strong> <?= htmlspecialchars(number_format($totalPrice, 2, ',', ' ')) ?> ₽</div>
                </div>
            </div>

            <div class="payment-qr-panel">
                <a
                    class="payment-qr-link"
                    href="<?= htmlspecialchars($paymentLink) ?>"
                    target="_blank"
                    rel="noopener"
                >
                    <img
                        class="payment-qr-image"
                        src="<?= htmlspecialchars($qrImageUrl) ?>"
                        alt="QR-код для оплаты"
                    >
                    <strong>Открыть оплату</strong>
                </a>

                <p>Отсканируйте QR-код или откройте оплату по ссылке.</p>

                <a
                    class="btn payment-open-btn"
                    href="<?= htmlspecialchars($paymentLink) ?>"
                    target="_blank"
                    rel="noopener"
                >
                    Перейти к оплате
                </a>
            </div>
        </section>
    <?php else: ?>
        <section class="payment-card payment-card-empty">
            <h2>Нет данных для оплаты</h2>
            <p>Выберите сеанс и места, чтобы перейти к оплате бронирования.</p>
            <a class="btn" href="index.php">Выбрать сеанс</a>
        </section>
    <?php endif; ?>
</main>

</body>
</html>
