<?php
session_start();
require __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$bookings = $pdo->query("
    SELECT 
        booking.id,
        booking.seat_row,
        booking.seat_number,
        booking.booked_at,
        clients.email,
        films.title,
        sessions.session_date,
        sessions.session_time,
        sessions.hall_name
    FROM booking
    JOIN clients ON booking.client_id = clients.id
    JOIN sessions ON booking.session_id = sessions.id
    JOIN films ON sessions.film_id = films.id
    ORDER BY booking.booked_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Бронирования</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="topbar">
        <h1>Текущие бронирования</h1>
        <nav>
            <a href="dashboard.php">Админ-панель</a>
            <a href="films.php">Фильмы</a>
            <a href="sessions.php">Сеансы</a>
            <a href="../index.php">На сайт</a>
        </nav>
    </header>

    <main class="container">
        <div class="card">
            <div class="card-body">
                <table class="admin-table">
                    <tr>
                        <th>ID</th>
                        <th>Пользователь</th>
                        <th>Фильм</th>
                        <th>Дата</th>
                        <th>Время</th>
                        <th>Зал</th>
                        <th>Ряд</th>
                        <th>Место</th>
                        <th>Дата брони</th>
                    </tr>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?= $booking['id'] ?></td>
                            <td><?= htmlspecialchars($booking['email']) ?></td>
                            <td><?= htmlspecialchars($booking['title']) ?></td>
                            <td><?= htmlspecialchars($booking['session_date']) ?></td>
                            <td><?= htmlspecialchars($booking['session_time']) ?></td>
                            <td><?= htmlspecialchars($booking['hall_name']) ?></td>
                            <td><?= htmlspecialchars($booking['seat_row']) ?></td>
                            <td><?= htmlspecialchars($booking['seat_number']) ?></td>
                            <td><?= htmlspecialchars($booking['booked_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </main>
</body>
</html>