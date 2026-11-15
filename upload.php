<?php
require 'bootstrap.php';
require 'connect.php';
require 'storage.php';

if (!isset($_SESSION['usrid'])) {
    header('Location: index.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: homepage.php');
    exit;
}

function upload_fail(string $code): void {
    header('Location: homepage.php?error=' . $code);
    exit;
}

// Gdy żądanie przekracza post_max_size, PHP zeruje $_POST i $_FILES (inaczej dostalibyśmy 403 z CSRF)
if (empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    upload_fail('toolarge');
}
csrf_check();

$uid = (int)$_SESSION['usrid'];
purge_expired($conn);

// --- Zebranie i wstępna walidacja plików z formularza ---
$incoming = [];
if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
    foreach ($_FILES['files']['name'] as $i => $name) {
        $err = $_FILES['files']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            upload_fail('toolarge');
        }
        if ($err !== UPLOAD_ERR_OK) {
            upload_fail('failed');
        }
        $tmp = (string)$_FILES['files']['tmp_name'][$i];
        if (!is_uploaded_file($tmp)) {
            upload_fail('failed');
        }
        $size = filesize($tmp);               // własny pomiar, nie ufamy polu size z żądania
        if ($size === false || $size === 0) {
            upload_fail('empty');
        }
        $incoming[] = ['tmp' => $tmp, 'name' => clean_filename((string)$name), 'size' => $size];
    }
}
if (!$incoming) {
    upload_fail('nofile');
}
if (count($incoming) > UPLOAD_MAX_FILES) {
    upload_fail('toomany');
}
$newBytes = array_sum(array_column($incoming, 'size'));

// --- Sprawdzenie limitów + zapis. Blokada per użytkownik, żeby równoległe uploady nie ominęły limitów ---
$lock  = 'tmpstg_upload_' . $uid;
$got   = $conn->query("SELECT GET_LOCK('$lock', 5) AS l")->fetch_assoc();
if ((int)$got['l'] !== 1) {
    upload_fail('busy');
}

$error = null;
$saved = [];
try {
    [$count, $bytes] = user_usage($conn, $uid);

    if ($count + count($incoming) > UPLOAD_MAX_FILES) {
        $error = 'toomany';
    } elseif ($bytes + $newBytes > UPLOAD_MAX_TOTAL) {
        $error = 'quota';
    } else {
        storage_ensure_dir();
        $conn->begin_transaction();
        $stmt = $conn->prepare(
            "INSERT INTO files (user_id, stored_name, original_name, size, expires_at)
             VALUES (?, ?, ?, ?, NOW() + INTERVAL " . (int)UPLOAD_TTL_MIN . " MINUTE)"
        );
        foreach ($incoming as $f) {
            $stored = bin2hex(random_bytes(16));
            if (!move_uploaded_file($f['tmp'], storage_path($stored))) {
                throw new RuntimeException('move_uploaded_file failed');
            }
            $saved[] = $stored;
            $stmt->bind_param("issi", $uid, $stored, $f['name'], $f['size']);
            $stmt->execute();
        }
        $conn->commit();
    }
} catch (Throwable $t) {
    error_log($t->getMessage());
    try { $conn->rollback(); } catch (Throwable $ignored) {}
    foreach ($saved as $s) {
        @unlink(storage_path($s));
    }
    $error = 'failed';
} finally {
    $conn->query("DO RELEASE_LOCK('$lock')");
}

if ($error !== null) {
    upload_fail($error);
}
header('Location: homepage.php?uploaded=' . count($incoming));
exit;
