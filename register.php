<?php
require 'bootstrap.php';
require 'connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['signUp'])) {
    header('Location: index.php');
    exit;
}
csrf_check();

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
