<?php
session_start();
require __DIR__ . '/includes/db.php';

$stmt = $pdo->query("
    SELECT 
        sessions.id AS session_id,
        sessions.session_date,
        sessions.session_time,
        films.title,
        films.description,
        films.duration,
        films.price,
        films.image
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
    <title>Кинотеатр</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="topbar">
        <h1>Кинотеатр</h1>
        <nav>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php">Выйти</a>
            <?php else: ?>
                <a href="login.php">Вход</a>
                <a href="register.php">Регистрация</a>
            <?php endif; ?>
            <a href="admin/dashboard.php">Админ</a>
        </nav>
    </header>

    <main class="container">
        <h2>Предстоящие сеансы</h2>

        <div class="cards">
            <?php foreach ($sessions as $session): ?>
                <div class="card">
                    <div class="card-body">
                        <h3><?= htmlspecialchars($session['title']) ?></h3>
                        <p><?= htmlspecialchars($session['description']) ?></p>
                        <p><strong>Дата:</strong> <?= htmlspecialchars($session['session_date']) ?></p>
                        <p><strong>Время:</strong> <?= htmlspecialchars($session['session_time']) ?></p>
                        <p><strong>Длительность:</strong> <?= htmlspecialchars($session['duration']) ?> мин.</p>
                        <p><strong>Цена:</strong> <?= htmlspecialchars($session['price']) ?> ₽</p>
                        <a class="btn" href="booking.php?session_id=<?= $session['session_id'] ?>">Забронировать</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>