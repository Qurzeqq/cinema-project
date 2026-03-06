<?php
session_start();
require __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Сеанс не найден.');
}

$sessionId = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM sessions WHERE id = ?");
$stmt->execute([$sessionId]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    die('Сеанс не найден.');
}

$films = $pdo->query("SELECT id, title FROM films ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filmId = (int) ($_POST['film_id'] ?? 0);
    $sessionDate = $_POST['session_date'] ?? '';
    $sessionTime = $_POST['session_time'] ?? '';
    $hallName = trim($_POST['hall_name'] ?? '');

    if ($filmId > 0 && $sessionDate && $sessionTime && $hallName) {
        $updateStmt = $pdo->prepare("
            UPDATE sessions
            SET film_id = ?, session_date = ?, session_time = ?, hall_name = ?
            WHERE id = ?
        ");
        $updateStmt->execute([$filmId, $sessionDate, $sessionTime, $hallName, $sessionId]);

        header('Location: sessions.php');
        exit;
    } else {
        $message = 'Заполните все поля.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать сеанс</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="topbar">
    <h1>Редактирование сеанса</h1>
    <nav>
        <a href="sessions.php">Назад к сеансам</a>
        <a href="dashboard.php">Админ панель</a>
        <a href="../index.php">На сайт</a>
    </nav>
</header>

<main class="container">
    <div class="card">
        <div class="card-body">
            <h2>Изменить сеанс</h2>

            <?php if (!empty($message)): ?>
                <p class="message error"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <form method="POST" class="admin-form">
                <select name="film_id" required>
                    <?php foreach ($films as $film): ?>
                        <option value="<?= $film['id'] ?>" <?= $film['id'] == $session['film_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($film['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input 
                    type="date" 
                    name="session_date" 
                    value="<?= htmlspecialchars($session['session_date']) ?>" 
                    required
                >

                <input 
                    type="time" 
                    name="session_time" 
                    value="<?= htmlspecialchars($session['session_time']) ?>" 
                    required
                >

                <input 
                    type="text" 
                    name="hall_name" 
                    value="<?= htmlspecialchars($session['hall_name']) ?>" 
                    placeholder="Название зала"
                    required
                >

                <button type="submit" class="btn">Сохранить изменения</button>
            </form>
        </div>
    </div>
</main>

</body>
</html>