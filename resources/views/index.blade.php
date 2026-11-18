<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay E-Serbisyo Portal</title>
    <link rel="stylesheet" href="{{ asset('stylesheet/index.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="{{ asset('logo/HomesteadLogo.jpg') }}" alt="Barangay Logo">
            </div>
            <h1>E-SERBISYO</h1>
            <p>Barangay Homestead, Bambang, Nueva Vizcaya</p>
        </div>

        <div class="content">
            <h2>Welcome</h2>
            <p>Select your portal access below</p>

            <a href="{{ url('/resident/login') }}" class="portal-btn">
                <div class="portal-info">
                    <div class="portal-icon"><i class="fas fa-users"></i></div>
                    <div class="portal-text">
                        <strong>Resident Portal</strong>
                        <small>For Barangay Residents</small>
                    </div>
                </div>
                <i class="fas fa-chevron-right arrow"></i>
            </a>

            <a href="{{ url('/admin/login') }}" class="portal-btn">
                <div class="portal-info">
                    <div class="portal-icon"><i class="fas fa-user-shield"></i></div>
                    <div class="portal-text">
                        <strong>Official Login</strong>
                        <small>Admin &amp; Staff Only</small>
                    </div>
                </div>
                <i class="fas fa-chevron-right arrow"></i>
            </a>
        </div>

        <div class="footer">Secure • Fast • Reliable</div>
    </div>

    <script>
        document.querySelectorAll('.portal-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var arrow = this.querySelector('.arrow');
                arrow.classList.remove('fa-chevron-right');
                arrow.classList.add('fa-spinner', 'fa-spin');
                this.style.pointerEvents = 'none';
            });
        });
    </script>
</body>
</html>