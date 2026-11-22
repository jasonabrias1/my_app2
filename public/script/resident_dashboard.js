    var CFG = window.TRACKING_PAGE_CONFIG || window.REQUEST_PAGE_CONFIG || {};
    var UPLOADS_BASE = CFG.uploadsBase || '';
    var STATUS_BADGE_CLASS = CFG.badgeMap || {};
    var STATUS_LABELS = CFG.statusLabels || {};

   function formatDetailDate(value) {
            if (!value) return value;
            var d = new Date(value);
            if (isNaN(d.getTime())) return value;
            return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        }

        function joinName(parts) {
            var kept = [];
            for (var i = 0; i < parts.length; i++) {
                if (parts[i] && String(parts[i]).trim() !== '') {
                    kept.push(parts[i]);
                }
            }
            return kept.join(' ');
        }

        function buildFullName(data) {
            return joinName([data.first_name, data.middle_name, data.last_name, data.suffix]);
        }

        function buildRequestorFullName(data) {
            return joinName([data.requestor_first_name, data.requestor_middle_name, data.requestor_last_name, data.requestor_suffix]);
        }


        function isOnBehalf(data) {
            var rel = data.relationship_to_resident;
            if (rel && rel !='You') {
                return true;
            }
            return false;
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
            titleDiv.innerHTML = '<i class="ti ' + icon + '"></i> ' + title;
            var grid = document.createElement('div');
            grid.className = 'detail-grid';
            for (var i = 0; i < rows.length; i++) {
                addDetailField(grid, rows[i].key, rows[i].label, rows[i].value, rows[i].fullWidth);
            }
            section.appendChild(titleDiv);
            section.appendChild(grid);
            body.appendChild(section);
        }

        function isImageFile(name) {
            if (!name) return false;
            var ext = name.split('.').pop().toLowerCase();
            return ext == 'jpg' || ext == 'jpeg' || ext == 'png' ||ext == 'gif' || ext == 'webp';
        }


        function addOneAttachment(section, it) {
                var src = UPLOADS_BASE + encodeURIComponent(it.file);

                var label = document.createElement('div');
                label.className = 'detail-field-label';
                label.style.margin = '12px 0 6px';
                label.textContent = it.label;
                section.appendChild(label);

                if (isImageFile(it.file)) {
                    var wrap = document.createElement('div');
                    wrap.className = 'modal-photo-wrap';
                    wrap.style.display = 'block';
                    var img = document.createElement('img');
                    img.src = src;
                    img.alt = it.label;
                    img.onclick = function () { window.open(src, '_blank'); };
                    img.onerror = function () {
                        wrap.style.display = 'none';
                        var noPhoto = document.createElement('div');
                        noPhoto.className = 'modal-no-photo';
                        noPhoto.style.display = 'flex';
                        noPhoto.innerHTML = '<i class="ti ti-photo-off"></i> Could not load the attachment.';
                        section.insertBefore(noPhoto, wrap.nextSibling);
                    };
                    wrap.appendChild(img);
                    section.appendChild(wrap);
                } else {

                    var link = document.createElement('a');
                    link.href = src;
                    link.target = '_blank';
                    link.rel = 'noopener';
                    link.innerHTML = '<i class="ti ti-file-type-pdf"></i> Open file';
                    section.appendChild(link);
                }
        }


function addAttachmentSection(body, data) {
    var all = [
        { label: 'Valid ID / Attachment', file: data.attachment_photo },
        { label: 'Proof of Identity' + (data.js_id_type ? ' (' + data.js_id_type + ')' : ''), file: data.js_identity },
        { label: 'Proof of Residency', file: data.js_residency },
        { label: "Requestor's Valid ID", file: data.requestor_valid_id },
        { label: 'Proof of Relationship', file: data.relationship_proof }
    ];

    var seen = {};
    var items = [];
    for (var i = 0; i < all.length; i++) {
        if (all[i].file && !seen[all[i].file]) {
            seen[all[i].file] = true;
            items.push(all[i]);
        }
    }
    if (items.length == 0) return;

    var section = document.createElement('div');
    section.className = 'detail-section';
    var titleDiv = document.createElement('div');
    titleDiv.className = 'detail-section-title';
    titleDiv.innerHTML = '<i class="ti ti-id-badge-2"></i> Attachments';
    section.appendChild(titleDiv);

    for (var j = 0; j < items.length; j++) {
        addOneAttachment(section, items[j]);
    }
        body.appendChild(section);

}
    function openDetailsModal (data) {
        var modal = document.getElementById('detailsModal');
        var body = document.getElementById('modalBody');
        var subtitle = document.getElementById('modalSubtitle');
            body.innerHTML = '';

            document.getElementById('modalTitle').textContent = data.document_type || 'Request Details';

            subtitle.innerHTML = '';
            if (data.status) {
                var badge = document.createElement('span');
                badge.className = 'badge ' + (STATUS_BADGE_CLASS[data.status] || 'badge-pending');
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

            addDetailSection(body, 'Requestor Information', 'ti-user', keepDefined([
                { key: 'full_name', label: 'Full Name', value: buildFullName(data), fullWidth: true },
                { key: 'civil_status', label: 'Civil Status', value: data.civil_status },
                { key: 'nationality', label: 'Nationality', value: data.nationality },
                { key: 'date_of_birth', label: 'Date of Birth', value: data.date_of_birth },
                { key: 'place_of_birth', label: 'Place of Birth', value: data.place_of_birth }
            ]));

            addDetailSection(body, 'Purok', 'ti-map-pin', [
                { key: 'address_purok', label: 'Purok', value: data.address_purok, fullWidth: true }
            ]);

            addDetailSection(body, 'Contact Information', 'ti-phone', keepDefined([
                { key: 'mobile_number', label: 'Mobile Number', value: data.mobile_number },
                { key: 'email_address', label: 'Email Address', value: data.email_address }
            ]));


            if (isOnBehalf(data)) {
                addDetailSection(body, 'Your Information', 'ti-user-check', [
                    { key: 'requestor_full_name', label: 'Requested By', value: buildRequestorFullName(data), fullWidth: true },
                    { key: 'relationship_to_resident', label: 'Relationship to Resident', value: data.relationship_to_resident },
                    { key: 'requestor_mobile_number', label: 'Mobile Number', value: data.requestor_mobile_number },
                    { key: 'requestor_address', label: 'Address', value: data.requestor_address, fullWidth: true },
                    { key: 'requestor_email_address', label: 'Email Address', value: data.requestor_email_address, fullWidth: true }
                ]);
            }

            addDetailSection(body, 'Request Information', 'ti-file-text', keepDefined([
                { key: 'document_type', label: 'Document Type', value: data.document_type },
                { key: 'purpose', label: 'Purpose', value: data.purpose, fullWidth: true },
                { key: 'status', label: 'Status', value: STATUS_LABELS[data.status] || data.status },
                { key: 'request_date', label: 'Date Filed', value: data.request_date },
                { key: 'tracking_code', label: 'Tracking Code', value: data.tracking_code }
            ]));

            if (data.business_name) {
                addDetailSection(body, 'Business Information', 'ti-building-store', [
                    { key: 'business_name', label: 'Business Name', value: data.business_name },
                    { key: 'business_nature', label: 'Nature of Business', value: data.business_nature },
                    { key: 'business_address', label: 'Business Address', value: data.business_address, fullWidth: true }
                ]);
            }

            addAttachmentSection(body, data);

            modal.classList.add('open');
            modal.style.display = 'flex';
        }


function closeDetailsModal() {
    var modal = document.getElementById('detailsModal');
    modal.classList.remove('open');
    modal.style.display = 'none';
}

function closeDetailsModalOnOverlay(e) {
    if (e.target.id == 'detailsModal') closeDetailsModal();
}


function setupViewButton(row) {
    var viewBtn = row.querySelector('.view-btn');
    if (!viewBtn) return;
    viewBtn.addEventListener('click', function () {
        var data = JSON.parse(row.getAttribute('data-request'));
        openDetailsModal(data);
    });
}

function setupDetailsModal(rowClass) {
    if (!document.getElementById('detailsModal')) return;

    var rows = document.querySelectorAll(rowClass);
    for (var i = 0; i < rows.length; i++) {
        setupViewButton(rows[i]);
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeDetailsModal();
    });
}


function setupMobileSidebar() {
    var menuToggle = document.getElementById('menu-toggle');
    var navSection = document.getElementById('nav-section');
    if (menuToggle && navSection) {
        menuToggle.addEventListener('click', function () {
            navSection.classList.toggle('open');
        });
    }
}


function setupReadyToast() {
    var toast = document.getElementById('ready-toast');
    var closeBtn = document.getElementById('ready-toast-close');
    if (!toast) return;

    var requestId = toast.getAttribute('data-request-id');
    var dismissalScope = toast.getAttribute('data-dismissal-scope');
    var dismissedKey = requestId && dismissalScope
        ? 'dismissedReadyRequest:' + dismissalScope + ':' + requestId
        : '';
    if (dismissedKey) {
        try {
            if (window.localStorage.getItem(dismissedKey) === '1') {
                toast.remove();
                return;
            }
        } catch (e) {}
    }

    if (!closeBtn) return;
    closeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (dismissedKey) {
            try {
                window.localStorage.setItem(dismissedKey, '1');
            } catch (e) {}
        }
        toast.remove();
    });
}

document.addEventListener('DOMContentLoaded', function () {
    setupMobileSidebar();
    setupReadyToast();

    setupDetailsModal('.status-row');
});

window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
        window.location.reload();
    }
});