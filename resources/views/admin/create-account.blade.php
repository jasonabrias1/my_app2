<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Resident Account - E-Serbisyo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('stylesheet/admin_dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('stylesheet/admin_create_account.css') }}">
    <style>
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none;
        }
    </style>
</head>
<body>
    @include('admin.partials.sidebar')

    <div class="main">
        <div class="topbar">
            <h2>Create Resident Account</h2>
            <div class="who">Signed in as <b>{{ $admin_name }}</b></div>
        </div>

        <div class="panel form-grid">
            <h3></h3>
            @if ($errors->any())
                <div class="error-list">
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <form method="POST" action="{{ url()->current() }}">
                @csrf
                <input type="hidden" name="action" value="create_resident">

                <div class="form-section-title">Resident's Information</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name <span class="req-mark">*</span></label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Name <span class="opt">(optional)</span></label>
                        <input type="text" name="middle_name" value="{{ old('middle_name') }}">
                    </div>
                    <div class="form-group">
                        <label>Last Name <span class="req-mark">*</span></label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Suffix <span class="opt">(optional)</span></label>
                        <input type="text" name="suffix" value="{{ old('suffix') }}" placeholder="Jr., Sr., III...">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Sitio / Purok <span class="req-mark">*</span></label>
                        <select name="address_purok" required>
                            <option value="" {{ old('address_purok') === null ? 'selected' : '' }} disabled>Select</option>
                            @foreach (['Purok 1', 'Purok 2', 'Purok 3'] as $purok)
                                <option value="{{ $purok }}" @selected(old('address_purok') === $purok)>{{ $purok }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Civil Status <span class="req-mark">*</span></label>
                        <select name="civil_status" required>
                            <option value="" {{ old('civil_status') === null ? 'selected' : '' }} disabled>Select</option>
                            @foreach (['Single', 'Married', 'Widowed', 'Separated'] as $status)
                                <option value="{{ $status }}" @selected(old('civil_status') === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nationality <span class="req-mark">*</span></label>
                        <input type="text" name="nationality" value="{{ old('nationality', 'Filipino') }}" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Birth <span class="req-mark">*</span></label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Place of Birth <span class="req-mark">*</span></label>
                        <input type="text" name="place_of_birth" value="{{ old('place_of_birth') }}" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Mobile Number <span class="req-mark">*</span></label>
                        <input type="tel" name="mobile_number" value="{{ old('mobile_number') }}"
                            placeholder="09xxxxxxxxx" pattern="09[0-9]{9}" maxlength="11" inputmode="numeric"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                        <small style="color:var(--muted);font-size:11.5px;">Format: 09xxxxxxxxx</small>
                    </div>
                    <div class="form-group">
                        <label>Email Address <span class="req-mark">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required>
                        <small style="color:var(--muted);font-size:11.5px;">Used to log in to the resident portal.</small>
                    </div>
                </div>

                <div class="form-section-title">Account Password</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Password</label>
                        <div class="password-field-wrap">
                            <input type="password" name="password" id="passwordInput" required minlength="8">
                            <button type="button" class="password-toggle-btn"
                                onclick="togglePasswordVisibility('passwordInput', this)"
                                aria-label="Show password" tabindex="-1"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <div class="password-field-wrap">
                            <input type="password" name="confirm_password" id="confirmPasswordInput" required minlength="8">
                            <button type="button" class="password-toggle-btn"
                                onclick="togglePasswordVisibility('confirmPasswordInput', this)"
                                aria-label="Show password" tabindex="-1"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                </div>
                <div class="form-submit-row">
                    <button type="submit" class="btn btn-centered">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="createAccountModal">
        <div class="modal-box" style="width:380px;">
            <div class="modal-body" style="padding:32px 26px;text-align:center;">
                <div id="createStepSuccess">
                    <div class="modal-doc-icon"
                        style="margin:0 auto 16px;background:var(--green-bg);color:var(--green);width:52px;height:52px;font-size:22px;">
                        <i class="fas fa-check"></i>
                    </div>
                    <h3 style="margin-bottom:8px;">Account Created</h3>
                    <p style="color:var(--muted);font-size:13.5px;margin-bottom:22px;">
                        Resident account for <strong id="createdResidentName"></strong> has been created successfully.
                    </p>
                    <button type="button" class="btn btn-block" onclick="showCreateAnotherStep()">OK</button>
                </div>
                <div id="createStepAgain" style="display:none;">
                    <div class="modal-doc-icon"
                        style="margin:0 auto 16px;background:var(--blue-bg);color:var(--primary-light);width:52px;height:52px;font-size:22px;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3 style="margin-bottom:8px;">Create another account?</h3>
                    <p style="color:var(--muted);font-size:13.5px;margin-bottom:22px;">
                        You can add another resident now, or head back to the dashboard.
                    </p>
                    <div style="display:flex;gap:10px;">
                        <button type="button" class="btn btn-view" style="flex:1;"
                            onclick="closeCreateAccountModal()">No, I'm done</button>
                        <button type="button" class="btn" style="flex:1;"
                            onclick="resetCreateAccountForm()">Yes, add another</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('created_resident_name'))
        <script>var CREATED_RESIDENT_NAME = @json(session('created_resident_name'));</script>
    @endif
    <script>
        window.ADMIN_RESIDENTS_URL = @json(route('admin.residents'));
        window.ADMIN_ACCOUNTS_URL = @json(route('admin.accounts'));
    </script>
    <script src="{{ asset('script/admin_create_account.js') }}"></script>
</body>
</html>