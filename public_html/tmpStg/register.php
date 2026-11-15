<?php
// Pliki wspólne leżą POZA public_html (nieosiągalne z przeglądarki)
require __DIR__ . '/../../tmpstg_private/bootstrap.php';
require __DIR__ . '/../../tmpstg_private/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['signUp'])) {
    header('Location: index.php');
    exit;
}
csrf_check();

// --- Limit rejestracji: max 5 prób na IP w ciągu godziny (ochrona przed botami zapychającymi dysk kontami) ---
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (random_int(1, 50) === 1) {
    purge_attempts($conn);               // porządki bez crona
}
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM register_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL 1 HOUR)");
$stmt->bind_param("s", $ip);
$stmt->execute();
if ((int)$stmt->get_result()->fetch_assoc()['c'] >= 5) {
    header('Location: index.php?error=ratelimit#signup');
    exit;
}
$stmt = $conn->prepare("INSERT INTO register_attempts (ip) VALUES (?)");
$stmt->bind_param("s", $ip);
$stmt->execute();

$firstName = trim((string)($_POST['fName'] ?? ''));
$lastName  = trim((string)($_POST['lName'] ?? ''));
$email     = strtolower(trim((string)($_POST['email'] ?? '')));
$password  = (string)($_POST['password'] ?? '');   // surowe, bez trim

// Walidacja PRZED bazą i PRZED password_hash
if ($firstName === '' || mb_strlen($firstName) > 50
    || $lastName === '' || mb_strlen($lastName) > 50
    || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254
    || strlen($password) < 10 || strlen($password) > 128) {
    header('Location: index.php?error=invalid#signup');
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $firstName, $lastName, $email, $hash);
    $stmt->execute();
} catch (mysqli_sql_exception $e) {
    // 1062 = duplikat emaila (UNIQUE). Udajemy, że wszystko OK (brak user enumeration).
    if ($e->getCode() !== 1062) {
        error_log($e->getMessage());
        http_response_code(500);
        exit("Something went wrong, try again later.");
    }
}

// Ta sama odpowiedź niezależnie od tego, czy email był wolny
header('Location: index.php?registered=1');
exit;
