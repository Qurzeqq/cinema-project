<?php
session_start();
require __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$filmsCount = $pdo->query("SELECT COUNT(*) FROM films")->fetchColumn();
$sessionsCount = $pdo->query("SELECT COUNT(*) FROM sessions")->fetchColumn();
$bookingsCount = $pdo->query("SELECT COUNT(*) FROM booking")->fetchColumn();
$clientsCount = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();

$latestBookingsStmt = $pdo->query("
    SELECT 
        booking.id,
        booking.seat_row,
        booking.seat_number,
        booking.booked_at,
        clients.email,
        films.title,
        sessions.session_date,
        sessions.session_time
    FROM booking
    JOIN clients ON booking.client_id = clients.id
    JOIN sessions ON booking.session_id = sessions.id
    JOIN films ON sessions.film_id = films.id
    ORDER BY booking.booked_at DESC
    LIMIT 5
");
$latestBookings = $latestBookingsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="site-header admin-header">
    <div class="header-inner">
        <a href="../index.php" class="brand">
            <span class="brand-icon">🎬</span>
            <span class="brand-text">Cinema Admin</span>
        </a>

        <nav class="main-nav">
            <a href="dashboard.php">Главная</a>
            <a href="films.php">Фильмы</a>
            <a href="sessions.php">Сеансы</a>
            <a href="bookings.php">Бронирования</a>
            <a href="../index.php">На сайт</a>
            <a href="../logout.php" class="nav-btn">Выйти</a>
        </nav>
    </div>
</header>

<main class="container page-top-spacing">

    <section class="admin-hero">
        <div class="admin-hero-box">
            <div>
                <h1>Панель администратора</h1>
                <p>Управляйте фильмами, сеансами и бронированиями пользователей.</p>
            </div>
            <div class="admin-hero-email">
                <?= htmlspecialchars($_SESSION['user_email']) ?>
            </div>
        </div>
    </section>

    <section class="stats-grid">
        <article class="stat-card">
            <div class="stat-icon">🎞</div>
            <div class="stat-content">
                <p class="stat-label">Фильмы</p>
                <h2><?= $filmsCount ?></h2>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-icon">🕒</div>
            <div class="stat-content">
                <p class="stat-label">Сеансы</p>
                <h2><?= $sessionsCount ?></h2>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-icon">🎟</div>
            <div class="stat-content">
                <p class="stat-label">Бронирования</p>
                <h2><?= $bookingsCount ?></h2>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <p class="stat-label">Пользователи</p>
                <h2><?= $clientsCount ?></h2>
            </div>
        </article>
    </section>

    <section class="admin-actions-grid">
        <a href="films.php" class="admin-action-card">
            <h3>Управление фильмами</h3>
            <p>Добавление, редактирование и удаление фильмов.</p>
        </a>

        <a href="sessions.php" class="admin-action-card">
            <h3>Управление сеансами</h3>
            <p>Создание расписания сеансов и изменение данных.</p>
        </a>

        <a href="bookings.php" class="admin-action-card">
            <h3>Просмотр бронирований</h3>
            <p>Контроль занятых мест и списка бронирований.</p>
        </a>
    </section>

    <section class="card">
        <div class="card-body">
            <div class="section-header">
                <h2 class="section-title">Последние бронирования</h2>
            </div>

            <?php if ($latestBookings): ?>
                <table class="admin-table">
                    <tr>
                        <th>ID</th>
                        <th>Пользователь</th>
                        <th>Фильм</th>
                        <th>Дата</th>
                        <th>Время</th>
                        <th>Место</th>
                        <th>Дата брони</th>
                    </tr>

                    <?php foreach ($latestBookings as $booking): ?>
                        <tr>
                            <td><?= $booking['id'] ?></td>
                            <td><?= htmlspecialchars($booking['email']) ?></td>
                            <td><?= htmlspecialchars($booking['title']) ?></td>
                            <td><?= htmlspecialchars($booking['session_date']) ?></td>
                            <td><?= htmlspecialchars($booking['session_time']) ?></td>
                            <td>Ряд <?= htmlspecialchars($booking['seat_row']) ?>, место <?= htmlspecialchars($booking['seat_number']) ?></td>
                            <td><?= htmlspecialchars($booking['booked_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>Пока нет бронирований.</p>
            <?php endif; ?>
        </div>
    </section>

</main>

</body>
</html>