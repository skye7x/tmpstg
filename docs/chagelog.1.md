# Changelog: audyt i utwardzanie aplikacji logowania (tmpStg)

Data: 2026-11-15. Punkt wyjścia: projekt z tutoriala (PHP + MySQL, XAMPP).

## Krok 1: SQL Injection
- Wszystkie zapytania (sprawdzanie emaila, INSERT, logowanie, pobranie użytkownika) przepisane na prepared statements (`prepare` / `bind_param` / `execute` / `get_result`).

## Krok 2: Hasła
- `md5()` zastąpione przez `password_hash(..., PASSWORD_DEFAULT)` (rejestracja) i `password_verify()` (logowanie).
- Logowanie szuka użytkownika tylko po emailu, hasło sprawdza PHP.
- Kolumna `password` ma `VARCHAR(255)`.
- Jeden wspólny komunikat dla złego emaila i złego hasła.

## Krok 3: XSS
- Wypisywanie danych użytkownika przez `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` (helper `e()`).

## Krok 4: Sesje
- `logout.php`: `session_start()`, wyczyszczenie `$_SESSION`, usunięcie ciasteczka, `session_destroy()`, `exit`.
- `session_regenerate_id(true)` po zalogowaniu (session fixation).
- W sesji `usrid` zamiast emaila; zapytania po `id` (`bind_param("i")`).

## Krok 5: Ochrona stron
- `homepage.php` przekierowuje niezalogowanych (sprawdzenie na samej górze, przed HTML).
- Obsługa konta usuniętego przy żywej sesji.

## Krok 6: Połączenie z bazą
- Osobny użytkownik `tmpstg-app` z minimalnymi uprawnieniami zamiast `root`.
- `mysqli_report` + `try/catch`: szczegóły błędów tylko w logu, użytkownik widzi ogólny komunikat.
- `set_charset('utf8mb4')`.

## Krok 7: Walidacja po stronie serwera
- Imię i nazwisko: niepuste, max 50 znaków. Email: `FILTER_VALIDATE_EMAIL`, max 254. Hasło: 10-128 znaków, sprawdzane PRZED haszowaniem.
- `trim()` na imieniu, nazwisku i emailu (hasło bez trim). Email zapisywany małymi literami.

## Krok 8: Unikalność, komunikaty, logout
- `UNIQUE` na `users.email` w bazie.
- Rejestracja nie zdradza, czy email istnieje (ta sama odpowiedź); duplikat łapany przez `try/catch` (kod 1062).
- Wylogowanie wyłącznie przez POST z tokenem CSRF (formularz zamiast linku).

## Krok 9: Porządek
- `register.php` i `login.php` rozdzielone (jeden plik, jedno zadanie).
- `index.html` -> `index.php`; unikalne `id` pól, poprawne `autocomplete`, `maxlength`.
- Linki względne (koniec problemu `/tmpStg` vs `/tmpstg/`).
- Usunięte debugi i zakomentowany kod; style inline przeniesione do `style.css`.

## Krok 10: Dodatkowa ochrona
- `bootstrap.php` (dołączany wszędzie): ciasteczka `HttpOnly`, `SameSite=Lax`, `Secure` (automatycznie na HTTPS), `display_errors=0`.
- Tokeny CSRF we wszystkich formularzach (`hash_equals`), odnawiane po zalogowaniu.
- Limit prób logowania: 5 na email lub 20 na IP w 15 minut (tabela `login_attempts`).
- Timeout sesji: 30 min bezczynności, 8 h maksymalnie.
- Nagłówki: CSP, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Cache-Control: no-store`, HSTS (na HTTPS).
- Wyrównanie czasu odpowiedzi logowania dla nieistniejącego użytkownika.

## Pliki
`bootstrap.php`, `connect.php`, `index.php`, `register.php`, `login.php`, `logout.php`, `homepage.php`, `script.js`, `style.css`, `database/schema.sql`.

## Do zrobienia (poza zakresem)
- **Zmienić hasło użytkownika `tmpstg-app`** (poprzednie zostało wklejone do czatu) i trzymać `connect.php` poza repozytorium.
- HTTPS (Let's Encrypt) przy publikacji w internecie.
- Font Awesome: dodać `integrity` (SRI) albo hostować lokalnie.
- Reset hasła (link "Recover" jest nadal martwy), weryfikacja emaila, 2FA (TOTP).
- Logowanie zdarzeń bezpieczeństwa do pliku, cykliczne czyszczenie `login_attempts`.
- Hasło do bazy ze zmiennej środowiskowej lub pliku poza `htdocs`.
