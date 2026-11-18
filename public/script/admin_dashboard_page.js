// Opens the certificate only.
function printCertificate(id) {
    window.open(window.ADMIN_SUMMARY_URL + '?id=' + id, '_blank');
}

// For Pick Up tab
function changeRequestStatus(selectEl, code) {
    if (selectEl.value == 'For Pick Up') return;
    var label = selectEl.options[selectEl.selectedIndex].text;
    showConfirm(
        'Move ' + code + ' to "' + label + '"?',
        function () { selectEl.form.submit(); }, // don't disable the select: disabled fields aren't submitted
        function () { selectEl.value = 'For Pick Up'; },
        'Yes, move it'
    );
}


var SYS_ICONS = { success: 'fa-check', error: 'fa-xmark', info: 'fa-circle-info', confirm: 'fa-question' };
var SYS_TITLES = { success: 'Success', error: 'Something went wrong', info: 'Notice', confirm: 'Please confirm' };

function closeSysDialog() {
    document.getElementById('sysDialog').classList.remove('open');
}

// Creates one button of the dialog
function addDialogButton(actions, b) {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = b.text;
    if (b.cancel) btn.className = 'sys-cancel';
    btn.addEventListener('click', function () {
        closeSysDialog();
        if (b.action) b.action();
    });
    actions.appendChild(btn);
}
function openSysDialog(type, message, title, buttons) {
    var icon = document.getElementById('sysIcon');
    icon.className = 'sys-icon ' + type;
    icon.innerHTML = '<i class="fas ' + SYS_ICONS[type] + '"></i>';
    document.getElementById('sysTitle').textContent = title || SYS_TITLES[type];
    document.getElementById('sysMsg').textContent = message;

    var actions = document.getElementById('sysActions');
    actions.innerHTML = '';
    for (var i = 0; i < buttons.length; i++) {
        addDialogButton(actions, buttons[i]);
    }
    document.getElementById('sysDialog').classList.add('open');
}

function showNotice(message, type, title) {
    openSysDialog(type || 'info', message, title, [{ text: 'OK' }]);
}

function showConfirm(message, onYes, onNo, yesText) {
    openSysDialog('confirm', message, null, [
        { text: 'Cancel', cancel: true, action: onNo },
        { text: yesText || 'Yes', action: onYes }
    ]);
}

// Sends an SMS to the resident on demand
var NOTIFIED_KEY = 'notificationCooldowns';
var NOTIFY_COOLDOWN_MS = 5 * 60 * 1000;
function getNotified() {
    try {
        var stored = JSON.parse(localStorage.getItem(NOTIFIED_KEY));
        return stored && typeof stored === 'object' && !Array.isArray(stored) ? stored : {};
    } catch (e) { return {}; }
}
function saveNotified(id) {
    try {
        var notifications = getNotified();
        notifications[id] = Date.now();
        localStorage.setItem(NOTIFIED_KEY, JSON.stringify(notifications));
    } catch (e) { }
}
function clearNotified(id) {
    try {
        var notifications = getNotified();
        delete notifications[id];
        localStorage.setItem(NOTIFIED_KEY, JSON.stringify(notifications));
    } catch (e) { }
}
function setNotifiedState(btn, sentAt) {
    var id = parseInt(btn.getAttribute('data-id'), 10);
    var remaining = NOTIFY_COOLDOWN_MS - (Date.now() - sentAt);
    if (remaining <= 0) {
        btn.disabled = false;
        btn.classList.remove('notified');
        btn.innerHTML = '<i class="fas fa-comment-sms"></i> Notify';
        clearNotified(id);
        return;
    }

    var seconds = Math.ceil(remaining / 1000);
    var minutes = Math.floor(seconds / 60);
    var remainder = String(seconds % 60).padStart(2, '0');
    btn.disabled = true;
    btn.classList.add('notified');
    btn.innerHTML = '<i class="fas fa-circle-check"></i> Remind in ' + minutes + ':' + remainder;
    window.setTimeout(function () {
        setNotifiedState(btn, sentAt);
    }, Math.min(1000, remaining));
}
var notifyButtons = document.querySelectorAll('.btn-notify');
var notifiedRequests = getNotified();
for (var notifyIndex = 0; notifyIndex < notifyButtons.length; notifyIndex++) {
    var notifyButton = notifyButtons[notifyIndex];
    var notifyId = parseInt(notifyButton.getAttribute('data-id'), 10);
    var notifiedAt = Number(notifiedRequests[notifyId]);
    if (notifiedAt > 0) {
        setNotifiedState(notifyButton, notifiedAt);
    }
}
function finishNotify(btnEl, sent, originalHTML) {
    if (sent) {
        setNotifiedState(btnEl, Date.now());
    }else{
        btnEl.disabled = false;
        btnEl.innerHTML = originalHTML;
    }
}
function notifyResident(id, btnEl) {
    var originalHTML = btnEl.innerHTML;
    btnEl.disabled = true;
    btnEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

    var formData = new FormData();
    formData.append('request_id', id);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

    fetch(window.ADMIN_NOTIFICATION_URL, { method: 'POST', body: formData })
        .then(function (res) { return res.text(); })
        .then(function (txt) {
            var data;
            try {
                data = JSON.parse(txt);
            } catch (e) {
                showNotice('Server error (hindi JSON ang sagot):\n\n' + txt.substring(0, 500), 'error');
                finishNotify(btnEl, false, originalHTML);
                return;
            }
            var msg = data.message || '';
            if (data.success) {
                saveNotified(id);
                showNotice(msg, 'success');
                finishNotify(btnEl, true, originalHTML);
            } else {
                showNotice(msg, 'error');
                finishNotify(btnEl, false, originalHTML);
            }
        })
        .catch(function () {
            showNotice('Hindi ma-reach ang server. Subukan ulit.', 'error');
            finishNotify(btnEl, false, originalHTML);
        });
}


var HANDLED_KEYS = [
    'id', 'resident_id', 'first_name', 'middle_name', 'last_name', 'suffix',
    'address_street', 'address_block', 'address_purok', 'residency_duration', 'residency_unit',
    'attachment_photo', 'civil_status', 'nationality', 'date_of_birth', 'place_of_birth',
    'mobile_number', 'email_address', 'document_type', 'purpose', 'status', 'request_date', 'tracking_code'
];

function formatDetailDate(value) {
    if (!value) return value;
    var d = new Date(value);
    if (isNaN(d.getTime())) return value;
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function joinParts(parts, separator) {
   var kept = [];
   for (var i = 0; i < parts.length; i++) {
    if (parts[i] && parts[i].trim() !== '') {
        kept.push(parts[i]);
    }
   }
   return kept.join(separator);
}

function titleCase(key) {
    var words = key.split('_');
    for (var i = 0; i < words.length; i++) {
        words[i] = words[i].charAt(0).toUpperCase() + words[i].slice(1);
    }
    return words.join(' ');
}

function keepDefined(rows) {
    var kept = [];
    for (var i = 0; i < rows.length; i++) {
        if (rows[i].value !== undefined) {
            kept.push(rows[i]);
        }
    }
    return kept;
}
function addDetailField(grid, key, label, rawValue, fullWidth) {
    var value = rawValue;
    if (key == 'date_of_birth' || key == 'request_date') {
        value = formatDetailDate(rawValue);
    }
    var field = document.createElement('div');
    field.className = 'detail-field';
    if (fullWidth) field.className = 'detail-field full-width';
    var labelDiv = document.createElement('div');
    labelDiv.className = 'detail-field-label';
    labelDiv.textContent = label;
    var valueDiv = document.createElement('div');
    valueDiv.className = 'detail-field-value';
    if (value === null || value === undefined || value === '') {
        valueDiv.textContent = '-';
    }else{
        valueDiv.textContent = value;
    }
    field.appendChild(labelDiv);
    field.appendChild(valueDiv);
    grid.appendChild(field);
}

function addDetailSection(body, title, icon, rows) {
    if (!rows.length) return;
    var section = document.createElement('div');
    section.className = 'detail-section';
    var titleDiv = document.createElement('div');
    titleDiv.className = 'detail-section-title';
    titleDiv.innerHTML = '<i class="fas ' + icon + '"></i> ' + title;
    var grid = document.createElement('div');
    grid.className = 'detail-grid';
    for (var i = 0; i < rows.length; i++) {
        addDetailField(grid, rows[i].key, rows[i].label, rows[i].value, rows[i].fullWidth);
    }
    section.appendChild(titleDiv);
    section.appendChild(grid);
    body.appendChild(section);
}

function addAttachmentSection(body, filename) {
    if (!filename) return;
    var section = document.createElement('div');
    section.className = 'detail-section';

    var titleDiv = document.createElement('div');
    titleDiv.className = 'detail-section-title';
    titleDiv.innerHTML = '<i class="fas fa-id-card"></i> Valid ID Submitted';
    section.appendChild(titleDiv);

    var wrap = document.createElement('div');
    wrap.style.marginTop = '8px';

    var src = window.ADMIN_ATTACHMENT_BASE + '/' + encodeURIComponent(filename);
    var img = document.createElement('img');
    img.src = src;
    img.alt = 'Submitted Valid ID';
    img.style.cssText = 'max-width:100%;max-height:360px;border-radius:8px;border:1px solid #ddd;display:block;margin:0 auto;cursor:zoom-in;';
    img.onerror = function () {
        var fullUrl = img.src;
        wrap.innerHTML = '<div class="empty-state" style="padding:12px;text-align:left;">'
            + '<i class="fas fa-triangle-exclamation"></i> Could not load the attachment.<br>'
            + '<span style="font-size:12px;color:#94a3b8;">Tried: <a href="' + fullUrl + '" target="_blank">' + fullUrl + '</a><br>'
            + 'Open that link directly — if it 404s, the file isn\'t at that path on the server.</span></div>';
    };
    img.onclick = function () { window.open(src, '_blank'); };

    wrap.appendChild(img);
    section.appendChild(wrap);
    body.appendChild(section);
}

function openDetailsModal(btn) {
    var data = JSON.parse(btn.getAttribute('data-details'));
    var body = document.getElementById('modalBody');
    body.innerHTML = '';

    document.getElementById('modalTitle').textContent = data.document_type || 'Request Details';
    var subtitle = document.getElementById('modalSubtitle');
    subtitle.innerHTML = '';
    if (data.status) {
        var badge = document.createElement('span');
        badge.className = 'badge ' + (STATUS_BADGE_CLASS[data.status] || '');
        badge.textContent = STATUS_LABELS[data.status] || data.status;
        subtitle.appendChild(badge);
    }
    var metaBits = [];
    if (data.tracking_code) metaBits.push(data.tracking_code);
    if (data.request_date) metaBits.push('Filed ' + formatDetailDate(data.request_date));
    if (metaBits.length) {
        var metaSpan = document.createElement('span');
        metaSpan.textContent = metaBits.join(' • ');
        subtitle.appendChild(metaSpan);
    }

    addDetailSection(body, 'Requestor Information', 'fa-user', keepDefined([
        { key: 'full_name', label: 'Full Name', fullWidth: true, value: joinParts([data.first_name, data.middle_name, data.last_name, data.suffix], ' ')},
        { key: 'civil_status', label: 'Civil Status', value: data.civil_status },
        { key: 'nationality', label: 'Nationality', value: data.nationality },
        { key: 'date_of_birth', label: 'Date of Birth', value: data.date_of_birth },
        { key: 'place_of_birth', label: 'Place of Birth', value: data.place_of_birth }
    ]));

    addDetailSection(body, 'Address', 'fa-location-dot', [
        { key: 'full_address', label: 'Complete Address', value: joinParts([data.address_street, data.address_block, data.address_purok], ', '), fullWidth: true }
    ]);

    addDetailSection(body, 'Contact Information', 'fa-phone', keepDefined([
        { key: 'mobile_number', label: 'Mobile Number', value: data.mobile_number },
        { key: 'email_address', label: 'Email Address', value: data.email_address }
    ]));

    addDetailSection(body, 'Request Information', 'fa-file-lines', keepDefined([
        { key: 'document_type', label: 'Document Type', value: data.document_type },
        { key: 'purpose', label: 'Purpose', value: data.purpose, fullWidth: true },
        { key: 'status', label: 'Status', value: STATUS_LABELS[data.status] || data.status },
        { key: 'request_date', label: 'Date Filed', value: data.request_date },
        { key: 'tracking_code', label: 'Tracking Code', value: data.tracking_code }
    ]));

    addAttachmentSection(body, data.attachment_photo);

    document.getElementById('detailsModal').classList.add('open');
}

function closeDetailsModal() {
    document.getElementById('detailsModal').classList.remove('open');
}

function closeDetailsModalOnOverlay(e) {
    if (e.target.id == 'detailsModal') closeDetailsModal();
}