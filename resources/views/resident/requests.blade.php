<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Requests - Homestead Barangay</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('stylesheet/resident_request.css') }}">
    <link rel="stylesheet" href="{{ asset('stylesheet/logout_animation.css') }}">
</head>

<body>
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
            <a href="{{ route('resident.requests') }}" class="nav-item active"><i class="ti ti-list-check"></i> My requests</a>
            <a href="{{ route('resident.tracking') }}" class="nav-item"><i class="ti ti-map-pin"></i> Track request</a>
            <a href="{{ route('resident.account') }}" class="nav-item"><i class="ti ti-user-cog"></i> Account Settings</a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-chip">
                <div class="avatar">
                    <?php echo $initials; ?>
                </div>
                <div>
                    <div class="user-name">
                        <?php echo htmlspecialchars($userName); ?>
                    </div>
                    <div class="user-role">Resident</div>
                </div>
            </div>
            <a href="{{ route('resident.logout') }}" class="nav-item logout">
                <i class="ti ti-logout"></i> Log out</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="page-top" style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;">
            <div class="page-header">
                <h1>My Requests</h1>
                <p>All the documents you've requested from the barangay, in one place.</p>
            </div>
            <a href="{{ route('resident.submit') }}" class="new-btn"><i class="ti ti-plus"></i> New request</a>
        </div>

        <div class="filter-tabs">
            <a href="{{ route('resident.requests') }}" class="<?php echo $filter === '' ? 'active' : ''; ?>">All</a>
            <?php foreach (array_keys($statusGroups) as $tab): ?>
                <a href="?status=<?php echo urlencode($tab); ?>"
                    class="<?php echo $filter === $tab ? 'active' : ''; ?>"><?php echo htmlspecialchars($tab); ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (count($requests) === 0): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="ti ti-inbox"></i></div>
                No requests found<?php echo $filter ? " for \"" . htmlspecialchars($filter) . "\"" : ""; ?>.
                <p>Once you submit a request, you'll be able to follow its progress right here.</p>
                <a href="{{ route('resident.submit') }}" class="empty-cta">Submit your first request <i
                        class="ti ti-arrow-right"></i></a>
            </div>
        <?php else: ?>
            <div class="status-card">
                <?php
                $rowIndex = 0;
                foreach ($requests as $row):
                    $rowIndex++;
                    $rawCode = trim($row['tracking_code'] ?? '');
                    $label = $statusLabels[$row['status']] ?? $row['status'];
                    ?>
                    <div class="status-row" style="animation-delay: <?php echo min($rowIndex * 0.05, 0.4); ?>s;">
                        <div class="doc-icon"><i class="ti ti-file-text"></i></div>

                        <div class="status-info">
                            <strong><?php echo htmlspecialchars($row['document_type']); ?></strong>

                            <small>
                                Submitted <?php echo date("F j, Y", strtotime($row['request_date'])); ?>
                            </small>

                            <div class="purpose">
                                <strong>Purpose:</strong>
                                <?php echo htmlspecialchars($row['purpose'] ?? ''); ?>
                            </div>

                            <?php if ($rawCode !== '' && $filter !== 'Completed'): ?>
                                <div style="display: flex; align-items: center; gap: 8px; margin-top: 6px;">
                                    <a class="track-link"
                                        href="{{ route('resident.tracking') }}?auto_track=1&tracking_no=<?php echo urlencode($rawCode); ?>">
                                        <i class="ti ti-map-pin"></i> Track this request
                                    </a>

                                    <button type="button" class="copy-btn" title="Copy tracking code"
                                        data-code="<?php echo htmlspecialchars($rawCode, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="ti ti-copy"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($filter !== ''): ?>
                            <div class="status-side">
                                <span class="badge <?php echo $badgeMap[$row['status']] ?? 'badge-pending'; ?>">
                                    <?php echo htmlspecialchars($label); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script src="{{ asset('script/resident_request.js') }}"></script>
    <script src="{{ asset('script/resident_mobile_menu.js') }}"></script>
    <script src="{{ asset('script/logout_animation.js') }}"></script>
</body>

</html>
