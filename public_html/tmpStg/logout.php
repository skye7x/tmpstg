<?php
// Pliki wspólne leżą POZA public_html (nieosiągalne z przeglądarki)
require __DIR__ . '/../../tmpstg_private/bootstrap.php';

// Wylogowanie tylko przez POST + token CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $p['path'],
        'secure'   => $p['secure'],
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_destroy();
}
header('Location: index.php');
exit;
