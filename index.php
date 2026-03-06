<?php
session_start();
require __DIR__ . '/includes/db.php';

$stmt = $pdo->query("
    SELECT 
        sessions.id AS session_id,
        sessions.session_date,
        sessions.session_time,
        sessions.hall_name,
        films.title,
        films.description,
        films.duration,
        films.price
    FROM sessions
    JOIN films ON sessions.film_id = films.id
    ORDER BY sessions.session_date, sessions.session_time
");

$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cinema</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="topbar">
    <h1>Cinema</h1>

    <nav>
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="user-email">
                <?= htmlspecialchars($_SESSION['user_email']) ?>
            </span>

            <a href="my_bookings.php">Мои бронирования</a>
            <a href="logout.php">Выйти</a>
        <?php else: ?>
            <a href="login.php">Вход</a>
            <a href="register.php">Регистрация</a>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <a href="admin/dashboard.php">Админ</a>
        <?php endif; ?>
    </nav>
</header>

<main class="container">

    <h2 class="section-title">Предстоящие сеансы</h2>

    <div class="movie-grid">
        <?php if ($sessions): ?>
            <?php foreach ($sessions as $session): ?>
                <div class="movie-card">
                    <div class="movie-header">
                        <h3><?= htmlspecialchars($session['title']) ?></h3>
                    </div>

                    <div class="movie-body">
                        <p class="movie-description">
                            <?= htmlspecialchars($session['description']) ?>
                        </p>

                        <div class="movie-info">
                            <span>🎬 <?= htmlspecialchars($session['duration']) ?> мин</span>
                            <span>📅 <?= htmlspecialchars($session['session_date']) ?></span>
                            <span>⏰ <?= htmlspecialchars($session['session_time']) ?></span>
                            <span>🏛 <?= htmlspecialchars($session['hall_name']) ?></span>
                            <span>🎟 <?= htmlspecialchars($session['price']) ?> ₽</span>
                        </div>
                    </div>

                    <div class="movie-footer">
                        <a class="btn-book" href="booking.php?session_id=<?= $session['session_id'] ?>">
                            Забронировать
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <h3>Сеансов пока нет</h3>
                    <p>Добавьте фильмы и сеансы через админ-панель.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

</main>

</body>
</html>