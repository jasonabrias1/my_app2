function openCreateAccountModal(name, type) {
    document.getElementById('createdAccountName').textContent = name;
    document.getElementById('createdAccountTypeLabel').textContent = type === 'admin' ? 'Admin' : 'Resident';
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

// Show only the fields for the selected account type.
// Hidden sections are disabled so they are not validated or submitted.
function applyAccountType() {
    var type = document.getElementById('accountType').value;
    var fields = document.getElementById('formFields');

    fields.style.display = type ? 'block' : 'none';
    document.getElementById('actionInput').value = type === 'admin' ? 'create_admin' : 'create_resident';
    document.getElementById('infoTitle').textContent =
        type === 'admin' ? "Admin's Information" : "Resident's Information";
    document.getElementById('emailHint').textContent =
        type === 'admin' ? 'Used to log in to the admin portal.' : 'Used to log in to the resident portal.';
    document.getElementById('submitBtn').textContent =
        type === 'admin' ? 'Create Admin Account' : 'Create Resident Account';

    document.querySelectorAll('.type-section').forEach(function (section) {
        var active = section.dataset.type === type;
        section.classList.toggle('active', active);
        section.querySelectorAll('input, select, textarea').forEach(function (el) {
            el.disabled = !active;
        });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    var select = document.getElementById('accountType');
    select.addEventListener('change', applyAccountType);
    applyAccountType();

    document.getElementById('createAccountForm').addEventListener('submit', function (e) {
        var pw = document.getElementById('passwordInput').value;
        var cpw = document.getElementById('confirmPasswordInput').value;
        if (pw !== cpw) {
            e.preventDefault();
            alert('Passwords do not match.');
        }
    });

    if (typeof CREATED_ACCOUNT_NAME !== 'undefined') {
        openCreateAccountModal(CREATED_ACCOUNT_NAME, CREATED_ACCOUNT_TYPE);
    }
});