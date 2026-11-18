<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Official Login - E-Serbisyo</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('stylesheet/admin_dashboard.css') }}">
  <link rel="stylesheet" href="{{ asset('stylesheet/admin_login.css') }}">
</head>

<body>

  <div class="container">

    <div class="left-panel">

      <a href="{{ url('/') }}" class="back-link">
        <i class="fas fa-arrow-left"></i> Back
      </a>

      <div class="logo-header">
        <div class="logo-circle">
          <img src="{{ asset('logo/HomesteadLogo.jpg') }}" alt="Barangay Logo">
        </div>

        <div class="logo-text">
          <h2>E-SERBISYO</h2>
          <span class="location">
            Homestead, Bambang, Nueva Vizcaya
          </span>
        </div>

      </div>

      <div class="tagline">
        Secure access for barangay administrators and staff members.
      </div>

    </div>

    <div class="right-panel">

      <h3>
        <i class="fas fa-user-shield" style="color:#2563eb;"></i>
        Official Login
      </h3>

      <p class="sub-text">
        Enter your administrator credentials.
      </p>

      <form method="POST" action="{{ route('admin.login.store') }}">
        @csrf

        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" id="admin-email" value="{{ session('admin_login_email', '') }}" required>
          <div class="field-error" id="admin-email-error"></div>
        </div>

        <div class="form-group">
          <label>Password</label>
          <div class="password-wrap">
            <input type="password" name="password" id="admin-password" required>
            <button type="button" class="toggle-password" id="admin-toggle-password" aria-label="Show password"
              tabindex="-1">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <div class="field-error" id="admin-password-error"></div>
        </div>

        <button type="submit" id="admin-submit-btn">
          <span id="admin-submit-label">Sign In</span>
        </button>

      </form>

      @if (session('admin_login_error'))
        <div class="error" role="alert">
          {{ session('admin_login_error') }}
        </div>
      @endif

    </div>

  </div>

  <script src="{{ asset('script/admin_login.js') }}"></script>

</body>

</html>
