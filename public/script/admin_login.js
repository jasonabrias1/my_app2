(function () {
  var form = document.querySelector('form[method="POST"]');
  var emailInput = document.getElementById('admin-email');
  var passwordInput = document.getElementById('admin-password');
  var emailError = document.getElementById('admin-email-error');
  var passwordError = document.getElementById('admin-password-error');
  var toggleBtn = document.getElementById('admin-toggle-password');
  var submitBtn = document.getElementById('admin-submit-btn');
  var submitLabel = document.getElementById('admin-submit-label');

  function showError(input, errorEl, message) {
    input.classList.add('invalid');
    errorEl.textContent = message;
    errorEl.classList.add('show');
  }

  function clearError(input, errorEl) {
    input.classList.remove('invalid');
    errorEl.textContent = '';
    errorEl.classList.remove('show');
  }

  function isValidEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
  }

  // Show/hide password
  toggleBtn.addEventListener('click', function () {
    if (passwordInput.type === 'password') {
      passwordInput.type = 'text';
      toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i>'; // fixed typo: slah -> slash
      toggleBtn.setAttribute('aria-label', 'Hide password');
    } else {
      passwordInput.type = 'password';
      toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
      toggleBtn.setAttribute('aria-label', 'Show password');
    }
  });

  // Live-clear errors as the person types
  emailInput.addEventListener('input', function () {
    clearError(emailInput, emailError);
  });
  passwordInput.addEventListener('input', function () {
    clearError(passwordInput, passwordError);
  });

  // Client-side validation before the existing POST submit runs.
  form.addEventListener('submit', function (e) {
    var valid = true;

    if (!emailInput.value.trim()) {
      showError(emailInput, emailError, 'Email address is required.');
      valid = false;
    } else if (!isValidEmail(emailInput.value.trim())) {
      showError(emailInput, emailError, 'Enter a valid email address.');
      valid = false;
    } else {
      clearError(emailInput, emailError);
    }

    if (!passwordInput.value) {
      showError(passwordInput, passwordError, 'Password is required.');
      valid = false;
    } else {
      clearError(passwordInput, passwordError);
    }

    if (!valid) {
      e.preventDefault();
      return;
    }

    submitBtn.disabled = true;
    submitLabel.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
  });

  // Kapag bumalik gamit ang back button, ibalik ang button state
  window.addEventListener('pageshow', function () {
    submitBtn.disabled = false;
    submitLabel.textContent = 'Sign In';
  });
})();