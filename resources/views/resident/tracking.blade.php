<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Track Request - Homestead Barangay</title>
    <link rel="stylesheet" href="{{ asset('stylesheet/resident_tracking.css') }}">
    <link rel="stylesheet" href="{{ asset('stylesheet/logout_animation.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" />
</head>

<body>
    <?php
    if (!function_exists('residentTrackingH')) {
        function residentTrackingH($value) { return e($value ?? ''); }
        function isOnBehalf($row) { return ($row['relationship_to_resident'] ?? '') !== '' && ($row['relationship_to_resident'] ?? '') !== 'You'; }
        function requestAttachments($row) {
            $jsLabel = 'Proof of Identity' . (!empty($row['js_id_type']) ? ' (' . $row['js_id_type'] . ')' : '');
            $candidates = [
                'Valid ID / Attachment' => $row['attachment_photo'] ?? '',
                $jsLabel => $row['js_identity'] ?? '',
                'Proof of Residency' => $row['js_residency'] ?? '',
                "Requestor's Valid ID" => $row['requestor_valid_id'] ?? '',
                'Proof of Relationship' => $row['relationship_proof'] ?? '',
            ];
            $attachments = [];
            foreach ($candidates as $label => $file) {
                if ($file !== '' && $file !== null && !in_array($file, $attachments, true)) $attachments[$label] = $file;
            }
            return $attachments;
        }
        function isImageFile($name) { return (bool) preg_match('/\.(jpe?g|png|gif|webp)$/i', $name); }
    }
    ?>
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon"><img src="{{ asset('logo/HomesteadLogo.jpg') }}" alt="Barangay Logo" /></div>
            <div>
                <div class="brand-name">Homestead Barangay</div>
                <div class="brand-loc">Bambang, Nueva Vizcaya</div>
            </div>
            <button type="button" class="menu-toggle" id="menu-toggle" aria-label="Toggle menu" aria-expanded="false">
                <i class="ti ti-menu-2"></i>
            </button>
        </div>

        <nav class="nav-section" id="nav-section">
            <div class="nav-label">Menu</div>
            <a href="{{ route('resident.dashboard') }}" class="nav-item"><i class="ti ti-home"></i> Home</a>
            <a href="{{ route('resident.submit') }}" class="nav-item"><i class="ti ti-file-plus"></i> Submit a request</a>
            <a href="{{ route('resident.requests') }}" class="nav-item"><i class="ti ti-list-check"></i> My requests</a>
            <a href="{{ route('resident.tracking') }}" class="nav-item active"><i class="ti ti-map-pin"></i> Track request</a>
            <a href="{{ route('resident.account') }}" class="nav-item"><i class="ti ti-user-cog"></i> Account Settings</a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-chip">
                <div class="avatar"><?php echo $initials; ?></div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
                    <div class="user-role">Resident</div>
                </div>
            </div>
            <a href="{{ route('resident.logout') }}" class="nav-item logout">
                <i class="ti ti-logout"></i> Log out</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>Track a Request</h1>
            <p>Enter your tracking number to see its current status.</p>
        </div>
        <form class="search-card" method="POST" action="{{ route('resident.tracking.search') }}" id="tracking-form">
            @csrf
            <input type="text" name="tracking_no" id="tracking-input" placeholder="e.g. BR-2026-0001"
                value="<?php echo htmlspecialchars($trackingCode); ?>" />

            <button type="submit" id="tracking-submit-btn">
                <i class="ti ti-search"></i>
                <span id="tracking-submit-label">Track</span>
            </button>
        </form>

        <div class="field-error" id="tracking-error"></div>

        <?php if ($notFound): ?>
            <div class="alert">
                No request found with tracking number "<?php echo htmlspecialchars($trackingCode); ?>".
            </div>
        <?php endif; ?>

        <?php if ($record): ?>
            <div class="result-card">
                <div class="result-head">
                    <div>
                        <h2><?php echo htmlspecialchars($record['document_type']); ?></h2>
                        <small>
                            #<?php echo htmlspecialchars($record['tracking_code']); ?>
                            &nbsp;•&nbsp;
                            Submitted <?php echo date("F j, Y", strtotime($record['request_date'])); ?>
                        </small>
                    </div>
                    <?php $badgeCls = $badgeMap[$record['status']] ?? "badge-pending"; ?>
                    <span class="badge <?php echo $badgeCls; ?>">
                        <?php echo htmlspecialchars($statusLabels[$record['status']] ?? $record['status']); ?>
                    </span>
                </div>

                <?php if ($currentStepIndex >= 0): ?>
                    <div class="stepper" style="margin-bottom:26px;">
                        <?php foreach ($stepKeys as $i => $key): ?>
                            <?php
                            if ($isCompleted || $i < $currentStepIndex) {
                                $stepClass = 'done';
                            } elseif ($i === $currentStepIndex) {
                                $stepClass = 'current';
                            } else {
                                $stepClass = '';
                            }
                            ?>
                            <div class="step <?= $stepClass ?>">
                                <div class="line"></div>
                                <div class="circle"><i class="ti ti-check"></i></div>
                                <div class="label"><?= residentTrackingH($steps[$key]) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- REQUESTOR DETAILS -->
                <div class="detail-section">
                    <div class="detail-section-title"><i class="ti ti-user"></i> Requestor Information</div>
                    <div class="detail-grid">
                        <div class="detail-field full-width">
                            <div class="detail-field-label">Full Name</div>
                            <div class="detail-field-value">
                                <?php echo residentTrackingH(trim($record['first_name'] . " " . $record['middle_name'] . " " . $record['last_name'] . " " . $record['suffix'])); ?>
                            </div>
                        </div>
                        <div class="detail-field">
                            <div class="detail-field-label">Civil Status</div>
                            <div class="detail-field-value"><?php echo residentTrackingH($record['civil_status'] ?? '—'); ?></div>
                        </div>
                        <div class="detail-field">
                            <div class="detail-field-label">Nationality</div>
                            <div class="detail-field-value"><?php echo residentTrackingH($record['nationality'] ?? '—'); ?></div>
                        </div>
                        <div class="detail-field">
                            <div class="detail-field-label">Date of Birth</div>
                            <div class="detail-field-value">
                                <?php echo !empty($record['date_of_birth']) ? date("F j, Y", strtotime($record['date_of_birth'])) : '—'; ?>
                            </div>
                        </div>
                        <div class="detail-field">
                            <div class="detail-field-label">Place of Birth</div>
                            <div class="detail-field-value"><?php echo residentTrackingH($record['place_of_birth'] ?? '—'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- LOCATION DETAILS -->
                <div class="detail-section">
                    <div class="detail-section-title"><i class="ti ti-map-pin"></i> Purok</div>
                    <div class="detail-grid">
                        <div class="detail-field full-width">
                            <div class="detail-field-label">Purok</div>
                            <div class="detail-field-value"><?php echo residentTrackingH($record['address_purok']); ?></div>
                        </div>
                    </div>
                </div>

                <!-- CONTACT DETAILS -->
                <div class="detail-section">
                    <div class="detail-section-title"><i class="ti ti-phone"></i> Contact Information</div>
                    <div class="detail-grid">
                        <div class="detail-field">
                            <div class="detail-field-label">Mobile Number</div>
                            <div class="detail-field-value"><?php echo residentTrackingH($record['mobile_number']); ?></div>
                        </div>
                        <div class="detail-field">
                            <div class="detail-field-label">Email Address</div>
                            <div class="detail-field-value"><?php echo residentTrackingH($record['email_address']) ?: '—'; ?></div>
                        </div>
                    </div>
                </div>

                <?php if (isOnBehalf($record)): ?>
                    <!-- "YOUR INFORMATION" SUMMARY (requestor, when filed on behalf of the resident) -->
                    <div class="detail-section">
                        <div class="detail-section-title"><i class="ti ti-user-check"></i> Your Information</div>
                        <div class="detail-grid">
                            <div class="detail-field full-width">
                                <div class="detail-field-label">Requested By</div>
                                <div class="detail-field-value">
                                    <?php echo residentTrackingH(trim(($record['requestor_first_name'] ?? '') . " " . ($record['requestor_middle_name'] ?? '') . " " . ($record['requestor_last_name'] ?? '') . " " . ($record['requestor_suffix'] ?? ''))) ?: '—'; ?>
                                </div>
                            </div>
                            <div class="detail-field">
                                <div class="detail-field-label">Relationship to Resident</div>
                                <div class="detail-field-value"><?php echo residentTrackingH($record['relationship_to_resident'] ?? '—'); ?></div>
                            </div>
                            <div class="detail-field">
                                <div class="detail-field-label">Mobile Number</div>
                                <div class="detail-field-value"><?php echo residentTrackingH($record['requestor_mobile_number'] ?? '') ?: '—'; ?></div>
                            </div>
                            <div class="detail-field full-width">
                                <div class="detail-field-label">Address</div>
                                <div class="detail-field-value"><?php echo residentTrackingH($record['requestor_address'] ?? '') ?: '—'; ?></div>
                            </div>
                            <div class="detail-field full-width">
                                <div class="detail-field-label">Email Address</div>
                                <div class="detail-field-value"><?php echo residentTrackingH($record['requestor_email_address'] ?? '') ?: '—'; ?></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($record['business_name'])): ?>

                    <div class="detail-section">
                        <div class="detail-section-title"><i class="ti ti-building-store"></i> Business Information</div>
                        <div class="detail-grid">
                            <div class="detail-field">
                                <div class="detail-field-label">Business Name</div>
                                <div class="detail-field-value"><?php echo residentTrackingH($record['business_name']); ?></div>
                            </div>
                            <div class="detail-field">
                                <div class="detail-field-label">Nature of Business</div>
                                <div class="detail-field-value"><?php echo residentTrackingH($record['business_nature'] ?? '') ?: '—'; ?></div>
                            </div>
                            <div class="detail-field full-width">
                                <div class="detail-field-label">Business Address</div>
                                <div class="detail-field-value"><?php echo residentTrackingH($record['business_address'] ?? '') ?: '—'; ?></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- REQUEST DETAILS -->
                <div class="detail-section">
                    <div class="detail-section-title"><i class="ti ti-file-text"></i> Request Information</div>
                    <div class="detail-grid">
                        <div class="detail-field">
                            <div class="detail-field-label">Document Type</div>
                            <div class="detail-field-value"><?php echo residentTrackingH($record['document_type']); ?></div>
                        </div>
                        <div class="detail-field">
                            <div class="detail-field-label">Status</div>
                            <div class="detail-field-value">
                                <?php echo residentTrackingH($statusLabels[$record['status']] ?? $record['status']); ?>
                            </div>
                        </div>
                        <div class="detail-field full-width">
                            <div class="detail-field-label">Purpose</div>
                            <div class="detail-field-value"><?php echo residentTrackingH($record['purpose']); ?></div>
                        </div>
                        <?php if (!empty($record['remarks'])): ?>
                            <div class="detail-field full-width">
                                <div class="detail-field-label">Remarks</div>
                                <div class="detail-field-value"><?php echo residentTrackingH($record['remarks']); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>


                <?php $attachments = requestAttachments($record); ?>
                <?php if ($attachments): ?>
                    <div class="detail-section">
                        <div class="detail-section-title"><i class="ti ti-id-badge-2"></i> Attachments</div>
                        <?php foreach ($attachments as $label => $file): ?>
                            <?php $fileUrl = $uploadPath . rawurlencode($file); ?>
                            <div class="detail-field-label" style="margin:12px 0 6px;"><?php echo residentTrackingH($label); ?></div>
                            <div class="modal-photo-wrap" style="display:block;">
                                <?php if (isImageFile($file)): ?>
                                    <a href="<?php echo residentTrackingH($fileUrl); ?>" target="_blank" rel="noopener">
                                        <img src="<?php echo residentTrackingH($fileUrl); ?>" alt="<?php echo residentTrackingH($label); ?>"
                                            style="max-width: 100%; max-height: 350px; border-radius: 8px; object-fit: contain; border: 1px solid #e2e8f0;" />
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo residentTrackingH($fileUrl); ?>" target="_blank" rel="noopener">
                                        <i class="ti ti-file-type-pdf"></i> Open file
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        <?php endif; ?>

        <div class="section-title">Your Requests</div>

        <?php if (count($myRequests) === 0): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="ti ti-inbox"></i></div>
                No requests found.
                <p>Once you submit a request, you'll be able to follow its progress right here.</p>
                <a href="{{ route('resident.submit') }}" class="empty-cta">Submit your first request <i
                        class="ti ti-arrow-right"></i></a>
            </div>
        <?php else: ?>
            <div class="pick-card">
                <?php foreach ($myRequests as $row): ?>
                    <?php
                    $cls = $badgeMap[$row['status']] ?? "badge-pending";
                    $rowJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="pick-row" data-request="<?php echo $rowJson; ?>">
                        <div class="pick-info">
                            <strong><?php echo htmlspecialchars($row['document_type']); ?></strong>
                            <small>
                                #<?php echo htmlspecialchars($row['tracking_code']); ?>
                                &nbsp;•&nbsp;
                                <?php echo date("F j, Y", strtotime($row['request_date'])); ?>
                            </small>
                        </div>
                        <span class="badge <?php echo $cls; ?>">
                            <?php echo htmlspecialchars($statusLabels[$row['status']] ?? $row['status']); ?>
                        </span>
                        <button type="button" class="view-btn">
                            <i class="ti ti-eye"></i> View
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- REQUEST DETAILS MODAL -->
    <div class="modal-overlay" id="detailsModal" style="display:none;" onclick="closeDetailsModalOnOverlay(event)">
        <div class="modal-box">
            <div class="modal-header">
                <div class="modal-title-group">
                    <div class="modal-doc-icon"><i class="ti ti-file-text"></i></div>
                    <div>
                        <h3 id="modalTitle">Request Details</h3>
                        <div class="modal-subtitle" id="modalSubtitle"></div>
                    </div>
                </div>
                <button type="button" class="modal-close" onclick="closeDetailsModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody"></div>
        </div>
    </div>

    <script>
        window.TRACKING_PAGE_CONFIG = {
            uploadsBase: @json($uploadPath),
            badgeMap: @json($badgeMap),
            statusLabels: @json($statusLabels)
        };
    </script>
    <script src="{{ asset('script/resident_tracking.js') }}"></script>
    <script src="{{ asset('script/resident_mobile_menu.js') }}"></script>
    <script src="{{ asset('script/logout_animation.js') }}"></script>
</body>

</html>