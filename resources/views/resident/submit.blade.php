<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Request</title>
    <link rel="stylesheet" href="{{ asset('stylesheet/resident_submit.css') }}">
    <link rel="stylesheet" href="{{ asset('stylesheet/logout_animation.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>

<body>
    @if (session('resident_submit_error') || $errors->any() || session('resident_submit_success'))
        <link rel="stylesheet" href="{{ asset('stylesheet/notification.css') }}">
    @endif

    @if (session('resident_submit_error') || $errors->any())
        <div class="toast" id="toast" role="alert" aria-live="assertive">
            <div class="toast-icon">✕</div>
            <div class="toast-message">{{ session('resident_submit_error') ?: $errors->first() }}</div>
            <button type="button" class="toast-close" id="toast-close" aria-label="Dismiss">✕</button>
        </div>
        <script>
            var notifToast = document.getElementById('toast');
            var notifTimer = setTimeout(removeNotifToast, 6000);

            function removeNotifToast() {
                clearTimeout(notifTimer);
                if (notifToast) notifToast.remove();
            }

            document.getElementById('toast-close').addEventListener('click', removeNotifToast);
        </script>
    @elseif (session('resident_submit_success'))
        <div class="modal-overlay" id="success-modal" role="dialog" aria-modal="true" aria-labelledby="success-title">
            <div class="modal-card">
                <div class="modal-check">✓</div>
                <h2 id="success-title">Request Submitted!</h2>
                <p>We've received your request and will update its status soon.</p>

                <div class="modal-tracking">
                    <div class="k">Tracking Number</div>
                    <div class="v">{{ session('resident_submit_success') }}</div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="modal-btn-done" id="btn-done">Done</button>
                    <button type="button" class="modal-btn-track" id="btn-track">Track</button>
                </div>
            </div>
        </div>
        <script>
            var notifModal = document.getElementById('success-modal');
            var notifTrackingCode = @json(session('resident_submit_success'));
            document.getElementById('btn-done').focus();
            document.getElementById('btn-done').addEventListener('click', function () {
                window.location.href = @json(route('resident.requests'));
            });
            document.getElementById('btn-track').addEventListener('click', function () {
                window.location.href = '/resident/tracking?auto_track=1&tracking_no=' + encodeURIComponent(notifTrackingCode);
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') notifModal.remove();
            });
        </script>
    @endif


    <aside class="sidebar" id="sidebar">

        <div class="sidebar-brand">
            <div class="brand-icon">
                <img src="{{ asset('logo/HomesteadLogo.jpg') }}" alt="Homestead Barangay Logo">
            </div>

            <div class="brand-text">
                <div class="brand-name">Homestead Barangay</div>
                <div class="brand-loc">Bambang, Nueva Vizcaya</div>
            </div>

            <button type="button" class="menu-toggle" id="menu-toggle" aria-label="Toggle menu">
                <i class="ti ti-menu-2"></i>
            </button>
        </div>

        <nav class="nav-section" id="nav-section">
            <div class="nav-label">Menu</div>

            <a href="{{ route('resident.dashboard') }}" class="nav-item">
                <i class="ti ti-home"></i> Home
            </a>

            <a href="{{ route('resident.submit') }}" class="nav-item active">
                <i class="ti ti-file-plus"></i> Submit a request
            </a>

            <a href="{{ url('/resident/requests') }}" class="nav-item">
                <i class="ti ti-list-check"></i> My requests
            </a>

            <a href="{{ url('/resident/tracking') }}" class="nav-item">
                <i class="ti ti-map-pin"></i> Track request
            </a>
            <a href="{{ url('/resident/account') }}" class="nav-item">
                <i class="ti ti-user-cog"></i> Account Settings
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-chip">
                <div class="avatar">
                    {{ $initials }}
                </div>

                <div>
                    <div class="user-name">
                        {{ $userName }}
                    </div>
                    <div class="user-role">Resident</div>
                </div>
            </div>

            <a href="{{ route('resident.logout') }}" class="nav-item logout">
                <i class="ti ti-logout"></i> Log out
            </a>
        </div>

    </aside>


    <main class="main-content">

        <div class="page-header">
            <h1>Submit a Request</h1>
            <p>A few quick steps to get your document request in.</p>
        </div>

        <div class="form-card">


            <div class="stepper" id="stepper">

            </div>

            <form action="{{ route('resident.submit.store') }}" method="POST" enctype="multipart/form-data" id="request-form"
                novalidate>
                @csrf

                <div class="form-body">

                    <section class="form-panel active" data-step="request">
                        <div class="panel-head">
                            <div class="panel-head-icon"><i class="ti ti-file-description"></i></div>
                            <div>
                                <h2>What do you need?</h2>
                                <p>Choose the document you're requesting and tell us what it's for.</p>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="request_type">Type of Document <span class="req-mark">*</span></label>
                                <select name="request_type" id="request_type" required>
                                    <option value="">Documents</option>
                                    <option value="Barangay Certificate / Clearance">Barangay Certificate / Clearance</option>
                                    <option value="Certificate of Residency">Certificate of Residency</option>
                                    <option value="Certificate of Indigency">Certificate of Indigency</option>
                                    <option value="Certificate of Good Moral Character">Good Moral Character</option>
                                    <option value="First-Time Job Seeker">First-Time Job Seeker</option>
                                    <option value="Barangay Business Clearance">Barangay Business Clearance</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="copies">Number of Copies <span class="req-mark">*</span></label>
                                <div class="copy-quantity-control">
                                    <input type="number" name="copies" id="copies" min="1" max="99"
                                        value="{{ old('copies', 1) }}" required aria-describedby="copies-help">
                                    <div class="copy-step-buttons">
                                        <button type="button" data-copy-adjust="1" aria-label="Add one copy">
                                            <i class="ti ti-chevron-up" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" data-copy-adjust="-1" aria-label="Remove one copy">
                                            <i class="ti ti-chevron-down" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                                <small id="copies-help">Starts at 1. Choose up to 99 copies.</small>
                            </div>
                        </div>


                        <div class="type-extra" id="js_notice">
                            <div class="notice-box">
                                <i class="ti ti-alert-triangle"></i>
                                <div>
                                    <strong>Reminder for First-Time Job Seekers (RA 11261)</strong>
                                    <ul>
                                        <li><strong>Personal appearance</strong> at the barangay hall is required. You cannot send someone else in your place.</li>
                                        <li>You will sign an <strong>Oath of Undertaking</strong> in front of the Barangay Captain or Secretary.</li>
                                        <li>Upload a valid ID or Birth Certificate in the last step.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>


                        <div class="type-extra" id="biz_fields">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Business Name <span class="req-mark">*</span></label>
                                    <input type="text" name="business_name" id="business_name">
                                </div>
                                <div class="form-group">
                                    <label>Nature of Business <span class="req-mark">*</span></label>
                                    <input type="text" name="business_nature" id="business_nature" placeholder="e.g. Sari-sari store">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Business Address <span class="req-mark">*</span></label>
                                <input type="text" name="business_address" id="business_address">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="purpose">Purpose <span class="req-mark">*</span></label>
                            <textarea name="purpose" id="purpose" rows="3"
                                placeholder="e.g. Employment requirement, school enrollment, loan application..."
                                required></textarea>
                            <div class="required-note" style="text-align:left;">
                                <span class="req-mark">Note:</span>
                                <small>All fields marked with an asterisk (*) are required.</small>
                            </div>
                        </div>
                    </section>


                    <section class="form-panel" data-step="relationship">
                        <div class="panel-head">
                            <div class="panel-head-icon"><i class="ti ti-users"></i></div>
                            <div>
                                <h2>Relationship to the Requestor</h2>
                                <p>Let us know if you're requesting for yourself or for someone else.</p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Relationship to the Resident <span class="req-mark">*</span></label>
                            <select name="relationship_to_resident" id="relationship_to_resident" required>
                                <option value="You" selected>You (the resident)</option>
                                <option value="Mother/Father">Mother/Father</option>
                                <option value="Brother/Sister">Brother/Sister</option>
                                <option value="Grandmother/Grandfather">Grandmother/Grandfather</option>
                                <option value="Son/Daughter">Son/Daughter</option>
                                <option value="Grandson/Granddaughter">Grandson/Granddaughter</option>
                            </select>
                        </div>
                    </section>


                    <section class="form-panel" data-step="personal">
                        <div class="panel-head">
                            <div class="panel-head-icon"><i class="ti ti-id-badge-2"></i></div>
                            <div>
                                <h2>Resident's Information</h2>
                                <p>This is the person the certificate will be issued to. <span id="autofill-note"
                                        style="display:none;">Fields already saved on your account are filled in and
                                        locked, except your mobile number, which you can still edit. Any field left
                                        blank is open for you to fill in.</span></p>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name <span class="req-mark">*</span></label>
                                <input type="text" name="first_name" required>
                            </div>
                            <div class="form-group">
                                <label>Middle Name <span class="opt">(optional)</span></label>
                                <input type="text" name="middle_name">
                            </div>
                            <div class="form-group">
                                <label>Last Name <span class="req-mark">*</span></label>
                                <input type="text" name="last_name" required>
                            </div>
                            <div class="form-group">
                                <label>Suffix <span class="opt">(optional)</span></label>
                                <input type="text" name="suffix" placeholder="Jr., Sr., III...">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Purok <span class="req-mark">*</span></label>
                                <select name="address_purok" required>
                                    <option value="">Select</option>
                                    <option>Purok 1</option>
                                    <option>Purok 2</option>
                                    <option>Purok 3</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Civil Status <span class="req-mark">*</span></label>
                                <select name="civil_status" required>
                                    <option value="">Select</option>
                                    <option>Single</option>
                                    <option>Married</option>
                                    <option>Widowed</option>
                                    <option>Separated</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Nationality <span class="req-mark">*</span></label>
                                <input type="text" name="nationality" value="Filipino" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Date of Birth <span class="req-mark">*</span></label>
                                <input type="date" name="date_of_birth" required>
                            </div>
                            <div class="form-group">
                                <label>Place of Birth <span class="req-mark">*</span></label>
                                <input type="text" name="place_of_birth" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Mobile Number <span class="req-mark">*</span></label>
                                <input type="tel" name="mobile_number" placeholder="09xxxxxxxxx" pattern="09[0-9]{9}"
                                    maxlength="11" inputmode="numeric"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                                <div class="required-note" style="text-align:left;">
                                    <span class="req-mark">Note:</span>
                                    <small>Kindly input your active/operational cellular number.</small>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Email Address <span class="opt">(optional)</span></label>
                                <input type="email" name="email_address" placeholder="you@example.com">
                            </div>
                        </div>
                    </section>


                    <section class="form-panel" data-step="requestor">
                        <div class="panel-head">
                            <div class="panel-head-icon"><i class="ti ti-user-check"></i></div>
                            <div>
                                <h2>Your Information</h2>
                                <p>Since you're requesting on behalf of the resident, we also need your details.</p>
                            </div>
                        </div>

                        <div class="info-banner">
                            <i class="ti ti-info-circle"></i>
                            <span>Your name and ID will be listed as the requestor on record, alongside the resident's
                                details. You'll also need to upload a document proving your relationship to the
                                resident.</span>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Your First Name <span class="req-mark">*</span></label>
                                <input type="text" name="requestor_first_name" id="requestor_first_name">
                            </div>
                            <div class="form-group">
                                <label>Your Middle Name <span class="opt">(optional)</span></label>
                                <input type="text" name="requestor_middle_name">
                            </div>
                            <div class="form-group">
                                <label>Your Last Name <span class="req-mark">*</span></label>
                                <input type="text" name="requestor_last_name" id="requestor_last_name">
                            </div>
                            <div class="form-group">
                                <label>Suffix <span class="opt">(optional)</span></label>
                                <input type="text" name="requestor_suffix" placeholder="Jr., Sr., III...">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Your Complete Address <span class="req-mark">*</span></label>
                                <input type="text" name="requestor_address" id="requestor_address">
                            </div>
                            <div class="form-group">
                                <label>Your Mobile Number <span class="req-mark">*</span></label>
                                <input type="tel" name="requestor_mobile_number" id="requestor_mobile_number"
                                    placeholder="09xxxxxxxxx" pattern="09[0-9]{9}" maxlength="11" inputmode="numeric"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            </div>
                            <div class="form-group">
                                <label>Your Email Address <span class="opt">(optional)</span></label>
                                <input type="email" name="requestor_email_address" placeholder="you@example.com">
                            </div>
                        </div>

                        <div class="docs-heading">Required Documents</div>
                        <div class="docs-grid">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Copy of Your Valid ID <span class="req-mark">*</span></label>
                                <div class="upload-box" data-upload="requestor_valid_id">
                                    <input type="file" name="requestor_valid_id" id="requestor_valid_id">
                                    <i class="ti ti-cloud-upload"></i>
                                    <div class="upload-title">Click or drag your ID here</div>
                                    <div class="upload-sub">JPG, PNG or PDF, up to 5MB</div>
                                    <div class="upload-filename"><i class="ti ti-file-check"></i><span></span></div>
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Proof of Relationship <span class="req-mark">*</span></label>
                                <div class="upload-box" data-upload="relationship_proof">
                                    <input type="file" name="relationship_proof" id="relationship_proof">
                                    <i class="ti ti-cloud-upload"></i>
                                    <div class="upload-title">Click or drag document here</div>
                                    <div class="upload-sub">e.g. birth or marriage certificate, up to 5MB</div>
                                    <div class="upload-filename"><i class="ti ti-file-check"></i><span></span></div>
                                </div>
                            </div>
                        </div>
                    </section>


                    <section class="form-panel" data-step="documents">
                        <div class="panel-head">
                            <div class="panel-head-icon"><i class="ti ti-clipboard-check"></i></div>
                            <div>
                                <h2>Review &amp; Upload ID</h2>
                                <p>Double-check your details, then attach the resident's valid ID to finish.</p>
                            </div>
                        </div>

                        <div class="review-card">
                            <h3>Summary</h3>
                            <ul class="review-list" id="review-list">

                            </ul>
                        </div>

                        <div class="docs-grid" id="valid_id_section">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Resident's Valid ID <span class="req-mark">*</span></label>
                                <div class="upload-box" data-upload="valid_id">
                                    <input type="file" name="valid_id" id="valid_id">
                                    <i class="ti ti-cloud-upload"></i>
                                    <div class="upload-title">Click or drag ID here</div>
                                    <div class="upload-sub">JPG, PNG or PDF, up to 5MB</div>
                                    <div class="upload-filename"><i class="ti ti-file-check"></i><span></span></div>
                                </div>
                            </div>
                        </div>


                        <div class="type-extra" id="js_section">
                            <div class="docs-heading">First-Time Job Seeker Requirements</div>
                            <div class="form-group">
                                <label>Type of Proof of Identity <span class="req-mark">*</span></label>
                                <select name="js_id_type" id="js_id_type">
                                    <option value="">Select</option>
                                    <option>National ID (PhilID) / ePhilID</option>
                                    <option>Birth Certificate (PSA / Local Civil Registrar)</option>
                                    <option>Student ID</option>
                                    <option>Passport</option>
                                    <option>UMID</option>
                                    <option>Postal ID</option>
                                </select>
                            </div>
                            <div class="docs-grid">
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Proof of Identity <span class="req-mark">*</span></label>
                                    <div class="upload-box" data-upload="js_identity">
                                        <input type="file" name="js_identity" id="js_identity">
                                        <i class="ti ti-cloud-upload"></i>
                                        <div class="upload-title">Click or drag file here</div>
                                        <div class="upload-sub">JPG, PNG or PDF, up to 5MB</div>
                                        <div class="upload-filename"><i class="ti ti-file-check"></i><span></span></div>
                                    </div>
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Proof of Residency (6+ months) <span class="opt">(optional)</span></label>
                                    <div class="upload-box" data-upload="js_residency">
                                        <input type="file" name="js_residency" id="js_residency">
                                        <i class="ti ti-cloud-upload"></i>
                                        <div class="upload-title">Click or drag file here</div>
                                        <div class="upload-sub">Old Barangay ID, voter's certification, or electric/water bill (a parent's name is acceptable)</div>
                                        <div class="upload-filename"><i class="ti ti-file-check"></i><span></span></div>
                                    </div>
                                </div>
                            </div>
                            <label class="privacy-check">
                                <input type="checkbox" name="data_privacy" id="data_privacy" value="1">
                                <span>I agree to the processing of my personal data in accordance with the Data Privacy Act. <span class="req-mark" style="color:#dc2626">*</span></span>
                            </label>
                        </div>
                    </section>

                </div>


                <div class="form-body" style="padding-top:0;">
                    <div class="step-actions" id="step-actions">
                        <button type="button" class="btn btn-ghost" id="btn-back">
                            <i class="ti ti-arrow-left"></i> Back
                        </button>
                        <button type="button" class="btn btn-primary" id="btn-next">
                            Continue <i class="ti ti-arrow-right"></i>
                        </button>
                        <button type="submit" class="btn btn-primary" id="btn-submit" style="display:none;">
                            <i class="ti ti-send"></i> Submit Request
                        </button>
                    </div>
                </div>

            </form>

        </div>

    </main>

    <script>
        window.RESIDENT_INFO = @json($residentInfo, JSON_HEX_TAG);
    </script>
    <script src="{{ asset('script/resident_submit.js') }}"></script>
    <script src="{{ asset('script/logout_animation.js') }}"></script>
</body>

</html>