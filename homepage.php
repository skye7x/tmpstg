<?php
require 'bootstrap.php';
require 'connect.php';
require 'storage.php';

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

// --- Pliki (upload): sprzątanie wygasłych, lista aktywnych, komunikaty ---
purge_expired($conn);
$stmt = $conn->prepare(
    "SELECT id, original_name, size, TIMESTAMPDIFF(SECOND, NOW(), expires_at) AS ttl
     FROM files WHERE user_id = ? AND expires_at > NOW() ORDER BY uploaded_at DESC, id DESC"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$files     = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$usedFiles = count($files);
$usedBytes = (int)array_sum(array_column($files, 'size'));

$errors = [
    'toolarge' => 'Upload too large for the server or over the 5 MB limit.',
    'empty'    => 'Empty files cannot be uploaded.',
    'nofile'   => 'Choose at least one file.',
    'toomany'  => 'Too many files. Limit: ' . UPLOAD_MAX_FILES . ' files.',
    'quota'    => 'Not enough space. Limit: ' . fmt_size(UPLOAD_MAX_TOTAL) . ' in total.',
    'busy'     => 'Another upload is in progress. Try again.',
    'failed'   => 'Upload failed. Try again.',
];
$msg = null;
if (isset($_GET['error']) && is_string($_GET['error']) && isset($errors[$_GET['error']])) {
    $msg = ['error', $errors[$_GET['error']]];
} elseif (isset($_GET['uploaded'])) {
    $msg = ['ok', 'Uploaded ' . (int)$_GET['uploaded'] . ' file(s). They expire in ' . UPLOAD_TTL_MIN . ' minutes.'];
} elseif (isset($_GET['deleted'])) {
    $msg = ['ok', 'File deleted.'];
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

    <div class="dash">
        <h2>Your files</h2>
        <p class="usage">
            <?= $usedFiles ?>/<?= UPLOAD_MAX_FILES ?> files &middot;
            <?= e(fmt_size($usedBytes)) ?> / <?= e(fmt_size(UPLOAD_MAX_TOTAL)) ?> &middot;
            files expire <?= UPLOAD_TTL_MIN ?> min after upload
        </p>
        <?php if ($msg): ?><p class="msg <?= e($msg[0]) ?>"><?= e($msg[1]) ?></p><?php endif; ?>
        <p class="msg error" id="clientMsg" hidden></p>

        <form method="post" action="upload.php" enctype="multipart/form-data" id="uploadForm"
              data-max-files="<?= UPLOAD_MAX_FILES ?>" data-max-total="<?= UPLOAD_MAX_TOTAL ?>"
              data-used-files="<?= $usedFiles ?>" data-used-bytes="<?= $usedBytes ?>">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="file" name="files[]" id="fileInput" multiple required>
            <input type="submit" class="btn" value="Upload">
        </form>

        <?php if ($files): ?>
        <table class="files">
            <thead><tr><th>Name</th><th>Size</th><th>Expires in</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($files as $f): ?>
                <tr>
                    <td class="fname"><a href="download.php?id=<?= (int)$f['id'] ?>"><?= e($f['original_name']) ?></a></td>
                    <td><?= e(fmt_size((int)$f['size'])) ?></td>
                    <td><span class="ttl" data-ttl="<?= max(0, (int)$f['ttl']) ?>"></span></td>
                    <td>
                        <form method="post" action="delete.php">
                            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                            <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                            <button type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="empty">No files yet.</p>
        <?php endif; ?>
    </div>

    <script src="dashboard.js"></script>
</body>
</html>
