<?php
// Dane dostępowe są w config.php (poza repozytorium i poza public_html), nie w kodzie.
$cfg = is_file(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];
$host = $cfg['host'] ?? 'localhost';
$user = $cfg['user'] ?? '';
$pass = $cfg['pass'] ?? '';
$db   = $cfg['db']   ?? '';
unset($cfg);

// błędy mysqli jako wyjątki
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());                       // szczegóły tylko w logu
    exit("Something went wrong, try again later.");    // użytkownik widzi to
}

// Czyści stare wpisy prób logowania i rejestracji (wołane z cleanup.php oraz losowo z login/register,
// więc działa też bez crona).
function purge_attempts(mysqli $conn): void {
    $conn->query("DELETE FROM login_attempts    WHERE attempted_at < (NOW() - INTERVAL 1 DAY)");
    $conn->query("DELETE FROM register_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)");
}
