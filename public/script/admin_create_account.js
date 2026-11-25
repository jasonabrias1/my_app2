
function openCreateAccountModal(name, type) {
    document.getElementById('createdAccountName').textContent = name;
    document.getElementById('createdAccountType').textContent = type === 'admin' ? 'Admin' : 'Resident';
    document.getElementById('createStepSuccess').style.display = 'block';
    document.getElementById('createStepAgain').style.display = 'none';
    document.getElementById('createAccountModal').classList.add('open');
}

function updateAccountType() {
    var accountType = document.getElementById('accountType').value;
    var isResident = accountType === 'resident';

    document.querySelectorAll('[data-resident-only]').forEach(function (section) {
        section.style.display = isResident ? '' : 'none';
    });
    document.querySelectorAll('[data-required-role="resident"]').forEach(function (input) {
        input.required = isResident;
    });
    document.getElementById('accountInfoTitle').textContent = isResident ? "Resident's Information" : "Admin's Information";
    document.getElementById('emailHelp').textContent = isResident
        ? 'Used to log in to the resident portal.'
        : 'Used to log in to the admin portal.';
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


document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('accountType').addEventListener('change', updateAccountType);
    updateAccountType();

    if (typeof CREATED_ACCOUNT_NAME !== 'undefined') {
        openCreateAccountModal(CREATED_ACCOUNT_NAME, CREATED_ACCOUNT_TYPE);
    }
});