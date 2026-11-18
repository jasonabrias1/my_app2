var RESIDENT_LABELS = {
    fullname: 'Full Name',
    first_name: 'First Name',
    middle_name: 'Middle Name',
    last_name: 'Last Name',
    suffix: 'Suffix',
    email: 'Email Address',
    mobile_number: 'Mobile Number',
    address_street: 'Address (Street)',
    address_block: 'Address (Block)',
    address_purok: 'Address (Purok)',
    civil_status: 'Civil Status',
    nationality: 'Nationality',
    date_of_birth: 'Date of Birth',
    place_of_birth: 'Place of Birth',
    created_at: 'Created At'
};

function humanizeKey(key) {
    var words = key.split('_');
    for (var i = 0; i < words.length; i++) {
        words[i] = words[i].charAt(0).toUpperCase() + words[i].slice(1);
    }
    return words.join(' ');
}

function formatResidentDate(value) {
    if (!value) return value;
    var d = new Date(value);
    if (isNaN(d.getTime())) return value;
    return d.toLocaleString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: '2-digit' });
}

function formatResidentBirthDate(value) {
    if (!value) return value;
    var d = new Date(value);
    if (isNaN(d.getTime())) return value;
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function joinResidentParts(parts, separator) {
    var kept = [];
    for (var i = 0; i < parts.length; i++) {
        if (parts[i]) {
            kept.push(parts[i]);
        }
    }
    return kept.join(separator);
}

function buildResidentFullName(data) {
    if (data.fullname) return data.fullname;
    return joinResidentParts([data.first_name, data.middle_name, data.last_name, data.suffix || data.suffix_name], ' ');
}

function buildResidentAddress(data) {
    return joinResidentParts([
        data.address_street,
        data.address_block,
        data.address_purok,
        'Homestead',
        'Bambang',
        'Nueva Vizcaya'
    ], ', ');
}

function addResidentField(grid, label, rawValue, wide) {
    var field = document.createElement('div');
    field.className = 'detail-field' + (wide ? ' detail-field-wide' : '');
    var labelDiv = document.createElement('div');
    labelDiv.className = 'detail-field-label';
    labelDiv.textContent = label;
    var valueDiv = document.createElement('div');
    valueDiv.className = 'detail-field-value';
    if (rawValue === null || rawValue === undefined || rawValue === '') {
        valueDiv.textContent = '-';
    } else {
        valueDiv.textContent = rawValue;
    }
    field.appendChild(labelDiv);
    field.appendChild(valueDiv);
    grid.appendChild(field);
}

function addResidentSection(body, title, icon, rows) {
    if (!rows.length) return;
    var section = document.createElement('div');
    section.className = 'detail-section';
    var titleDiv = document.createElement('div');
    titleDiv.className = 'detail-section-title';
    titleDiv.innerHTML = '<i class="fas ' + icon + '"></i> ' + title;
    var grid = document.createElement('div');
    grid.className = 'detail-grid';
    for (var i = 0; i < rows.length; i++) {
        addResidentField(grid, rows[i].label, rows[i].value, rows[i].wide);
    }
    section.appendChild(titleDiv);
    section.appendChild(grid);
    body.appendChild(section);
}

// Fields already shown in the sections above or in the header,
// so they are not repeated under "Additional Information".
var RESIDENT_HANDLED_KEYS = [
    'id', 'password', 'status', 'fullname',
    'first_name', 'middle_name', 'last_name', 'suffix', 'suffix_name',
    'address_street', 'address_block', 'address_purok',
    'email', 'mobile_number', 'civil_status', 'nationality',
    'date_of_birth', 'place_of_birth', 'created_at'
];

function openResidentModal(btn) {
    var data = JSON.parse(btn.getAttribute('data-details'));
    var body = document.getElementById('residentModalBody');
    body.innerHTML = '';

    document.getElementById('residentModalTitle').textContent = buildResidentFullName(data) || 'Resident Details';
    var subtitle = document.getElementById('residentModalSubtitle');
    subtitle.innerHTML = '';
    var badge = document.createElement('span');
    var status = data.status || 'Active';
    badge.className = 'badge badge-status';
    badge.textContent = status;
    subtitle.appendChild(badge);
    if (data.created_at) {
        var metaSpan = document.createElement('span');
        metaSpan.textContent = 'Registered ' + formatResidentDate(data.created_at);
        subtitle.appendChild(metaSpan);
    }

    addResidentSection(body, 'Personal Information', 'fa-user', [
        { label: 'Full Name', value: buildResidentFullName(data), wide: true },
        { label: 'Civil Status', value: data.civil_status },
        { label: 'Nationality', value: data.nationality },
        { label: 'Date of Birth', value: formatResidentBirthDate(data.date_of_birth) },
        { label: 'Place of Birth', value: data.place_of_birth }
    ]);

    addResidentSection(body, 'Address', 'fa-location-dot', [
        { label: 'Complete Address', value: buildResidentAddress(data), wide: true }
    ]);

    addResidentSection(body, 'Contact Information', 'fa-phone', [
        { label: 'Email Address', value: data.email },
        { label: 'Mobile Number', value: data.mobile_number }
    ]);

    var otherRows = [];
    for (var key in data) {
        if (RESIDENT_HANDLED_KEYS.indexOf(key) === -1) {
            otherRows.push({ label: RESIDENT_LABELS[key] || humanizeKey(key), value: data[key] });
        }
    }
    addResidentSection(body, 'Additional Information', 'fa-circle-info', otherRows);

    document.getElementById('residentModal').classList.add('open');
}

function closeResidentModal() {
    document.getElementById('residentModal').classList.remove('open');
}

function closeResidentModalOnOverlay(e) {
    if (e.target.id === 'residentModal') {
        closeResidentModal();
    }
}