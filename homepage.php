<?php
require 'bootstrap.php';
require 'connect.php';

// Tylko dla zalogowanych
if (!isset($_SESSION['usrid'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_SESSION['usrid'];
$stmt = $conn->prepare("SELECT firstname, lastname FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user === null) {            // konto usunięte, a sesja jeszcze żyje
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>tmpStg</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="home">
        <p class="hello">Hello <?= e($user['firstname'] . ' ' . $user['lastname']) ?> :)</p>
        <form method="post" action="logout.php">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <button type="submit">Logout</button>
        </form>
    </div>
</body>
</html>
