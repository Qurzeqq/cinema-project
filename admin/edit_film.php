<?php
session_start();
require __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Фильм не найден.');
}

$filmId = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM films WHERE id = ?");
$stmt->execute([$filmId]);
$film = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$film) {
    die('Фильм не найден.');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration = (int) ($_POST['duration'] ?? 0);
    $price = (float) ($_POST['price'] ?? 0);

    if ($title && $description && $duration > 0 && $price > 0) {
        $updateStmt = $pdo->prepare("
            UPDATE films
            SET title = ?, description = ?, duration = ?, price = ?
            WHERE id = ?
        ");
        $updateStmt->execute([$title, $description, $duration, $price, $filmId]);

        header('Location: films.php');
        exit;
    } else {
        $message = 'Заполните все поля корректно.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать фильм</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="topbar">
    <h1>Редактирование фильма</h1>
    <nav>
        <a href="films.php">Назад к фильмам</a>
        <a href="dashboard.php">Админ панель</a>
        <a href="../index.php">На сайт</a>
    </nav>
</header>

<main class="container">
    <div class="card">
        <div class="card-body">
            <h2>Изменить фильм</h2>

            <?php if (!empty($message)): ?>
                <p class="message error"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <form method="POST" class="admin-form">
                <input 
                    type="text" 
                    name="title" 
                    value="<?= htmlspecialchars($film['title']) ?>" 
                    placeholder="Название фильма"
                    required
                >

                <textarea 
                    name="description" 
                    placeholder="Описание фильма"
                    required
                ><?= htmlspecialchars($film['description']) ?></textarea>

                <input 
                    type="number" 
                    name="duration" 
                    value="<?= htmlspecialchars($film['duration']) ?>" 
                    placeholder="Длительность (минуты)"
                    required
                >

                <input 
                    type="number" 
                    step="0.01" 
                    name="price" 
                    value="<?= htmlspecialchars($film['price']) ?>" 
                    placeholder="Цена билета"
                    required
                >

                <button type="submit" class="btn">Сохранить изменения</button>
            </form>
        </div>
    </div>
</main>

</body>
</html>