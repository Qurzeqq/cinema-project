<?php
session_start();
require __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_film'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration = (int)($_POST['duration'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);

    if ($title && $description && $duration > 0 && $price > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO films (title, description, duration, price)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$title, $description, $duration, $price]);
        $message = 'Фильм добавлен.';
    } else {
        $message = 'Заполните все поля корректно.';
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $filmId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM films WHERE id = ?");
    $stmt->execute([$filmId]);
    header('Location: films.php');
    exit;
}

$films = $pdo->query("SELECT * FROM films ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Фильмы</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="topbar">
        <h1>Управление фильмами</h1>
        <nav>
            <a href="dashboard.php">Админ-панель</a>
            <a href="sessions.php">Сеансы</a>
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
                <h2>Добавить фильм</h2>
                <form method="POST" class="admin-form">
                    <input type="hidden" name="add_film" value="1">
                    <input type="text" name="title" placeholder="Название фильма" required>
                    <textarea name="description" placeholder="Описание" required></textarea>
                    <input type="number" name="duration" placeholder="Длительность в минутах" required>
                    <input type="number" step="0.01" name="price" placeholder="Цена билета" required>
                    <button type="submit" class="btn">Добавить фильм</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h2>Список фильмов</h2>
                <table class="admin-table">
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Длительность</th>
                        <th>Цена</th>
                        <th>Действие</th>
                    </tr>
                    <?php foreach ($films as $film): ?>
                        <tr>
                            <td><?= $film['id'] ?></td>
                            <td><?= htmlspecialchars($film['title']) ?></td>
                            <td><?= htmlspecialchars($film['duration']) ?> мин</td>
                            <td><?= htmlspecialchars($film['price']) ?> ₽</td>
                            <td>
                                <a class="btn-delete" href="films.php?delete=<?= $film['id'] ?>" onclick="return confirm('Удалить фильм?')">
                                    Удалить
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </main>
</body>
</html>