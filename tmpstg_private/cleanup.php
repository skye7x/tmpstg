<?php
// Sprzątanie wygasłych plików z linii poleceń (np. cron co minutę):  php cleanup.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/connect.php';
require __DIR__ . '/storage.php';
purge_expired($conn);
purge_attempts($conn);      // stare próby logowania/rejestracji (connect.php)
echo "ok\n";
