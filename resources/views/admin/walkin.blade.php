<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Walk-in Request - E-Serbisyo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('stylesheet/admin_dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('stylesheet/admin_create_account.css') }}">
    <style>
        .walkin-extra { display: none; }
        .walkin-extra.show { display: block; }
        .walkin-note { background: var(--blue-bg, #eff6ff); border-radius: 10px; padding: 10px 14px; font-size: 13px; margin-bottom: 14px; }
        .walkin-done { border-left: 4px solid var(--green, #16a34a); margin-bottom: 16px; }
        .walkin-done .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
        .walkin-done .actions a { text-decoration: none; }
        .walkin-form textarea, .walkin-form select { width: 100%; box-sizing: border-box; font: inherit; }
        .walkin-form textarea { padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; resize: vertical; }
        .walkin-privacy { display: flex; gap: 8px; align-items: flex-start; font-size: 13px; margin-top: 8px; }
    </style>
</head>

<body>
    @if (\Illuminate\Support\Facades\View::exists('admin.partials.sidebar'))
        @include('admin.partials.sidebar', ['counts' => $counts, 'total_residents' => $total_residents, 'admin_name' => $admin_name])
    @else
        <script src="{{ asset('script/admin_sidebar.js') }}"></script>
    @endif

    <div class="main">
        <div class="topbar">
            <h2>Walk-in Request</h2>
            <div class="who">Signed in as <b>{{ $admin_name }}</b></div>
        </div>

        @if ($done)
            <div class="panel walkin-done">
                <h3>Walk-in request saved</h3>
                <p style="font-size:13.5px;margin:6px 0 0;">
                    <b>{{ $done['doc'] }}</b> for <b>{{ $done['name'] }}</b> has been recorded.<br>
                    Tracking code: <b>{{ $done['code'] }}</b>
                </p>
                <div class="actions">
                    <a class="btn"
                       href="{{ \Illuminate\Support\Facades\Route::has('admin.summary') ? route('admin.summary', ['id' => $done['id']]) : url('/admin/summary?id=' . (int) $done['id']) }}"
                       target="_blank" rel="noopener">
                        <i class="fas fa-print"></i> View / Print certificate
                    </a>
                </div>
            </div>
        @endif

        <div class="panel form-grid walkin-form">
            <h3></h3>
            @if ($errors->any())
                <div class="error-list">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" id="walkinForm"
                  action="{{ \Illuminate\Support\Facades\Route::has('admin.walkin.store') ? route('admin.walkin.store') : url('/admin/walkin') }}">
                @csrf

                <div class="form-section-title">Document</div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Type of Document <span class="req-mark">*</span></label>
                        <select name="request_type" id="request_type" required>
                            <option value="">Select</option>
                            @foreach ($document_types as $document)
                                <option value="{{ $document }}" @selected($values['request_type'] === $document)>{{ $document }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Relationship to the Resident <span class="req-mark">*</span></label>
                        <input type="hidden" name="relationship_to_resident" id="relationship_to_resident" value="You">
                        <input type="text" value="Resident (self)" readonly>
                    </div>
                </div>

                <div class="walkin-extra" id="biz_fields">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Business Name <span class="req-mark">*</span></label>
                            <input type="text" name="business_name" value="{{ $values['business_name'] }}">
                        </div>
                        <div class="form-group">
                            <label>Nature of Business <span class="req-mark">*</span></label>
                            <input type="text" name="business_nature" value="{{ $values['business_nature'] }}" placeholder="e.g. Sari-sari store">
                        </div>
                        <div class="form-group">
                            <label>Business Address <span class="req-mark">*</span></label>
                            <input type="text" name="business_address" value="{{ $values['business_address'] }}">
                        </div>
                    </div>
                </div>

                <div class="walkin-extra" id="js_fields">
                    <div class="walkin-note">First-Time Job Seeker (RA 11261): the resident must be present to sign the Oath of Undertaking.</div>
                    <div class="form-group">
                        <label>Type of Proof of Identity Presented <span class="req-mark">*</span></label>
                        <select name="js_id_type">
                            <option value="">Select</option>
                            @foreach ($job_seeker_id_types as $type)
                                <option value="{{ $type }}" @selected($values['js_id_type'] === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="walkin-privacy">
                        <input type="checkbox" name="data_privacy" value="1" @checked($data_privacy)>
                        <span>The resident agrees to the processing of their personal data in accordance with the Data Privacy Act. <span class="req-mark">*</span></span>
                    </label>
                </div>

                <div class="form-group">
                    <label>Purpose <span class="req-mark">*</span></label>
                    <textarea name="purpose" rows="2" placeholder="e.g. Employment requirement, school enrollment..." required>{{ $values['purpose'] }}</textarea>
                </div>

                <div class="form-section-title">Resident's Information</div>

                <div class="form-row">
                    <div class="form-group">
                        <label>First Name <span class="req-mark">*</span></label>
                        <input type="text" name="first_name" value="{{ $values['first_name'] }}" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Name <span class="opt">(optional)</span></label>
                        <input type="text" name="middle_name" value="{{ $values['middle_name'] }}">
                    </div>
                    <div class="form-group">
                        <label>Last Name <span class="req-mark">*</span></label>
                        <input type="text" name="last_name" value="{{ $values['last_name'] }}" required>
                    </div>
                    <div class="form-group">
                        <label>Suffix <span class="opt">(optional)</span></label>
                        <input type="text" name="suffix" value="{{ $values['suffix'] }}" placeholder="Jr., Sr., III...">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Purok <span class="req-mark">*</span></label>
                        <select name="address_purok" required>
                            <option value="">Select</option>
                            @foreach ($puroks as $purok)
                                <option value="{{ $purok }}" @selected($values['address_purok'] === $purok)>{{ $purok }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Civil Status <span class="req-mark">*</span></label>
                        <select name="civil_status" required>
                            <option value="">Select</option>
                            @foreach ($civil_statuses as $status)
                                <option value="{{ $status }}" @selected($values['civil_status'] === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nationality <span class="req-mark">*</span></label>
                        <input type="text" name="nationality" value="{{ $values['nationality'] }}" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Birth <span class="req-mark">*</span></label>
                        <input type="date" name="date_of_birth" value="{{ $values['date_of_birth'] }}" required>
                    </div>
                    <div class="form-group">
                        <label>Place of Birth <span class="req-mark">*</span></label>
                        <input type="text" name="place_of_birth" value="{{ $values['place_of_birth'] }}" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Mobile Number <span class="opt">(optional)</span></label>
                        <input type="tel" name="mobile_number" value="{{ $values['mobile_number'] }}" placeholder="09xxxxxxxxx"
                            pattern="09[0-9]{9}" maxlength="11" inputmode="numeric"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        <small style="color:var(--muted);font-size:11.5px;">Needed to send the SMS when the document is ready.</small>
                    </div>
                    <div class="form-group">
                        <label>Email Address <span class="opt">(optional)</span></label>
                        <input type="email" name="email_address" value="{{ $values['email_address'] }}" placeholder="you@example.com">
                    </div>
                </div>

                <div class="walkin-extra" id="requestor_fields">
                    <div class="form-section-title">Requestor's Information</div>
                    <div class="walkin-note">The person claiming the document on the resident's behalf.</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name <span class="req-mark">*</span></label>
                            <input type="text" name="requestor_first_name" value="{{ $values['requestor_first_name'] }}">
                        </div>
                        <div class="form-group">
                            <label>Middle Name <span class="opt">(optional)</span></label>
                            <input type="text" name="requestor_middle_name" value="{{ $values['requestor_middle_name'] }}">
                        </div>
                        <div class="form-group">
                            <label>Last Name <span class="req-mark">*</span></label>
                            <input type="text" name="requestor_last_name" value="{{ $values['requestor_last_name'] }}">
                        </div>
                        <div class="form-group">
                            <label>Suffix <span class="opt">(optional)</span></label>
                            <input type="text" name="requestor_suffix" value="{{ $values['requestor_suffix'] }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Complete Address <span class="req-mark">*</span></label>
                            <input type="text" name="requestor_address" value="{{ $values['requestor_address'] }}">
                        </div>
                        <div class="form-group">
                            <label>Mobile Number <span class="req-mark">*</span></label>
                            <input type="tel" name="requestor_mobile_number" value="{{ $values['requestor_mobile_number'] }}"
                                placeholder="09xxxxxxxxx" pattern="09[0-9]{9}" maxlength="11" inputmode="numeric"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        <div class="form-group">
                            <label>Email Address <span class="opt">(optional)</span></label>
                            <input type="email" name="requestor_email_address" value="{{ $values['requestor_email_address'] }}">
                        </div>
                    </div>
                </div>

                <div class="form-submit-row">
                    <button type="submit" class="btn btn-centered">Save Walk-in Request</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        var typeSel = document.getElementById('request_type');
        var relSel = document.getElementById('relationship_to_resident');

        function syncWalkinFields() {
            document.getElementById('biz_fields').classList.toggle('show', typeSel.value === 'Barangay Business Clearance');
            document.getElementById('js_fields').classList.toggle('show', typeSel.value === 'First-Time Job Seeker');
            document.getElementById('requestor_fields').classList.toggle('show', relSel.value !== 'You');
        }

        typeSel.addEventListener('change', syncWalkinFields);
        relSel.addEventListener('change', syncWalkinFields);
        syncWalkinFields();
    </script>
</body>

</html>
