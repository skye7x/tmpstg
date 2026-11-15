<?php
// Konfiguracja i funkcje uploadu plików (dashboard).

const UPLOAD_MAX_FILES = 20;                 // max plików na użytkownika (aktywnych naraz)
const UPLOAD_MAX_TOTAL = 5 * 1024 * 1024;    // max łączny rozmiar: 5 MB
const UPLOAD_TTL_MIN   = 5;                  // plik wygasa po 5 minutach od wgrania

// Katalog na pliki: media/ w tmpstg_private, czyli POZA public_html (przeglądarka w ogóle go nie widzi).
// Dodatkowo (pas i szelki) media/.htaccess blokuje dostęp, gdyby katalog kiedyś znalazł się w katalogu WWW,
// pliki wychodzą wyłącznie przez download.php (logowanie + sprawdzenie właściciela).
define('STORAGE_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'media');

const MEDIA_HTACCESS = "# Zakaz bezposredniego dostepu do plikow\n"
    . "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
    . "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n"
    . "Options -Indexes -ExecCGI\n"
    . "RemoveHandler .php .phtml .php3 .php4 .php5 .phar\n"
    . "<IfModule mod_php.c>\n    php_flag engine off\n</IfModule>\n";

function storage_ensure_dir(): void {
    if (!is_dir(STORAGE_DIR) && !mkdir(STORAGE_DIR, 0750, true) && !is_dir(STORAGE_DIR)) {
        throw new RuntimeException('Cannot create storage dir');
    }
    // Zawsze pilnuj, żeby zabezpieczenie istniało (np. po usunięciu/odtworzeniu katalogu)
    $ht = STORAGE_DIR . DIRECTORY_SEPARATOR . '.htaccess';
    if (!is_file($ht) && file_put_contents($ht, MEDIA_HTACCESS) === false) {
        throw new RuntimeException('Cannot write media/.htaccess');
    }
}

// Nazwa na dysku to zawsze 32 znaki hex (nigdy nazwa od użytkownika)
function storage_path(string $stored): string {
    if (!preg_match('/^[a-f0-9]{32}$/', $stored)) {
        throw new InvalidArgumentException('Bad stored name');
    }
    return STORAGE_DIR . DIRECTORY_SEPARATOR . $stored;
}

// Usuwa wygasłe pliki (z dysku i z bazy). Wywoływane przy każdym wejściu na dashboard/upload/download,
// a dodatkowo można z crona: php cleanup.php
function purge_expired(mysqli $conn): void {
    for ($round = 0; $round < 20; $round++) {
        $res  = $conn->query("SELECT id, stored_name FROM files WHERE expires_at <= NOW() LIMIT 500");
        $ids  = [];
        $rows = 0;
        while ($r = $res->fetch_assoc()) {
            $rows++;
            $p = storage_path($r['stored_name']);
            if (!is_file($p) || @unlink($p)) {
                $ids[] = (int)$r['id'];
            }
        }
        if ($ids) {
            $conn->query("DELETE FROM files WHERE id IN (" . implode(',', $ids) . ")");
        }
        if ($rows < 500 || !$ids) {
            break;
        }
    }
}

// [liczba aktywnych plików, ich łączny rozmiar w bajtach]
function user_usage(mysqli $conn, int $uid): array {
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS c, COALESCE(SUM(size), 0) AS s FROM files WHERE user_id = ? AND expires_at > NOW()"
    );
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    return [(int)$r['c'], (int)$r['s']];
}

function clean_filename(string $n): string {
    $n = basename(str_replace('\\', '/', $n));
    $n = preg_replace('/[\x00-\x1F\x7F]/u', '', $n) ?? '';   // znaki sterujące; null przy złym UTF-8
    $n = trim($n);
    if ($n === '' || $n === '.' || $n === '..') {
        $n = 'file';
    }
    return mb_substr($n, 0, 200);
}

function fmt_size(int $b): string {
    if ($b >= 1048576) return number_format($b / 1048576, 2) . ' MB';
    if ($b >= 1024)    return number_format($b / 1024, 1) . ' KB';
    return $b . ' B';
}
