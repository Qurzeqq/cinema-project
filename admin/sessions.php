<?php
session_start();
require __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_session'])) {
    $filmId = (int)($_POST['film_id'] ?? 0);
    $sessionDate = $_POST['session_date'] ?? '';
    $sessionTime = $_POST['session_time'] ?? '';
    $hallName = trim($_POST['hall_name'] ?? '');

    if ($filmId > 0 && $sessionDate && $sessionTime && $hallName) {
        $stmt = $pdo->prepare("
            INSERT INTO sessions (film_id, session_date, session_time, hall_name)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$filmId, $sessionDate, $sessionTime, $hallName]);

        $message = 'Сеанс добавлен.';
    } else {
        $message = 'Заполните все поля.';
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $sessionId = (int) $_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM sessions WHERE id = ?");
    $stmt->execute([$sessionId]);

    header('Location: sessions.php');
    exit;
}

$films = $pdo->query("SELECT id, title FROM films ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

$sessions = $pdo->query("
    SELECT 
        sessions.id,
        sessions.session_date,
        sessions.session_time,
        sessions.hall_name,
        films.title
    FROM sessions
    JOIN films ON sessions.film_id = films.id
    ORDER BY sessions.session_date, sessions.session_time
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сеансы</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="topbar">
    <h1>Управление сеансами</h1>
    <nav>
        <a href="dashboard.php">Админ панель</a>
        <a href="films.php">Фильмы</a>
        <a href="bookings.php">Бронирования</a>
        <a href="../index.php">На сайт</a>
    </nav>
</header>

<main class="container">

    <?php if (!empty($message)): ?>
        <p class="message success"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <h2>Добавить сеанс</h2>

            <form method="POST" class="admin-form">
                <input type="hidden" name="add_session" value="1">

                <select name="film_id" required>
                    <option value="">Выберите фильм</option>
                    <?php foreach ($films as $film): ?>
                        <option value="<?= $film['id'] ?>">
                            <?= htmlspecialchars($film['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="date" name="session_date" required>
                <input type="time" name="session_time" required>
                <input type="text" name="hall_name" placeholder="Название зала" required>

                <button type="submit" class="btn">Добавить сеанс</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h2>Расписание сеансов</h2>

            <table class="admin-table">
                <tr>
                    <th>ID</th>
                    <th>Фильм</th>
                    <th>Дата</th>
                    <th>Время</th>
                    <th>Зал</th>
                    <th>Действия</th>
                </tr>

                <?php foreach ($sessions as $item): ?>
                    <tr>
                        <td><?= $item['id'] ?></td>
                        <td><?= htmlspecialchars($item['title']) ?></td>
                        <td><?= htmlspecialchars($item['session_date']) ?></td>
                        <td><?= htmlspecialchars($item['session_time']) ?></td>
                        <td><?= htmlspecialchars($item['hall_name']) ?></td>
                        <td>
                            <a class="btn-edit" href="edit_session.php?id=<?= $item['id'] ?>">Редактировать</a>
                            <a class="btn-delete" href="sessions.php?delete=<?= $item['id'] ?>" onclick="return confirm('Удалить сеанс?')">Удалить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

</main>

</body>
</html>