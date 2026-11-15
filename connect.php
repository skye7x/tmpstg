<?php
$host = "localhost";
$user = "tmpstg-app";
$pass = "/J)Zo/5GtwYjso(V";
$db   = "tmpstg_db";

// błędy mysqli jako wyjątki
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());                       // szczegóły tylko w logu
    exit("Something went wrong, try again later.");    // użytkownik widzi to
}
