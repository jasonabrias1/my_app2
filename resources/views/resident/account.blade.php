<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Account Settings</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" />
    <link rel="stylesheet" href="{{ asset('stylesheet/resident_account.css') }}">
    <link rel="stylesheet" href="{{ asset('stylesheet/logout_animation.css') }}">
</head>

<body>
    <?php
    if (!function_exists('residentAccountPwField')) {
        function residentAccountPwField($id, $name, $label, $placeholder, $extra = '')
        {
            return '<div class="form-group">
                <label for="' . $id . '">' . $label . '</label>
                <div class="pw-wrap">
                    <input type="password" id="' . $id . '" name="' . $name . '" placeholder="' . $placeholder . '" required
                        autocomplete="new-password" readonly onfocus="this.removeAttribute(\'readonly\')" ' . $extra . '>
                    <button type="button" class="pw-toggle" data-target="' . $id . '" aria-label="Show password" aria-pressed="false">
                        <i class="ti ti-eye"></i>
                    </button>
                </div>
            </div>';
        }
    }
    ?>


    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon"><img src="{{ asset('logo/HomesteadLogo.jpg') }}" alt="Barangay Logo" /></div>
            <div>
                <div class="brand-name">Homestead Barangay</div>
                <div class="brand-loc">Bambang, Nueva Vizcaya</div>
            </div>
            <button type="button" class="menu-toggle" id="menu-toggle" aria-label="Toggle menu">
                <i class="ti ti-menu-2"></i>
            </button>
        </div>

        <nav class="nav-section" id="nav-section">
            <div class="nav-label">Menu</div>
            <a href="{{ route('resident.dashboard') }}" class="nav-item"><i class="ti ti-home"></i> Home</a>
            <a href="{{ route('resident.submit') }}" class="nav-item"><i class="ti ti-file-plus"></i> Submit a request</a>
            <a href="{{ route('resident.requests') }}" class="nav-item"><i class="ti ti-list-check"></i> My requests</a>
            <a href="{{ route('resident.tracking') }}" class="nav-item"><i class="ti ti-map-pin"></i> Track request</a>
            <a href="{{ route('resident.account') }}" class="nav-item active"><i class="ti ti-user-cog"></i> Account Settings</a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-chip">
                <div class="avatar"><?php echo $initials; ?></div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
                    <div class="user-role">Resident</div>
                </div>
            </div>
            <a href="{{ route('resident.logout') }}" class="nav-item logout"><i class="ti ti-logout"></i> Log out</a>
        </div>
    </aside>


    <main class="main-content">
        <div class="page-wrap">

            <div class="page-header">
                <h1>Account Settings</h1>
                <p>Choose what you want to update. We'll ask for your password to keep your account safe.</p>
            </div>


            <section class="profile-card">
                <div class="profile-avatar"><?php echo $initials; ?></div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($userName); ?></h2>
                    <div class="profile-meta">
                        <span><i class="ti ti-mail"></i> <?php echo htmlspecialchars($emailInProfile); ?></span>
                        <span><i class="ti ti-device-mobile"></i> <?php echo htmlspecialchars($mobileInProfile); ?></span>
                    </div>
                </div>
            </section>


            <div class="tabs" role="tablist" aria-label="Account settings">
                <button type="button" class="tab" role="tab" data-tab="email" aria-selected="<?php echo $emailTabSelected; ?>">
                    <i class="ti ti-mail"></i> Email
                </button>
                <button type="button" class="tab" role="tab" data-tab="mobile" aria-selected="<?php echo $mobileTabSelected?>">
                    <i class="ti ti-device-mobile"></i> Mobile
                </button>
                <button type="button" class="tab" role="tab" data-tab="password" aria-selected="<?php echo $passwordTabSelected?>">
                    <i class="ti ti-lock"></i> Password
                </button>
            </div>


            <section class="tab-panel <?php echo $emailTabClass; ?>" id="panel-email" role="tabpanel">
                <div class="status-card account-card">
                    <div class="account-card-header">
                        <div class="card-icon blue"><i class="ti ti-mail"></i></div>
                        <div>
                            <h2>Change email address</h2>
                            <p>Current email: <strong><?php echo htmlspecialchars($emailInCard); ?></strong></p>
                        </div>
                    </div>

                    <?php if ($emailSuccess): ?>
                        <div class="alert alert-success"><i class="ti ti-circle-check"></i> <?php echo htmlspecialchars($emailSuccess); ?></div>
                    <?php elseif ($emailError): ?>
                        <div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?php echo htmlspecialchars($emailError); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="{{ route('resident.account.update') }}" class="account-form" autocomplete="off">
                        @csrf
                        <input type="hidden" name="action" value="change_email">
                        <div class="form-group">
                            <label for="new_email">New email address</label>
                            <input type="email" id="new_email" name="new_email" placeholder="you@example.com" required>
                        </div>
                        <?php echo residentAccountPwField('current_password_email', 'current_password_email', 'Confirm your password', 'Enter your current password'); ?>
                        <button type="submit" class="form-submit"><i class="ti ti-device-floppy"></i> Save email</button>
                    </form>
                </div>
            </section>


            <section class="tab-panel <?php echo $mobileTabClass; ?>" id="panel-mobile" role="tabpanel">
                <div class="status-card account-card">
                    <div class="account-card-header">
                        <div class="card-icon green"><i class="ti ti-device-mobile"></i></div>
                        <div>
                            <h2>Change mobile number</h2>
                            <p>Current number: <strong><?php echo htmlspecialchars($mobileInCard); ?></strong></p>
                        </div>
                    </div>

                    <div class="steps" aria-label="Progress">
                        <span class="step <?php echo $step1Class;  ?>"><b>1</b> New number</span>
                        <span class="line"></span>
                        <span class="step <?php echo $step2Class; ?>"><b>2</b> Verify code</span>
                    </div>

                    <?php if ($mobileSuccess): ?>
                        <div class="alert alert-success"><i class="ti ti-circle-check"></i> <?php echo htmlspecialchars($mobileSuccess); ?></div>
                    <?php elseif ($mobileError): ?>
                        <div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?php echo htmlspecialchars($mobileError); ?></div>
                    <?php elseif ($mobileInfo): ?>
                        <div class="alert alert-info"><i class="ti ti-info-circle"></i> <?php echo htmlspecialchars($mobileInfo); ?></div>
                    <?php endif; ?>

                    <?php if ($mobilePending): ?>

                        <form method="POST" action="{{ route('resident.account.update') }}" class="account-form" autocomplete="off">
                        @csrf
                            <input type="hidden" name="action" value="verify_mobile_otp">
                            <p class="form-note">
                                Changing to <strong><?php echo htmlspecialchars($mobilePending['new_mobile']); ?></strong>.
                                Enter the code sent to your saved number
                                <strong><?php echo htmlspecialchars($currentMobileMasked); ?></strong>.
                            </p>
                            <div class="form-group">
                                <label for="otp_code">Verification code</label>
                                <input type="text" id="otp_code" name="otp_code" class="otp-input" inputmode="numeric"
                                    pattern="[0-9]{<?php echo $otpLength; ?>}" maxlength="<?php echo $otpLength; ?>"
                                    placeholder="••••••" required autofocus>
                            </div>
                            <button type="submit" class="form-submit"><i class="ti ti-shield-check"></i> Verify and save number</button>
                        </form>

                        <div class="otp-actions">
                            <form method="POST" action="{{ route('resident.account.update') }}">
                        @csrf
                                <input type="hidden" name="action" value="resend_mobile_otp">
                                <button type="submit" class="link-btn" id="resend-btn"
                                    data-wait="<?php echo (int) $resendWait; ?>" <?php echo $resendDisabled; ?>>
                                    <i class="ti ti-refresh"></i> <span id="resend-label">Resend code</span>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('resident.account.update') }}">
                        @csrf
                                <input type="hidden" name="action" value="cancel_mobile_otp">
                                <button type="submit" class="link-btn"><i class="ti ti-x"></i> Cancel</button>
                            </form>
                        </div>

                    <?php else: ?>

                        <form method="POST" action="{{ route('resident.account.update') }}" class="account-form" autocomplete="off">
                        @csrf
                            <input type="hidden" name="action" value="send_mobile_otp">
                            <div class="form-group">
                                <label for="new_mobile">New mobile number</label>
                                <input type="tel" id="new_mobile" name="new_mobile" placeholder="09xxxxxxxxx"
                                    pattern="09[0-9]{9}" maxlength="11" inputmode="numeric"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                            </div>
                            <?php echo residentAccountPwField('current_password_mobile', 'current_password_mobile', 'Confirm your password', 'Enter your current password'); ?>
                            <p class="form-note small">We'll text a verification code to the number currently saved on your account.</p>
                            <button type="submit" class="form-submit"><i class="ti ti-send"></i> Send verification code</button>
                        </form>

                    <?php endif; ?>
                </div>
            </section>


            <section class="tab-panel <?php echo $passwordTabClass; ?>" id="panel-password" role="tabpanel">
                <div class="status-card account-card">
                    <div class="account-card-header">
                        <div class="card-icon amber"><i class="ti ti-lock"></i></div>
                        <div>
                            <h2>Change password</h2>
                            <p>Use at least 8 characters. Avoid reusing old passwords.</p>
                        </div>
                    </div>

                    <?php if ($passwordSuccess): ?>
                        <div class="alert alert-success"><i class="ti ti-circle-check"></i> <?php echo htmlspecialchars($passwordSuccess); ?></div>
                    <?php elseif ($passwordError): ?>
                        <div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?php echo htmlspecialchars($passwordError); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="{{ route('resident.account.update') }}" class="account-form" autocomplete="off" id="password-form">
                        @csrf
                        <input type="hidden" name="action" value="change_password">
                        <?php echo residentAccountPwField('current_password', 'current_password', 'Current password', 'Enter your current password'); ?>
                        <?php echo residentAccountPwField('new_password', 'new_password', 'New password', 'At least 8 characters', 'minlength="8"'); ?>
                        <div class="strength-text" id="strength" data-level="0" aria-live="polite"></div>
                        <?php echo residentAccountPwField('confirm_password', 'confirm_password', 'Confirm new password', 'Re-enter new password', 'minlength="8"'); ?>
                        <div class="field-hint" id="match-hint" aria-live="polite"></div>
                        <button type="submit" class="form-submit"><i class="ti ti-device-floppy"></i> Save password</button>
                    </form>
                </div>
            </section>

        </div>
    </main>
    <script src="{{ asset('script/resident_account.js') }}"></script>
    <script src="{{ asset('script/logout_animation.js') }}"></script>
</body>

</html>