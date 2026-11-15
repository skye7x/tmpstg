<?php

$host = "localhost";
$user = "tmpstg-app";       // do zmiany w zadaniu 3 (osobny użytkownik)
$pass = "yz-.EHLc@]JI[[nt";
$db   = "tmpstg_db";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset('utf8mb4');   // dopiero PO udanym połączeniu
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());     // szczegóły tylko do logu serwera
    exit("Something went wrong, try again later.");  // użytkownik widzi tylko to
}