<?php
// Pliki wspólne leżą POZA public_html (nieosiągalne z przeglądarki)
require __DIR__ . '/../../tmpstg_private/bootstrap.php';
require __DIR__ . '/../../tmpstg_private/connect.php';
require __DIR__ . '/../../tmpstg_private/storage.php';

if (!isset($_SESSION['usrid']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: homepage.php');
    exit;
}
csrf_check();

$uid = (int)$_SESSION['usrid'];
$id  = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

if ($id) {
    $stmt = $conn->prepare("SELECT stored_name FROM files WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $uid);
    $stmt->execute();
    $f = $stmt->get_result()->fetch_assoc();
    if ($f) {
        $stmt = $conn->prepare("DELETE FROM files WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $uid);
        $stmt->execute();
        @unlink(storage_path($f['stored_name']));
    }
}
header('Location: homepage.php?deleted=1');
exit;
