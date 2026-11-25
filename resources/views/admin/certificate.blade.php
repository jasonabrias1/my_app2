<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $residentView ? 'View Completed Request' : 'Print Certificate' }} - E-Serbisyo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .paper { max-width: {{ $paper['w'] }}mm; min-height: {{ $paper['h'] }}mm; }
        @media print {
            @page { size: {{ $paper['w'] }}mm {{ $paper['h'] }}mm; margin: 0; }
            .paper {
                zoom: {{ $paper['zoom'] }};
                width: calc(100% / {{ $paper['zoom'] }});
                max-width: none;
                min-height: calc(({{ $paper['h'] }}mm - 2mm) / {{ $paper['zoom'] }});
            }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('stylesheet/admin_certificate.css') }}">
</head>
<body>
    @if ($isSample)
        <div class="sample-flag">
            Preview mode — showing sample/placeholder data (no real request loaded). Open with
            <code>?id=REQUEST_ID</code> from the summary table to print an actual request.
        </div>
    @endif

    <div class="toolbar">
        <div>
            <select id="paperPick" onchange="changePaper(this.value)">
                @foreach ($paperSizes as $name => $size)
                    <option value="{{ $name }}" @selected($name === $paperKey)>
                        {{ $name }} ({{ $size['w'] }} × {{ $size['h'] }} mm)
                    </option>
                @endforeach
            </select>
            <button class="btn-print" onclick="window.print()">🖨️ Print</button>
        </div>
    </div>

    @if ($needsCompletion)
        <div class="sys-overlay" id="sysNotice">
            <div class="sys-box">
                <div class="sys-icon">&#10005;</div>
                <h3>Something went wrong</h3>
                <p id="sysNoticeMsg"></p>
                <button type="button" onclick="document.getElementById('sysNotice').classList.remove('open')">OK</button>
            </div>
        </div>
        <div class="print-overlay" id="printConfirm">
            <div class="print-confirm">
                <div>
                    <p class="pc-title" id="pcTitle">Did the certificate print successfully?</p>
                    <p class="pc-sub" id="pcSub">Confirming will move this request to {{ $isWalkIn ? 'Completed' : 'For Pick Up' }}.</p>
                </div>
                <div class="pc-actions" id="pcAsk">
                    <button type="button" class="no" id="btnNotYet">Not yet</button>
                    <button type="button" id="btnPrinted">Yes, mark {{ $isWalkIn ? 'Completed' : 'For Pick Up' }}</button>
                </div>
                <div class="pc-actions" id="pcLater" style="display:none;">
                    <button type="button" class="no" id="btnCloseConfirm">Close</button>
                    <a href="{{ $dashboardUrl }}" class="pc-link">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    @endif

    <div class="paper">
        @if (file_exists(public_path('logo/HomesteadLetterhead.jpg')))
            <img src="{{ asset('logo/HomesteadLetterhead.jpg') }}" alt="Barangay Homestead Letterhead" class="letterhead-banner">
        @else
            <div class="letterhead">
                @if (file_exists(public_path('logo/MunicipalSeal.jpg')))
                    <img src="{{ asset('logo/MunicipalSeal.jpg') }}" alt="Municipality of Bambang Seal">
                @else
                    <div class="logo-slot">BARANGAY<br>LOGO</div>
                @endif
                <div class="center-text">
                    <p>Republic of the Philippines</p>
                    <p><b>Province of Nueva Vizcaya</b></p>
                    <p>Municipality of Bambang</p>
                    <p class="brgy-name">BARANGAY HOMESTEAD</p>
                </div>
                @if (file_exists(public_path('logo/BarangaySeal.png')))
                    <img src="{{ asset('logo/BarangaySeal.png') }}" alt="Barangay Homestead Official Seal">
                @endif
            </div>
            <div class="office-line-wrap"><span class="office-line">OFFICE OF THE PUNONG BARANGAY</span></div>
        @endif

        @if ($docType === 'indigency')
            <div class="cert-title"><span></span><hr class="cert-title-rule"></div>
            <div class="body-text">
                TO WHOM IT MAY CONCERN:<br><br>
                <b>THIS IS TO CERTIFY</b> that Mr/Mrs/Ms <span class="fillable">{{ $fullName }}</span>,
                citizenship <span class="fillable">{{ $data['citizenship'] ?? 'Filipino' }}</span>,
                civil status <span class="fillable">{{ $data['civil_status'] ?? '' }}</span>
                is a bona fide resident of So. <span class="fillable">{{ $data['address_purok'] ?? '' }}</span>,
                Homestead, Bambang, Nueva Vizcaya.<br><br>
                <b>TO CERTIFY FURTHER</b> that according to assessment made by this office, the above-mentioned
                name was found a member of <b>INDIGENT</b> people and presently residing at this Barangay.<br><br>
                This certification is issued upon the request of
                <span class="fillable">{{ $data['requested_by'] }}</span> for the purpose indicated below.<br><br>
                <b>ISSUED</b> this {{ $issueDay }} day of {{ $issueMonth }} {{ $issueYear }}
                at Barangay Homestead, Bambang, Nueva Vizcaya.
            </div>
            <div class="purpose-block">
                <div class="label">Purpose/s: (check the appropriate box)</div>
                @php($purposeOptions = ['Medical Assistance', 'Financial Assistance', 'Educational Assistance', 'Burial Assistance'])
                @foreach ($purposeOptions as $option)
                    <label><input type="checkbox" @checked(stripos($selectedPurpose, $option) !== false) disabled> {{ $option }}</label>
                @endforeach
                <label>
                    <input type="checkbox" @checked(! in_array($selectedPurpose, $purposeOptions, true) && $selectedPurpose !== '') disabled>
                    Others <i>(please specify)</i>
                    <span class="fillable" style="min-width:200px;">
                        {{ ! in_array($selectedPurpose, $purposeOptions, true) ? $selectedPurpose : '' }}
                    </span>
                </label>
            </div>
        @elseif ($docType === 'clearance')
            <div class="cert-title"><span></span><hr class="cert-title-rule"></div>
            <div class="body-text">
                TO WHOM IT MAY CONCERN:<br><br>
                <b>THIS IS TO CERTIFY</b> that <span class="fillable">{{ $fullName }}</span> a bona fide
                resident of Barangay Homestead, Bambang, Nueva Vizcaya.<br><br>
                <b>TO CERTIFY FURTHER</b> that he/she is presently residing in this barangay and is known to be
                a person with <b>good moral character</b> and no derogatory record.<br><br>
                This Clearance is issued upon the request of the above-mentioned named for
                <span class="fillable">{{ $selectedPurpose !== '' ? $selectedPurpose : 'ANY LEGAL purposes' }}</span>.<br><br>
                <b>ISSUED</b> this {{ $issueDay }} day of {{ strtoupper($issueMonth) }} {{ $issueYear }}
                at Barangay Homestead, Bambang, Nueva Vizcaya.
            </div>
        @elseif ($docType === 'residency')
            <div class="cert-title"><span>CERTIFICATE OF RESIDENCY</span><hr class="cert-title-rule"></div>
            <div class="body-text">
                TO WHOM IT MAY CONCERN:<br><br>
                <b>THIS IS TO CERTIFY</b> that <span class="fillable">{{ $fullName }}</span> is a bona fide
                resident of <span class="fillable">{{ $data['purok_label'] }}</span>,
                Barangay Homestead, Bambang, Nueva Vizcaya.<br><br>
                <b>TO CERTIFY FURTHER</b> that he/she is known to this office to be presently residing in this
                barangay and of good standing in this community.<br><br>
                This certification is issued upon the request of the above-named for
                <span class="fillable">{{ $purposeOrDefault }}</span>.<br><br>
                <b>ISSUED</b> this {{ $issueDay }} day of {{ $issueMonth }} {{ $issueYear }}
                at Barangay Homestead, Bambang, Nueva Vizcaya.
            </div>
        @elseif ($docType === 'business_permit')
            <div class="cert-title"><span>BARANGAY BUSINESS CLEARANCE</span><hr class="cert-title-rule"></div>
            <div class="body-text">
                TO WHOM IT MAY CONCERN:<br><br>
                <b>THIS IS TO CERTIFY</b> that the business named
                <span class="fillable" style="min-width:180px;">{{ $businessName }}</span>,
                owned/operated by <span class="fillable">{{ $fullName }}</span>, located at
                <span class="fillable">{{ $businessAddress }}</span>, Barangay Homestead, Bambang, Nueva Vizcaya,
                is hereby granted clearance to operate.<br><br>
                <b>TO CERTIFY FURTHER</b> that the said business has been found compliant with barangay ordinances
                and regulations, and that the owner/operator is presently residing in this barangay with no
                derogatory record on file.<br><br>
                This clearance is issued upon request of the above-named owner/operator for
                <span class="fillable">{{ $selectedPurpose !== '' ? $selectedPurpose : 'application of Business Permit' }}</span>.<br><br>
                <b>ISSUED</b> this {{ $issueDay }} day of {{ $issueMonth }} {{ $issueYear }}
                at Barangay Homestead, Bambang, Nueva Vizcaya.
            </div>
        @elseif ($docType === 'jobseeker')
            <div class="cert-title"><span>CERTIFICATE OF FIRST TIME JOBSEEKER</span><hr class="cert-title-rule"></div>
            <div class="body-text">
                TO WHOM IT MAY CONCERN:<br><br>
                <b>THIS IS TO CERTIFY</b> that <span class="fillable">{{ $fullName }}</span>, a resident of
                <span class="fillable">{{ $data['purok_label'] }}</span>, Barangay Homestead, Bambang,
                Nueva Vizcaya, is a <b>FIRST TIME JOBSEEKER</b> as defined under Republic Act No. 11261,
                otherwise known as the "First Time Jobseekers Assistance Act."<br><br>
                <b>TO CERTIFY FURTHER</b> that he/she is presently residing in this barangay and that this
                certification is issued for the purpose of availing the benefits granted under said Act,
                particularly exemption from fees in securing government documents required for employment purposes.<br><br>
                <b>ISSUED</b> this {{ $issueDay }} day of {{ $issueMonth }} {{ $issueYear }}
                at Barangay Homestead, Bambang, Nueva Vizcaya.
            </div>
        @elseif ($docType === 'barangay_certificate')
            <div class="cert-title"><span>BARANGAY CERTIFICATION</span><hr class="cert-title-rule"></div>
            <div class="body-text">
                TO WHOM IT MAY CONCERN:<br><br>
                <b>THIS IS TO CERTIFY</b> that <span class="fillable">{{ $fullName }}</span> is a bona fide
                resident of <span class="fillable">{{ $data['purok_label'] }}</span>, Barangay Homestead,
                Bambang, Nueva Vizcaya.<br><br>
                <b>TO CERTIFY FURTHER</b> that he/she is presently residing in this barangay.<br><br>
                This certification is issued upon the request of the above-named person for
                <span class="fillable">{{ $purposeOrDefault }}</span>.<br><br>
                <b>ISSUED</b> this {{ $issueDay }} day of {{ $issueMonth }} {{ $issueYear }}
                at Barangay Homestead, Bambang, Nueva Vizcaya.
            </div>
        @else
            <div class="cert-title"><span>CERTIFICATE OF GOOD MORAL CHARACTER</span><hr class="cert-title-rule"></div>
            <div class="body-text">
                TO WHOM IT MAY CONCERN:<br><br>
                <b>THIS IS TO CERTIFY</b> that <span class="fillable">{{ $fullName }}</span>, a resident of
                Barangay Homestead, Bambang, Nueva Vizcaya, is presently residing in this barangay and is known
                to this office to be of <b>good moral character</b> and has no derogatory record on file.<br><br>
                This certification is issued upon the request of the above-named for
                <span class="fillable">{{ $purposeOrDefault }}</span>.<br><br>
                <b>ISSUED</b> this {{ $issueDay }} day of {{ $issueMonth }} {{ $issueYear }}
                at Barangay Homestead, Bambang, Nueva Vizcaya.
            </div>
        @endif

        <div class="signature-block">
            <div class="signature-space"></div>
            <div class="signature-name">JERRY D. VILLANUEVA</div>
            <div class="signature-role">Punong Barangay</div>
            <div class="not-valid">Not Valid Without Seal</div>
        </div>
        <div class="bottom-block">
            <div>Control #: <span class="fillable">{{ $data['tracking_code'] ?? '' }}</span></div>
        </div>
        <hr class="address-rule">
        <div class="address-footer">Boyle St. Purok 2, Homestead, Bambang, Nueva Vizcaya</div>
    </div>

    @if ($needsCompletion)
        <script>
            var CERT_REQUEST_ID = {{ $requestId }};
            var csrfToken = @json(csrf_token());
            var markPrintedUrl = @json($markPrintedUrl);
            var dashboardUrl = @json($dashboardUrl);
            var pcBox = document.getElementById('printConfirm');
            var pcAskRow = document.getElementById('pcAsk');
            var pcLaterRow = document.getElementById('pcLater');
            var pcShown = false;

            function pcShowAsk() {
                document.getElementById('pcTitle').textContent = 'Did the certificate print successfully?';
                document.getElementById('pcSub').textContent = 'Confirming will move this request to {{ $isWalkIn ? 'Completed' : 'For Pick Up' }}.';
                pcAskRow.style.display = '';
                pcLaterRow.style.display = 'none';
            }
            function pcShowLater() {
                document.getElementById('pcTitle').textContent = 'No problem';
                document.getElementById('pcSub').textContent = 'This request stays as it is. You can print it again anytime.';
                pcAskRow.style.display = 'none';
                pcLaterRow.style.display = '';
            }
            function pcShowNotice(message) {
                document.getElementById('sysNoticeMsg').textContent = message;
                document.getElementById('sysNotice').classList.add('open');
            }
            function pcOpen() {
                if (pcShown) return;
                pcShown = true;
                pcShowAsk();
                pcBox.classList.add('show');
                window.scrollTo(0, 0);
            }
            if (pcBox) {
                window.addEventListener('afterprint', pcOpen);
                var printButton = document.querySelector('.btn-print');
                if (printButton) {
                    printButton.addEventListener('click', function () {
                        pcShown = false;
                        setTimeout(pcOpen, 800);
                    });
                }
                document.getElementById('btnNotYet').addEventListener('click', pcShowLater);
                document.getElementById('btnCloseConfirm').addEventListener('click', function () {
                    pcBox.classList.remove('show');
                    pcShown = false;
                    pcShowAsk();
                });
                document.getElementById('btnPrinted').addEventListener('click', function () {
                    var button = this;
                    button.disabled = true;
                    button.textContent = 'Saving...';
                    var formData = new FormData();
                    formData.append('_token', csrfToken);
                    formData.append('action', 'mark_printed');
                    formData.append('id', CERT_REQUEST_ID);
                    fetch(markPrintedUrl, { method: 'POST', body: formData })
                        .then(function (response) {
                            if (!response.ok) throw new Error('Request failed');
                            return response.json();
                        })
                        .then(function (result) {
                            if (!result.ok) throw new Error('Could not update');
                            try {
                                if (window.opener && !window.opener.closed) window.opener.location.reload();
                            } catch (error) {}
                            window.location.href = dashboardUrl;
                        })
                        .catch(function () {
                            pcShowNotice('Could not update the status. Please try again.');
                            button.disabled = false;
                            button.textContent = 'Yes, mark {{ $isWalkIn ? 'Completed' : 'For Pick Up' }}';
                        });
                });
            }
            function changePaper(value) {
                var query = new URLSearchParams(window.location.search);
                query.set('paper', value);
                window.location.search = query.toString();
            }
        </script>
    @else
        <script>
            function changePaper(value) {
                var query = new URLSearchParams(window.location.search);
                query.set('paper', value);
                window.location.search = query.toString();
            }
        </script>
    @endif
</body>
</html>
