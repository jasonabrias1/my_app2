function openCreateAccountModal(name) {
    document.getElementById('createdAccountName').textContent = name;
    document.getElementById('createStepSuccess').style.display = 'block';
    document.getElementById('createStepAgain').style.display = 'none';
    document.getElementById('createAccountModal').classList.add('open');
}

function showCreateAnotherStep() {
    document.getElementById('createStepSuccess').style.display = 'none';
    document.getElementById('createStepAgain').style.display = 'block';
}

function closeCreateAccountModal() {
    document.getElementById('createAccountModal').classList.remove('open');
    window.location.href = window.ADMIN_DONE_URL;
}

function resetCreateAccountForm() {
    document.getElementById('createAccountModal').classList.remove('open');
    window.location.href = window.ADMIN_CREATE_URL;
}

function togglePasswordVisibility(inputId, btn) {
    var input = document.getElementById(inputId);
    var icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
        btn.setAttribute('aria-label', 'Hide password');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
        btn.setAttribute('aria-label', 'Show password');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('createAccountForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            var pw = document.getElementById('passwordInput').value;
            var cpw = document.getElementById('confirmPasswordInput').value;
            if (pw !== cpw) {
                e.preventDefault();
                alert('Passwords do not match.');
            }
        });
    }

    if (typeof CREATED_ACCOUNT_NAME !== 'undefined') {
        openCreateAccountModal(CREATED_ACCOUNT_NAME);
    }
});