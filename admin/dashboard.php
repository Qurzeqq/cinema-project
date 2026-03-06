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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="topbar">
        <h1>Админ-панель</h1>
        <nav>
            <a href="../index.php">На сайт</a>
            <a href="films.php">Фильмы</a>
            <a href="sessions.php">Сеансы</a>
            <a href="bookings.php">Бронирования</a>
            <a href="../logout.php">Выйти</a>
        </nav>
    </header>

    <main class="container">
        <div class="cards">
            <div class="card">
                <div class="card-body">
                    <h3>Фильмы</h3>
                    <p><?= $filmsCount ?></p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h3>Сеансы</h3>
                    <p><?= $sessionsCount ?></p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h3>Бронирования</h3>
                    <p><?= $bookingsCount ?></p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h3>Пользователи</h3>
                    <p><?= $clientsCount ?></p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>