<?php
// Pliki wspólne leżą POZA public_html (nieosiągalne z przeglądarki)
require __DIR__ . '/../../tmpstg_private/bootstrap.php';

// Zalogowany nie musi widzieć formularza
if (isset($_SESSION['usrid'])) {
    header('Location: homepage.php');
    exit;
}

$messages = [
    'invalid' => ['error', 'Invalid input. Check the fields and password length (10-128 characters).'],
    'login'   => ['error', 'Incorrect email or password.'],
    'locked'  => ['error', 'Too many attempts. Try again in 15 minutes.'],
    'ratelimit' => ['error', 'Too many registrations from this address. Try again later.'],
];
$msg = null;
if (isset($_GET['error']) && is_string($_GET['error']) && isset($messages[$_GET['error']])) {
    $msg = $messages[$_GET['error']];
} elseif (isset($_GET['registered'])) {
    $msg = ['ok', 'Done. If the registration was possible, you can now log in.'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>tmpStg</title>
    <!-- Font Awesome 7.3.1 hostowany lokalnie (vendor/fontawesome), więc SRI i CDN nie są potrzebne -->
    <link rel="stylesheet" href="vendor/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="vendor/fontawesome/css/solid.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" id="signup">
        <h1 class="form-title">Register</h1>
        <?php if ($msg): ?><p class="msg <?= e($msg[0]) ?>"><?= e($msg[1]) ?></p><?php endif; ?>
        <form method="post" action="register.php">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <div class="input-group">
                <label for="fName">First Name</label>
                <i class="fa-solid fa-user"></i>
                <input type="text" name="fName" id="fName" placeholder="First Name" maxlength="50" autocomplete="given-name" required>
            </div>
            <div class="input-group">
                <label for="lName">Last Name</label>
                <i class="fa-solid fa-user"></i>
                <input type="text" name="lName" id="lName" placeholder="Last Name" maxlength="50" autocomplete="family-name" required>
            </div>
            <div class="input-group">
                <label for="suEmail">Email</label>
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" id="suEmail" placeholder="Email" maxlength="254" autocomplete="email" required>
            </div>
            <div class="input-group">
                <label for="suPassword">Password</label>
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" id="suPassword" placeholder="Password" maxlength="128" autocomplete="new-password" required>
            </div>
            <input type="submit" class="btn" name="signUp" value="Sign Up">
        </form>
        <div>
            <details class="password-hints">
                <summary>Wymagania dotyczące hasła</summary>
                <ul>
                    <li id="hint-min">Minimum 10 znaków</li>
                    <li id="hint-max">Maksimum 128 znaków</li>
                </ul>
            </details>
            <p class="login-link">Already have an account? <a href="#" id="signInButton">Login</a></p>
        </div>
    </div>

    <div class="container" id="signin">
        <h1 class="form-title">Login</h1>
        <?php if ($msg): ?><p class="msg <?= e($msg[0]) ?>"><?= e($msg[1]) ?></p><?php endif; ?>
        <form method="post" action="login.php">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <div class="input-group">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" id="loginEmail" placeholder="Email" maxlength="254" autocomplete="email" required>
                <label for="loginEmail">Email</label>
            </div>
            <div class="input-group">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" id="loginPassword" placeholder="Password" maxlength="128" autocomplete="current-password" required>
                <label for="loginPassword">Password</label>
            </div>
            <p class="recover">Forgot your password? <a href="#">Recover</a></p>
            <input type="submit" class="btn" name="signIn" value="Sign In">
        </form>
        <p class="login-link">Don't have an account yet? <a href="#" id="signUpButton">Sign Up</a></p>
    </div>

    <script src="script.js"></script>
</body>
</html>
