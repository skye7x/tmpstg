<?php
require 'bootstrap.php';
require 'connect.php';
require 'storage.php';

if (!isset($_SESSION['usrid'])) {
    header('Location: index.php');
    exit;
}
$uid = (int)$_SESSION['usrid'];
$id  = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
session_write_close();                      // nie blokuj sesji na czas pobierania

function not_found(): void {
    http_response_code(404);
    exit('Not found or expired.');
}
if (!$id) {
    not_found();
}

purge_expired($conn);

// Tylko właściciel i tylko niewygasły plik
$stmt = $conn->prepare(
    "SELECT stored_name, original_name, size FROM files WHERE id = ? AND user_id = ? AND expires_at > NOW()"
);
$stmt->bind_param("ii", $id, $uid);
$stmt->execute();
$f = $stmt->get_result()->fetch_assoc();
if (!$f) {
    not_found();
}
$path = storage_path($f['stored_name']);
if (!is_file($path)) {
    not_found();
}

$ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', $f['original_name']);
while (ob_get_level() > 0) {                // wyczyść bufor, żeby nic nie dopisało się do pliku
    ob_end_clean();
}
header('Content-Type: application/octet-stream');       // zawsze pobieranie, nigdy renderowanie w przeglądarce
header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($f['original_name']));
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
