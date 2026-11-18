var PERSONAL_FIELDS = [
    'first_name', 'middle_name', 'last_name', 'suffix', 'address_purok', 'civil_status',
    'nationality', 'date_of_birth', 'place_of_birth', 'mobile_number', 'email_address'
];
    document.addEventListener('DOMContentLoaded', function () {

    // Collapsible sidebar menu on small screens 
    var toggle = document.getElementById('menu-toggle');
    var nav = document.getElementById('nav-section');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            nav.classList.toggle('open');
        });
    }

    //  Request form 
    var form = document.getElementById('request-form');
    var relationshipSelect = document.getElementById('relationship_to_resident');
    if (form && relationshipSelect) {
        setupRequestForm(form, relationshipSelect);
    }
});

function setupRequestForm(form, relationshipSelect) {

    var RESIDENT_INFO = window.RESIDENT_INFO || {};

        var ALL_STEPS = [
            { key: 'request', label: 'Request' },
            { key: 'relationship', label: 'Who' },
            { key: 'personal', label: 'Resident Info' },
            { key: 'requestor', label: 'Your Info' },
            { key: 'documents', label: 'Documents' }
        ];

        var MAX_FILE_BYTES = 5 * 1024 * 1024; // matches the "up to 5MB" hint and the server check
        var ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'pdf'];

        var requestorRequiredFields = [
            document.getElementById('requestor_first_name'),
            document.getElementById('requestor_last_name'),
            document.getElementById('requestor_address'),
            document.getElementById('requestor_mobile_number'),
            document.getElementById('requestor_valid_id'),
            document.getElementById('relationship_proof')
        ];

        var validIdField = document.getElementById('valid_id');
        var validIdSection = document.getElementById('valid_id_section');

        var autofillNote = document.getElementById('autofill-note');
        var stepperEl = document.getElementById('stepper');

        var panels = {};
        for (var p = 0; p < ALL_STEPS.length; p++) {
            panels[ALL_STEPS[p].key] = form.querySelector('[data-step="' + ALL_STEPS[p].key + '"]');
        }

        var currentIndex = 0;
        var personalInfoState = null;

        var userEditedPersonalInfo = false;
        var requestorInfoInitialized = false;

        var REQUESTOR_INFO_FIELDS = [
            ['requestor_first_name', 'first_name'],
            ['requestor_middle_name', 'middle_name'],
            ['requestor_last_name', 'last_name'],
            ['requestor_suffix', 'suffix'],
            ['requestor_address', 'address_purok'],
            ['requestor_mobile_number', 'mobile_number'],
            ['requestor_email_address', 'email_address']
        ];

         function blockLockedSelect(e) {
        if (this.getAttribute('data-locked') == 'true') e.preventDefault();
    }

    function markEdited() {
        userEditedPersonalInfo = true;
    }

    for (var f = 0; f < PERSONAL_FIELDS.length; f++) {
        var personalField = form.querySelector('[name="' + PERSONAL_FIELDS[f] + '"]');
        if (!personalField) continue;

        if (personalField.tagName == 'SELECT') {
            personalField.addEventListener('mousedown', blockLockedSelect);
            personalField.addEventListener('keydown', blockLockedSelect);
        }
        personalField.addEventListener('input', markEdited);
        personalField.addEventListener('change', markEdited);
    }

        function escapeHtml(str) {
                    str = String(str);
        str = str.replace(/&/g, '&amp;');
        str = str.replace(/</g, '&lt;');
        str = str.replace(/>/g, '&gt;');
        str = str.replace(/"/g, '&quot;');
        str = str.replace(/'/g, '&#39;');
        return str;
    }

    function joinWords(words) {
        var kept = [];
        for (var i = 0; i < words.length; i++) {
            if (words[i]) kept.push(words[i]);
        }
        return kept.join(' ');
        }

        function isSelf() {
            return relationshipSelect.value == 'You' || relationshipSelect.value === '';
        }

        function activeSteps() {
        var steps = [];
        for (var i = 0; i < ALL_STEPS.length; i++) {
            if (ALL_STEPS[i].key == 'requestor' && isSelf()) continue;
            steps.push(ALL_STEPS[i]);
        }
        return steps;
        }

        function renderStepper() {
            var steps = activeSteps();
            var html = '';
                    for (var i = 0; i < steps.length; i++) {
            var state = '';
            if (i < currentIndex) {
                state = 'done';
            } else if (i == currentIndex) {
                state = 'active';
            }

            var bubble = i + 1;
            if (i < currentIndex) {
                bubble = '<i class="ti ti-check"></i>';
            }
                html += '<div class="step-item ' + state + '">' +
                    '<div class="step-bubble">' + bubble + '</div>' +
                    '<div class="step-label">' + steps[i].label + '</div>' +
                    '</div>';
                }
            stepperEl.innerHTML = html;
        }

        function selectedType() {
            var r = document.getElementById('request_type');
                    if (r) {
            return r.value;
        }
        return '';
    }

    function showOrHide(id, show, displayValue) {
        var el = document.getElementById(id);
        if (show) {
            el.style.display = displayValue;
        } else {
            el.style.display = 'none';
        }
    }

    function setRequired(ids, required) {
        for (var i = 0; i < ids.length; i++) {
            document.getElementById(ids[i]).required = required;
        }
    }

        function updateRequestorRequired() {
            var self = isSelf();
            var type = selectedType();
                var isJS = (type == 'First-Time Job Seeker');
                var isBiz = (type == 'Barangay Business Clearance');

            for (var i = 0; i < requestorRequiredFields.length; i++) {
                if (requestorRequiredFields[i]) {
                requestorRequiredFields[i].required = !self;
            }
        }
                if (validIdSection) {
                    if (self && !isJS) {
                        validIdSection.style.display = '';
                } else {
                        validIdSection.style.display = 'none';
                }
        }
        if (validIdField) validIdField.required = (self && !isJS);

            // Job Seeker extras
            showOrHide('js_notice', isJS, 'block');
            showOrHide('js_section', isJS, 'block');
            setRequired(['js_id_type', 'js_identity', 'data_privacy'], isJS);

            // Business Clearance extras
            showOrHide('biz_fields', isBiz, 'block');
            setRequired(['business_name', 'business_nature', 'business_address'], isBiz);
        }

        function syncRequestorInfo() {
            if (isSelf() || requestorInfoInitialized) return;

            for (var i = 0; i < REQUESTOR_INFO_FIELDS.length; i++) {
                var fieldName = REQUESTOR_INFO_FIELDS[i][0];
                var residentField = REQUESTOR_INFO_FIELDS[i][1];
                var field = form.querySelector('[name="' + fieldName + '"]');

                if (field && field.value === '') {
                    field.value = RESIDENT_INFO[residentField] || '';
                }
            }

            requestorInfoInitialized = true;
        }

        function setFieldLocked(field, locked) {
            if (locked) {
                field.classList.add('field-locked');
            } else {
                field.classList.remove('field-locked');
            }
            if (field.tagName == 'SELECT') {
                if (locked) {
                    field.setAttribute('data-locked', 'true');
                } else {
                    field.setAttribute('data-locked', 'false');
                }
            } else {
                field.readOnly = locked;
            }
        }

        function fillPersonalInfoFromAccount() {
            var filledSomething = false;
                    for (var i = 0; i < PERSONAL_FIELDS.length; i++) {
            var name = PERSONAL_FIELDS[i];
            var value = RESIDENT_INFO[name] || '';
            var field = form.querySelector('[name="' + name + '"]');
            if (!field) continue;

            field.value = value;
            var hasValue = (value !== '');
            if (hasValue) filledSomething = true;
            setFieldLocked(field, hasValue && name != 'mobile_number');
        }
        if (autofillNote) {
            if (filledSomething) {
                autofillNote.style.display = 'inline';
            } else {
                autofillNote.style.display = 'none';
            }
        }
        }

        function clearPersonalInfoFields() {
                    for (var i = 0; i < PERSONAL_FIELDS.length; i++) {
            var name = PERSONAL_FIELDS[i];
            var field = form.querySelector('[name="' + name + '"]');
            if (!field) continue;

            if (name == 'nationality') {
                field.value = 'Filipino';
            } else {
                field.value = '';
            }
            setFieldLocked(field, false);
        }
            if (autofillNote) autofillNote.style.display = 'none';
        }

        function syncPersonalInfo() {
            if (isSelf()) {
                if (personalInfoState !== 'self') {
                    if (!userEditedPersonalInfo) {
                        fillPersonalInfoFromAccount();
                    }
                    personalInfoState = 'self';
                }
            } else {
                if (personalInfoState === 'self' && !userEditedPersonalInfo) {
                    clearPersonalInfoFields();
                }
                personalInfoState = 'other';
            }
        }

        function showStep(index) {
            var steps = activeSteps();
            if (index < 0) index = 0;
            if (index > steps.length - 1) index = steps.length - 1;
            currentIndex = index;

            for (var key in panels) {
                panels[key].classList.remove('active');
            }
            panels[steps[currentIndex].key].classList.add('active');

                var isLast = (currentIndex == steps.length - 1);

                if (currentIndex == 0) {
                    document.getElementById('btn-back').style.visibility = 'hidden';
                } else {
                    document.getElementById('btn-back').style.visibility = 'visible';
                }

            if (isLast) {
                document.getElementById('btn-next').style.display = 'none';
                document.getElementById('btn-submit').style.display = 'inline-flex';
                document.getElementById('step-actions').classList.add('is-last');
                buildReview();
            } else {
                document.getElementById('btn-next').style.display = 'inline-flex';
                document.getElementById('btn-submit').style.display = 'none';
                document.getElementById('step-actions').classList.remove('is-last');
            }

            renderStepper();
            window.scrollTo({ top: document.querySelector('.form-card').offsetTop - 20, behavior: 'smooth' });
        }

        function validateCurrentStep() {
            var steps = activeSteps();
            var panel = panels[steps[currentIndex].key];
            var fields = panel.querySelectorAll('input, select, textarea');

            var firstInvalid = null;
            for (var i = 0; i < fields.length; i++) {
                if (!fields[i].checkValidity()) {
                    if (firstInvalid == null) firstInvalid = fields[i];
                fields[i].reportValidity();
            }
        }

        if (firstInvalid != null) {
            firstInvalid.focus();
            return false;
            }
            return true;
        }

        function fieldValue(name) {
            var el = form.querySelector('[name="' + name + '"]');
            if (!el) return '';
            return el.value || '';
        }

        function buildReview() {
            var list = document.getElementById('review-list');
            var full = joinWords([fieldValue('first_name'), fieldValue('middle_name'), fieldValue('last_name'), fieldValue('suffix')]);

            var rows = [
                ['Document', fieldValue('request_type') || '—'],
                ['Resident', full || '—'],
                ['Purok', fieldValue('address_purok') || '—'],
                ['Mobile Number', fieldValue('mobile_number') || '—'],
                ['Relationship', fieldValue('relationship_to_resident') || '—']
            ];

            if (!isSelf()) {
                var reqFull = joinWords([fieldValue('requestor_first_name'), fieldValue('requestor_last_name')]);
                rows.push(['Requested by', reqFull || '—']);
            }

            if (fieldValue('request_type') == 'Barangay Business Clearance') {
                rows.push(['Business', fieldValue('business_name') || '—']);
            }
            if (fieldValue('request_type') == 'First-Time Job Seeker') {
                rows.push(['Proof of Identity', fieldValue('js_id_type') || '—']);
            }

            var html = '';
            for (var i = 0; i < rows.length; i++){
                html += '<li><span class="k">' + escapeHtml(rows[i][0]) + '</span><span class="v">' + escapeHtml(rows[i][1]) + '</span></li>';
            }
            list.innerHTML = html;
        }

        //buttons and form events

        document.getElementById('btn-next').addEventListener('click', function () {
            if (!validateCurrentStep()) return;
            showStep(currentIndex + 1);
        });

        document.getElementById('btn-back').addEventListener('click', function () {
            showStep(currentIndex - 1);
        });

        relationshipSelect.addEventListener('change', function () {
            updateRequestorRequired();
            syncPersonalInfo();
            syncRequestorInfo();
        });

        document.getElementById('request_type').addEventListener('change', updateRequestorRequired);


        form.addEventListener('submit', function (e) {
            if (!validateCurrentStep()) e.preventDefault();
        });


        function setupUploadBox(box) {
            var input = box.querySelector('input[type=file]');
            var nameEl = box.querySelector('.upload-filename span');

            function onFileChange() {
            var file = null;
            if (input.files && input.files.length > 0) {
                file = input.files[0];
            }

                if (file) {
                    var ext = file.name.split('.').pop().toLowerCase();
                    if (ALLOWED_EXT.indexOf(ext) == -1) {
                        alert('Please choose a JPG, PNG, or PDF file.');
                        input.value = '';
                        file = null;
                    } else if (file.size > MAX_FILE_BYTES) {
                        alert('That file is too large. The maximum size is 5MB.');
                        input.value = '';
                        file = null;
                    }
                }

                if (file) {
                    nameEl.textContent = file.name;
                    box.classList.add('has-file');
                } else {
                    nameEl.textContent = '';
                    box.classList.remove('has-file');
                }
            };

            function dragOn(e) {
                    e.preventDefault();
                    box.classList.add('dragover');
            }
            function dragOff(e) {
                    e.preventDefault();
                    box.classList.remove('dragover');
                }
                input.addEventListener('change', onFileChange);
                box.addEventListener('dragover', dragOn);
                box.addEventListener('dragenter', dragOn);
                box.addEventListener('dragleave', dragOff);
                box.addEventListener('drop', dragOff);
                box.addEventListener('drop', function (e) {
                if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
                    input.files = e.dataTransfer.files;
                    onFileChange();
                }
            });
        }

        var uploadBoxes = document.querySelectorAll('.upload-box');
        for (var u = 0; u < uploadBoxes.length; u++){
            setupUploadBox(uploadBoxes[u]);
        }


        form.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                var steps = activeSteps();
                if (currentIndex !== steps.length - 1) {
                    e.preventDefault();
                    document.getElementById('btn-next').click();
                }
            }
        });

        updateRequestorRequired();
        syncPersonalInfo();
        showStep(0);
    }
