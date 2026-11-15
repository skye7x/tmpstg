const SignUpButton = document.getElementById('signUpButton');
const SignInButton = document.getElementById('signInButton');
const SignInForm = document.getElementById('signin');
const SignUpForm = document.getElementById('signup');

SignUpButton.addEventListener('click', function() {
    SignInForm.style.display = 'none';
    SignUpForm.style.display = 'block';
});

SignInButton.addEventListener('click', function() {
    SignUpForm.style.display = 'none';
    SignInForm.style.display = 'block';
});

const pwd = document.querySelector('input[name="password"]');

pwd.addEventListener('input', () => {
    const len = pwd.value.length;
    document.getElementById('hint-min').classList.toggle('ok', len >= 10);
    document.getElementById('hint-max').classList.toggle('ok', len > 0 && len <= 128);
});
