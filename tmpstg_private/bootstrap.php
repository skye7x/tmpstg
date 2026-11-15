<?php
ini_set('display_errors', '0');
ini_set('log_errors', '1');          // błędy lądują w error_log (cPanel), nie na ekranie

// Plik odpowidzialny za securrity stronki
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');   // gdy przed serwerem stoi proxy/CDN (np. Cloudflare)
ini_set('session.use_strict_mode', '1');      // serwer nie przyjmuje ID sesji wymyślonego przez klienta
ini_set('session.use_only_cookies', '1');     // ID sesji nigdy w URL
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,                  // JS nie odczyta ciasteczka
    'secure'   => $https,                // włączy się samo po przejściu na HTTPS
    'samesite' => 'Lax',
]);
session_start();

// Nagłówki bezpieczeństwa
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
// Font Awesome jest hostowany lokalnie (vendor/), więc nie potrzeba już żadnego zewnętrznego CDN
header("Content-Security-Policy: default-src 'self'; style-src 'self'; font-src 'self'; script-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'none'");
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('Cache-Control: no-store');       // po wylogowaniu "wstecz" nie pokaże strony
if ($https) {
    header('Strict-Transport-Security: max-age=31536000');
}

// Timeout sesji: 30 min bezczynności, 8 h łącznie
const SESSION_IDLE_SECONDS = 1800;
const SESSION_MAX_SECONDS  = 28800;
if (isset($_SESSION['usrid'])) {
    $now = time();
    if ($now - ($_SESSION['last_activity'] ?? 0) > SESSION_IDLE_SECONDS
        || $now - ($_SESSION['created_at'] ?? 0) > SESSION_MAX_SECONDS) {
        $_SESSION = [];
        session_destroy();
        session_start();
    } else {
        $_SESSION['last_activity'] = $now;
    }
}

// CSRF
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function csrf_check(): void {
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(403);
        exit('Invalid request.');
    }
}

// Bezpieczne wypisywanie tekstu na stronie (XSS)
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
