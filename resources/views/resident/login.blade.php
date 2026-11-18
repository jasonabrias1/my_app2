<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resident Login - E-Serbisyo</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('stylesheet/resident_login.css') }}">
</head>

<body>

  <div class="container">

    <div class="left-panel">

      <a href="{{ url('/') }}" class="back-link">
        <i class="fas fa-arrow-left"></i> Back
      </a>

      <div class="brand-header">

        <div class="logo-circle">
          <img src="{{ asset('logo/HomesteadLogo.jpg') }}" alt="Barangay Logo">
        </div>

        <div class="brand-text">
          <h2>E-SERBISYO</h2>
          <span class="location">
            Homestead, Bambang, Nueva Vizcaya
          </span>
        </div>

      </div>

      <div class="tagline">
        Access barangay services, requests, and updates through the Resident Portal.
      </div>

    </div>

    <div class="right-panel">

      <h3>
        <i class="fas fa-users" style="color:#000000;"></i>
        Resident Login
      </h3>
      <p class="sub-text">
        Sign in to access your resident account.
      </p>

      <form method="POST" action="{{ route('resident.login.store') }}" autocomplete="on" novalidate>
        @csrf

        <div class="form-group">
          <label for="resident-email">Email Address</label>
          <input type="email" name="email" id="resident-email" placeholder="name@example.com"
            value="{{ session('resident_login_email', $email) }}" autocomplete="username" required>
          <div class="field-error" id="email-error"></div>
        </div>

        <div class="form-group">
          <label for="resident-password">Password</label>
          <div class="password-wrap">
            <input type="password" name="password" id="resident-password" placeholder="Enter your password"
              autocomplete="current-password" required>
            <button type="button" class="toggle-password" id="toggle-password" aria-label="Show password"
              tabindex="-1">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <div class="field-error" id="password-error"></div>
        </div>

        <button type="submit" id="login-submit-btn">
          <span id="login-submit-label">Sign In</span>
        </button>

        @if (session('resident_login_error'))
          <div class="error-message" role="alert">
            {{ session('resident_login_error') }}
          </div>
        @endif
      </form>

    </div>

  </div>

  <script>
      var form = document.querySelector('form[method="POST"]');
      var emailInput = document.getElementById('resident-email');
      var passwordInput = document.getElementById('resident-password');
      var emailError = document.getElementById('email-error');
      var passwordError = document.getElementById('password-error');
      var toggleBtn = document.getElementById('toggle-password');
      var submitBtn = document.getElementById('login-submit-btn');
      var submitLabel = document.getElementById('login-submit-label');

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

      window.addEventListener('pageshow', function () {
        submitBtn.disabled = false;
        submitLabel.textContent = 'Sign In';
      });

      toggleBtn.addEventListener('click', function () {
        if (passwordInput.type == 'password') {
          passwordInput.type = 'text';
          toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
          toggleBtn.setAttribute('aria-label', 'Hide password');
        } else {
          passwordInput.type = 'password';
          toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
          toggleBtn.setAttribute('aria-label', 'Show password');
        }
      });

      emailInput.addEventListener('input', function () { clearError(emailInput, emailError); });
      passwordInput.addEventListener('input', function () { clearError(passwordInput, passwordError); });

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
  </script>
</body>

</html>
