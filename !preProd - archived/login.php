<?php
require 'bootstrap.php';
require 'connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['signIn'])) {
    header('Location: index.php');
    exit;
}
csrf_check();

$email    = substr(strtolower(trim((string)($_POST['email'] ?? ''))), 0, 254);
$password = (string)($_POST['password'] ?? '');   // surowe; password_verify samo sprawdzi
$ip       = $_SERVER['REMOTE_ADDR'] ?? '';

// --- Limit prób: 5 na email lub 20 na IP w ciągu 15 minut ---
$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(email = ?), 0) AS e, COALESCE(SUM(ip = ?), 0) AS i
     FROM login_attempts
     WHERE attempted_at > (NOW() - INTERVAL 15 MINUTE)"
);
$stmt->bind_param("ss", $email, $ip);
$stmt->execute();
$c = $stmt->get_result()->fetch_assoc();
if ((int)$c['e'] >= 5 || (int)$c['i'] >= 20) {
    header('Location: index.php?error=locked');
    exit;
}

// --- Szukanie użytkownika po emailu ---
$stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

// Gdy nie ma użytkownika, i tak wykonujemy podobnie kosztowną operację (wyrównanie czasu)
$hash = $row['password'] ?? password_hash('dummy-password', PASSWORD_DEFAULT);

if ($row && password_verify($password, $hash)) {
    session_regenerate_id(true);                   // ochrona przed session fixation
    $_SESSION['usrid']         = (int)$row['id'];
    $_SESSION['created_at']    = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf']          = bin2hex(random_bytes(32));   // nowy token po zalogowaniu

    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    header('Location: homepage.php');
    exit;
}

// Porażka: zapis próby i JEDEN wspólny komunikat
$stmt = $conn->prepare("INSERT INTO login_attempts (email, ip) VALUES (?, ?)");
$stmt->bind_param("ss", $email, $ip);
$stmt->execute();

header('Location: index.php?error=login');
exit;
