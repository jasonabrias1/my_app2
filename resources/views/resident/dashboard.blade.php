<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Barangay Service Request</title>
  <link rel="stylesheet" href="{{ asset('stylesheet/resident_dashboard.css') }}">
  <link rel="stylesheet" href="{{ asset('stylesheet/logout_animation.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" />
</head>

<body>
  @if ($readyRequest)
    <div class="ready-toast" id="ready-toast" data-request-id="{{ $readyRequest->id }}"
      data-dismissal-scope="{{ $notificationScope }}">
      <a class="ready-toast-link"
        href="{{ url('/resident/tracking') }}?tracking_no={{ urlencode($readyRequest->tracking_code ?? '') }}&amp;auto_track=1">
        <div class="ready-toast-icon"><i class="ti ti-package"></i></div>
        <div class="ready-toast-text">
          <strong>Ready for pickup</strong>
          <span>{{ $readyRequest->document_type }} — tap to track</span>
        </div>
      </a>
      <button type="button" class="ready-toast-close" id="ready-toast-close" aria-label="Dismiss">
        <i class="ti ti-x"></i>
      </button>
    </div>
  @endif

  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon">
        <img src="{{ asset('logo/HomesteadLogo.jpg') }}" alt="Barangay Logo" />
      </div>
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
      <a href="{{ route('resident.dashboard') }}" class="nav-item active">
        <i class="ti ti-home"></i> Home
      </a>
      <a href="{{ url('/resident/submit') }}" class="nav-item">
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
        <i class="ti ti-logout"></i>
        Log out
      </a>
    </div>
  </aside>

  <main class="main-content">
    <div class="page-header">
      <h1>Good day, {{ $userName }}</h1>
      <p>What do you need help with today?</p>
    </div>

    <div class="card-grid">
      <a href="{{ url('/resident/submit') }}" class="action-card">
        <div class="card-top">
          <div class="card-icon blue"><i class="ti ti-file-plus"></i></div>
          <div class="card-arrow"><i class="ti ti-arrow-right"></i></div>
        </div>
        <h2>Submit a request</h2>
        <p>Ask for a clearance, certificate, or other document.</p>
      </a>

      <a href="{{ url('/resident/tracking') }}" class="action-card">
        <div class="card-top">
          <div class="card-icon green"><i class="ti ti-map-pin"></i></div>
          <div class="card-arrow"><i class="ti ti-arrow-right"></i></div>
        </div>
        <h2>Track a request</h2>
        <p>Check if your document is ready for pickup.</p>
      </a>

      <a href="{{ url('/resident/requests') }}" class="action-card">
        <div class="card-top">
          <div class="card-icon amber"><i class="ti ti-list-check"></i></div>
          <div class="card-arrow"><i class="ti ti-arrow-right"></i></div>
        </div>
        <h2>My request history</h2>
        <p>See all your past and current requests.</p>
      </a>
    </div>

    <div class="section-title">Recent requests</div>

    <div class="status-card">
      @if ($recentRequests->isEmpty())
        <div class="empty-state">
          <i class="ti ti-file-off"></i>
          You haven't submitted any requests yet.
        </div>
      @else
        @foreach ($recentRequests as $req)
          @php
            $badgeClass = $badgeMap[$req->status] ?? 'badge-pending';
          @endphp

          <div class="status-row" data-request="{{ json_encode($req, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">
            <div class="doc-icon">
              <i class="ti ti-file-text"></i>
            </div>

            <div class="status-info">
              <strong>{{ $req->document_type }}</strong>

              <small>
                Submitted
                {{ date('M j, Y', strtotime($req->request_date)) }}
              </small>

              <br>

              <small>
                Tracking Code:
                {{ $req->tracking_code ?? '' }}
              </small>
            </div>

            <span class="badge {{ $badgeClass }}">
              {{ $statusLabels[$req->status] ?? $req->status }}
            </span>

            <button type="button" class="view-btn">
              <i class="ti ti-eye"></i> View
            </button>
          </div>
        @endforeach
      @endif
    </div>
  </main>

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
      uploadsBase: @json(url('/resident/dashboard/attachments') . '/'),
      badgeMap: @json($badgeMap),
      statusLabels: @json($statusLabels)
    };
    window.REQUEST_PAGE_CONFIG = window.TRACKING_PAGE_CONFIG;
  </script>
  <script src="{{ asset('script/resident_dashboard.js') }}"></script>
  <script src="{{ asset('script/logout_animation.js') }}"></script>
</body>

</html>
