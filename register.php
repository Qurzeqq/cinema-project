<?php
session_start();
require __DIR__ . '/includes/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($email) || empty($password) || empty($confirm_password)) {
        $message = 'Заполните все поля.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Введите корректный email.';
    } elseif ($password !== $confirm_password) {
        $message = 'Пароли не совпадают.';
    } elseif (strlen($password) < 6) {
        $message = 'Пароль должен содержать минимум 6 символов.';
    } else {
        $checkStmt = $pdo->prepare("SELECT id FROM clients WHERE email = ?");
        $checkStmt->execute([$email]);

        if ($checkStmt->fetch()) {
            $message = 'Пользователь с таким email уже существует.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO clients (email, password, role) VALUES (?, ?, 'user')");
            $stmt->execute([$email, $hashedPassword]);

            header('Location: login.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1>Регистрация</h1>

            <?php if (!empty($message)): ?>
                <p class="message error"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <form method="POST">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Пароль" required>
                <input type="password" name="confirm_password" placeholder="Повторите пароль" required>
                <button type="submit" class="btn">Зарегистрироваться</button>
            </form>

            <p><a href="login.php">Уже есть аккаунт? Войти</a></p>
            <p><a href="index.php">На главную</a></p>
        </div>
    </div>
</body>
</html>