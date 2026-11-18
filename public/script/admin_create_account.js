// Two-step "account created" confirmation:
function openCreateAccountModal(name) {
    document.getElementById('createdResidentName').textContent = name;
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
    window.location.href = window.ADMIN_RESIDENTS_URL;
}

function resetCreateAccountForm() {
    document.getElementById('createAccountModal').classList.remove('open');
    window.location.href = window.ADMIN_ACCOUNTS_URL;
}

// Password show/hide toggle
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

// Open the "account created" popup after a successful save
document.addEventListener('DOMContentLoaded', function () {
    if (typeof CREATED_RESIDENT_NAME !== 'undefined') {
        openCreateAccountModal(CREATED_RESIDENT_NAME);
    }
});