<?php

function encodeMailHeader(string $value): string
{
    $value = trim(str_replace(["\r", "\n"], '', $value));

    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function formatMailPrice(float $value): string
{
    return number_format($value, 2, ',', ' ') . ' ₽';
}

function buildTicketCode(array $bookings, string $clientEmail): string
{
    $bookingIds = array_column($bookings, 'id');
    $hash = strtoupper(substr(hash('sha256', implode('-', $bookingIds) . '|' . $clientEmail), 0, 10));

    return 'CIN-' . $hash;
}

function buildTicketEmailBody(array $bookings, string $clientEmail): string
{
    $ticketCount = count($bookings);
    $ticketPrice = $ticketCount > 0 ? (float) $bookings[0]['price'] : 0;
    $totalPrice = $ticketPrice * $ticketCount;
    $seatList = array_map(
        fn($booking) => 'ряд ' . $booking['seat_row'] . ', место ' . $booking['seat_number'],
        $bookings
    );
    $bookingNumbers = implode(', ', array_column($bookings, 'id'));
    $ticketCode = buildTicketCode($bookings, $clientEmail);

    $lines = [
        'Здравствуйте!',
        '',
        'Покупка билета успешно подтверждена.',
        '',
        'Ваш билет:',
        'Код билета: ' . $ticketCode,
        'Номер бронирования: ' . $bookingNumbers,
        'Фильм: ' . $bookings[0]['title'],
        'Дата: ' . $bookings[0]['session_date'],
        'Время: ' . $bookings[0]['session_time'],
        'Зал: ' . $bookings[0]['hall_name'],
        'Места: ' . implode(', ', $seatList),
        'Количество билетов: ' . $ticketCount,
        'Сумма оплаты: ' . formatMailPrice($totalPrice),
        '',
        'Покажите это письмо на входе в зал.',
        '',
        'Спасибо за покупку!',
    ];

    return implode("\r\n", $lines);
}

function sendTicketEmail(string $to, array $bookings): bool
{
    if (!$bookings || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    global $mailFrom, $mailFromName;

    $configuredFrom = $mailFrom ?? 'no-reply@cinema.local';
    $fromEmail = filter_var($configuredFrom, FILTER_VALIDATE_EMAIL)
        ? $configuredFrom
        : 'no-reply@cinema.local';
    $fromName = $mailFromName ?? 'Cinema';
    $subject = 'Ваш билет в кино';
    $body = buildTicketEmailBody($bookings, $to);
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'From: ' . encodeMailHeader($fromName) . ' <' . $fromEmail . '>',
        'Reply-To: ' . $fromEmail,
        'X-Mailer: PHP/' . phpversion(),
    ];

    if (stripos(PHP_OS, 'WIN') === 0) {
        ini_set('sendmail_from', $fromEmail);
    }

    return @mail($to, encodeMailHeader($subject), $body, implode("\r\n", $headers));
}
