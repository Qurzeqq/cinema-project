<?php
session_start();
require __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $bookingId = (int) $_GET['cancel'];

    $stmt = $pdo->prepare("
        DELETE FROM booking
        WHERE id = ? AND client_id = ?
    ");
    $stmt->execute([$bookingId, $userId]);

    header('Location: my_bookings.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        booking.id,
        booking.seat_row,
        booking.seat_number,
        booking.booked_at,
        films.title,
        sessions.session_date,
        sessions.session_time,
        sessions.hall_name
    FROM booking
    JOIN sessions ON booking.session_id = sessions.id
    JOIN films ON sessions.film_id = films.id
    WHERE booking.client_id = ?
    ORDER BY sessions.session_date, sessions.session_time
");
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мои бронирования</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="topbar">
    <h1>Мои бронирования</h1>
    <nav>
        <a href="index.php">На главную</a>
        <a href="logout.php">Выйти</a>
    </nav>
</header>

<main class="container">
    <div class="card">
        <div class="card-body">
            <h2>Ваши билеты</h2>

            <?php if ($bookings): ?>
                <table class="admin-table">
                    <tr>
                        <th>ID</th>
                        <th>Фильм</th>
                        <th>Дата</th>
                        <th>Время</th>
                        <th>Зал</th>
                        <th>Ряд</th>
                        <th>Место</th>
                        <th>Дата брони</th>
                        <th>Действие</th>
                    </tr>

                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?= $booking['id'] ?></td>
                            <td><?= htmlspecialchars($booking['title']) ?></td>
                            <td><?= htmlspecialchars($booking['session_date']) ?></td>
                            <td><?= htmlspecialchars($booking['session_time']) ?></td>
                            <td><?= htmlspecialchars($booking['hall_name']) ?></td>
                            <td><?= htmlspecialchars($booking['seat_row']) ?></td>
                            <td><?= htmlspecialchars($booking['seat_number']) ?></td>
                            <td><?= htmlspecialchars($booking['booked_at']) ?></td>
                            <td>
                                <a 
                                    class="btn-delete"
                                    href="my_bookings.php?cancel=<?= $booking['id'] ?>"
                                    onclick="return confirm('Отменить это бронирование?')"
                                >
                                    Отменить
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>У вас пока нет бронирований.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

</body>
</html>