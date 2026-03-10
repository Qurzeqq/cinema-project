<?php
session_start();
require __DIR__ . '/includes/db.php';

$search = trim($_GET['search'] ?? '');

$sql = "
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
    WHERE (
        sessions.session_date > CURDATE()
        OR (sessions.session_date = CURDATE() AND sessions.session_time >= CURTIME())
    )
";

$params = [];

if ($search !== '') {
    $sql .= " AND (films.title LIKE ? OR films.description LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$sql .= " ORDER BY sessions.session_date ASC, sessions.session_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
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

<header class="site-header">
    <div class="header-inner">
        <a href="index.php" class="brand">
            <span class="brand-icon">🎬</span>
            <span class="brand-text">Cinema</span>
        </a>

        <nav class="main-nav">
            <a href="index.php">Главная</a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="my_bookings.php">Мои бронирования</a>

                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin/dashboard.php">Админка</a>
                <?php endif; ?>

                <span class="user-badge">
                    <?= htmlspecialchars($_SESSION['user_email']) ?>
                </span>

                <a href="logout.php" class="nav-btn">Выйти</a>
            <?php else: ?>
                <a href="login.php">Вход</a>
                <a href="register.php" class="nav-btn">Регистрация</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container page-top-spacing">

    <section class="hero-banner">
        <div class="hero-banner__content">
            <h1>Бронирование билетов в кинотеатр</h1>
            <p>
                Выберите фильм, найдите удобный сеанс и забронируйте лучшие места в зале.
            </p>
        </div>
    </section>

    <section class="search-section">
        <form method="GET" class="search-form">
            <input
                type="text"
                name="search"
                class="search-input"
                placeholder="Поиск фильма по названию или описанию..."
                value="<?= htmlspecialchars($search) ?>"
            >
            <button type="submit" class="btn search-btn">Найти</button>

            <?php if ($search !== ''): ?>
                <a href="index.php" class="clear-search-btn">Сбросить</a>
            <?php endif; ?>
        </form>
    </section>

    <section class="section-header">
        <h2 class="section-title">Актуальные сеансы</h2>

        <?php if ($search !== ''): ?>
            <p class="search-result-text">
                Результаты поиска по запросу:
                <strong><?= htmlspecialchars($search) ?></strong>
            </p>
        <?php endif; ?>
    </section>

    <div class="movie-grid">
        <?php if ($sessions): ?>
            <?php foreach ($sessions as $session): ?>
                <article class="movie-card">
                    <div class="movie-header">
                        <h3><?= htmlspecialchars($session['title']) ?></h3>
                    </div>

                    <div class="movie-body">
                        <p class="movie-description">
                            <?= htmlspecialchars($session['description']) ?>
                        </p>

                        <div class="movie-info">
                            <span>🎬 Длительность: <?= htmlspecialchars($session['duration']) ?> мин</span>
                            <span>📅 Дата: <?= htmlspecialchars($session['session_date']) ?></span>
                            <span>⏰ Время: <?= htmlspecialchars($session['session_time']) ?></span>
                            <span>🏛 Зал: <?= htmlspecialchars($session['hall_name']) ?></span>
                            <span>🎟 Цена: <?= htmlspecialchars($session['price']) ?> ₽</span>
                        </div>
                    </div>

                    <div class="movie-footer">
                        <a class="btn-book" href="booking.php?session_id=<?= $session['session_id'] ?>">
                            Забронировать
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <h3>Сеансы не найдены</h3>
                <p>
                    <?php if ($search !== ''): ?>
                        По вашему запросу ничего не найдено. Попробуйте изменить текст поиска.
                    <?php else: ?>
                        В системе пока нет доступных будущих сеансов.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

</main>

</body>
</html>