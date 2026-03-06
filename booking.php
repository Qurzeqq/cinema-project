<?php
session_start();
require __DIR__ . '/includes/db.php';

if (!isset($_GET['session_id']) || !is_numeric($_GET['session_id'])) {
    die('Сеанс не найден.');
}

$sessionId = (int) $_GET['session_id'];

$stmt = $pdo->prepare("
    SELECT 
        sessions.id,
        sessions.session_date,
        sessions.session_time,
        sessions.hall_name,
        films.title,
        films.description,
        films.price
    FROM sessions
    JOIN films ON sessions.film_id = films.id
    WHERE sessions.id = ?
");
$stmt->execute([$sessionId]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    die('Сеанс не найден.');
}

$rows = 5;
$cols = 8;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        $message = 'Для бронирования нужно войти в аккаунт.';
    } elseif (!empty($_POST['seats']) && is_array($_POST['seats'])) {
        $selectedSeats = $_POST['seats'];

        try {
            $pdo->beginTransaction();

            $checkStmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM booking
                WHERE session_id = ? AND seat_row = ? AND seat_number = ?
            ");

            $insertStmt = $pdo->prepare("
                INSERT INTO booking (client_id, session_id, seat_row, seat_number)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($selectedSeats as $seat) {
                [$seatRow, $seatNumber] = explode('-', $seat);

                $seatRow = (int) $seatRow;
                $seatNumber = (int) $seatNumber;

                $checkStmt->execute([$sessionId, $seatRow, $seatNumber]);
                $exists = $checkStmt->fetchColumn();

                if ($exists) {
                    throw new Exception("Место {$seatRow}-{$seatNumber} уже занято.");
                }

                $insertStmt->execute([
                    $_SESSION['user_id'],
                    $sessionId,
                    $seatRow,
                    $seatNumber
                ]);
            }

            $pdo->commit();
            $message = 'Бронирование успешно выполнено.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Ошибка бронирования: ' . $e->getMessage();
        }
    } else {
        $message = 'Выберите хотя бы одно место.';
    }
}

$bookedStmt = $pdo->prepare("
    SELECT seat_row, seat_number
    FROM booking
    WHERE session_id = ?
");
$bookedStmt->execute([$sessionId]);
$bookedSeatsRaw = $bookedStmt->fetchAll(PDO::FETCH_ASSOC);

$bookedSeats = [];
foreach ($bookedSeatsRaw as $seat) {
    $bookedSeats[] = $seat['seat_row'] . '-' . $seat['seat_number'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бронирование</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="topbar">
    <h1>Бронирование мест</h1>
    <nav>
        <a href="index.php">На главную</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="my_bookings.php">Мои бронирования</a>
            <a href="logout.php">Выйти</a>
        <?php else: ?>
            <a href="login.php">Вход</a>
        <?php endif; ?>
    </nav>
</header>

<main class="container booking-page">

    <div class="card booking-session-card">
        <div class="card-body">
            <h2><?= htmlspecialchars($session['title']) ?></h2>
            <p class="movie-description"><?= htmlspecialchars($session['description']) ?></p>

            <div class="booking-meta">
                <div><strong>Дата:</strong> <?= htmlspecialchars($session['session_date']) ?></div>
                <div><strong>Время:</strong> <?= htmlspecialchars($session['session_time']) ?></div>
                <div><strong>Зал:</strong> <?= htmlspecialchars($session['hall_name']) ?></div>
                <div><strong>Цена билета:</strong> <?= htmlspecialchars($session['price']) ?> ₽</div>
            </div>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <p class="message <?= str_contains($message, 'успешно') ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <div class="booking-layout">
        <div class="booking-legend">
            <div class="legend-item">
                <span class="seat free"></span>
                <span>Свободно</span>
            </div>
            <div class="legend-item">
                <span class="seat booked"></span>
                <span>Занято</span>
            </div>
            <div class="legend-item">
                <span class="seat selected demo"></span>
                <span>Выбрано</span>
            </div>
        </div>

        <div class="hall-wrapper">
            <div class="screen">Экран</div>

            <form method="POST" class="booking-form">
                <div class="hall-grid">
                    <?php for ($r = 1; $r <= $rows; $r++): ?>
                        <div class="hall-grid-row">
                            <div class="row-label">Ряд <?= $r ?></div>

                            <div class="row-seats">
                                <?php for ($c = 1; $c <= $cols; $c++): ?>
                                    <?php
                                        $seatKey = $r . '-' . $c;
                                        $isBooked = in_array($seatKey, $bookedSeats, true);
                                    ?>
                                    <label class="seat-label">
                                        <input
                                            type="checkbox"
                                            name="seats[]"
                                            value="<?= $seatKey ?>"
                                            class="seat-input"
                                            <?= $isBooked ? 'disabled' : '' ?>
                                        >
                                        <span class="seat <?= $isBooked ? 'booked' : 'free' ?>">
                                            <?= $c ?>
                                        </span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <button type="submit" class="btn booking-submit-btn">
                    Подтвердить бронирование
                </button>
            </form>
        </div>
    </div>

</main>

<script>
document.querySelectorAll('.seat-input').forEach(input => {
    input.addEventListener('change', function () {
        const seat = this.nextElementSibling;

        if (this.checked) {
            seat.classList.remove('free');
            seat.classList.add('selected');
        } else {
            seat.classList.remove('selected');
            seat.classList.add('free');
        }
    });
});
</script>

</body>
</html>