const SignUpButton = document.getElementById('signUpButton');
const SignInButton = document.getElementById('signInButton');
const SignInForm = document.getElementById('signin');
const SignUpForm = document.getElementById('signup');

function showSignUp() {
    SignInForm.style.display = 'none';
    SignUpForm.style.display = 'block';
}
function showSignIn() {
    SignUpForm.style.display = 'none';
    SignInForm.style.display = 'block';
}

SignUpButton.addEventListener('click', function (e) { e.preventDefault(); showSignUp(); });
SignInButton.addEventListener('click', function (e) { e.preventDefault(); showSignIn(); });

// po błędzie rejestracji (index.php?error=invalid#signup) pokaż formularz rejestracji
if (location.hash === '#signup') {
    showSignUp();
}

// podpowiedzi o haśle (tylko UX; prawdziwa walidacja jest w register.php)
const pwd = document.getElementById('suPassword');
pwd.addEventListener('input', () => {
    const len = pwd.value.length;
    document.getElementById('hint-min').classList.toggle('ok', len >= 10);
    document.getElementById('hint-max').classList.toggle('ok', len > 0 && len <= 128);
});
