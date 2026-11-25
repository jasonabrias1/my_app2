<div class="mobile-topbar">
    <button type="button" class="menu-toggle" onclick="openSidebar()" aria-label="Open menu"
        aria-controls="sidebar" aria-expanded="false"><i class="fas fa-bars"></i></button>
    <div class="mobile-brand">
        <span class="brand-circle"><img src="{{ asset('logo/HomesteadLogo.jpg') }}" alt="Barangay Logo"></span>
        <b>E-SERBISYO</b>
    </div>
</div>
<div class="sidebar-overlay" onclick="closeSidebar()"></div>

<div class="sidebar" id="sidebar">
    @php($currentDashboardView = request()->routeIs('admin.dashboard') ? ($view ?? request()->query('view', 'overview')) : null)
    @php($isAccountsView = request()->routeIs('admin.accounts') || request()->routeIs('admin.residents') || request()->routeIs('admin.admins'))
    @php($isRequestView = in_array($currentDashboardView, ['pending', 'pickup', 'completed', 'cancelled'], true) || $isAccountsView)
    @php($currentAccountType = request()->query('type', 'resident') === 'admin' ? 'admin' : 'resident')
    <button type="button" class="sidebar-close" onclick="closeSidebar()" aria-label="Close menu"><i
            class="fas fa-xmark"></i></button>
    <div class="brand">
        <div class="brand-circle"><img src="{{ asset('logo/HomesteadLogo.jpg') }}" alt="Barangay Logo"></div>
        <div>
            <h1>E-SERBISYO</h1>
            <span>Homestead, Bambang, N.V.</span>
        </div>
    </div>

    <a href="{{ route('admin.dashboard', ['view' => 'overview']) }}"
        class="nav-link {{ $currentDashboardView === 'overview' ? 'active' : '' }}">
        <span><i class="fas fa-grip nav-icon"></i>Dashboard</span>
    </a>

    @php($docRequestsTotal = ($counts['Pending'] ?? 0) + ($counts['PickUp'] ?? 0) + ($counts['Released'] ?? 0) + ($counts['Cancelled'] ?? 0))
    <button type="button" class="nav-link nav-parent-toggle {{ $isRequestView ? 'nav-parent-expanded' : '' }}"
        aria-expanded="{{ $isRequestView ? 'true' : 'false' }}" aria-controls="doc-requests-submenu"
        onclick="toggleNavGroup('doc-requests')"
        style="text-align:left;padding:11px 6px 11px 12px;gap:4px;">
        <span style="white-space:nowrap;font-size:12.5px;"><i class="fas fa-folder-open nav-icon" style="width:15px;margin-right:5px;"></i>Document Requests</span>
        <span style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
            <span class="nav-badge" style="padding:1px 5px;font-size:10px;">{{ $docRequestsTotal }}</span>
            <i class="fas fa-chevron-down nav-chevron" style="font-size:10px;"></i>
        </span>
    </button>
    <div class="nav-submenu {{ $isRequestView ? 'nav-submenu-expanded' : '' }}" id="doc-requests-submenu"
        aria-hidden="{{ $isRequestView ? 'false' : 'true' }}">
        <div class="nav-submenu-content">
            <a href="{{ route('admin.dashboard', ['view' => 'pending']) }}"
                class="nav-link nav-sublink {{ $currentDashboardView === 'pending' ? 'active' : '' }}">
                <span><i class="fas fa-hourglass-half nav-icon"></i>Pending</span>
                <span class="nav-badge">{{ $counts['Pending'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.dashboard', ['view' => 'pickup']) }}"
                class="nav-link nav-sublink {{ $currentDashboardView === 'pickup' ? 'active' : '' }}">
                <span><i class="fas fa-box-archive nav-icon"></i>For Pick Up</span>
                <span class="nav-badge">{{ $counts['PickUp'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.dashboard', ['view' => 'completed']) }}"
                class="nav-link nav-sublink {{ $currentDashboardView === 'completed' ? 'active' : '' }}">
                <span><i class="fas fa-circle-check nav-icon"></i>Completed</span>
                <span class="nav-badge">{{ $counts['Released'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.dashboard', ['view' => 'cancelled']) }}"
                class="nav-link nav-sublink {{ $currentDashboardView === 'cancelled' ? 'active' : '' }}">
                <span><i class="fas fa-ban nav-icon"></i>Cancelled</span>
                <span class="nav-badge">{{ $counts['Cancelled'] ?? 0 }}</span>
            </a>
        </div>
    </div>

    <div class="nav-section-label">Residents</div>
    <a href="{{ route('admin.walkin') }}"
        class="nav-link {{ request()->routeIs('admin.walkin') ? 'active' : '' }}">
        <span><i class="fas fa-person-walking nav-icon"></i>Walk-in Request</span>
    </a>
    <button type="button" class="nav-link nav-parent-toggle {{ $isAccountsView ? 'nav-parent-expanded' : '' }}"
        aria-expanded="{{ $isAccountsView ? 'true' : 'false' }}" aria-controls="accounts-submenu"
        onclick="toggleNavGroup('accounts')"
        style="text-align:left;padding:11px 6px 11px 12px;gap:4px;">
        <span style="white-space:nowrap;font-size:12.5px;"><i class="fas fa-user-group nav-icon" style="width:15px;margin-right:5px;"></i>Accounts</span>
        <span style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
            <i class="fas fa-chevron-down nav-chevron" style="font-size:10px;"></i>
        </span>
    </button>
    <div class="nav-submenu {{ $isAccountsView ? 'nav-submenu-expanded' : '' }}" id="accounts-submenu"
        aria-hidden="{{ $isAccountsView ? 'false' : 'true' }}">
        <div class="nav-submenu-content">
            <a href="{{ route('admin.residents') }}"
                class="nav-link nav-sublink {{ request()->routeIs('admin.residents') ? 'active' : '' }}">
                <span><i class="fas fa-users nav-icon"></i>Resident Accounts</span>
                <span class="nav-badge">{{ $total_residents ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.admins') }}"
                class="nav-link nav-sublink {{ request()->routeIs('admin.admins') ? 'active' : '' }}">
                <span><i class="fas fa-user-shield nav-icon"></i>Admin Accounts</span>
                <span class="nav-badge">{{ $total_admins ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.accounts', ['type' => 'resident']) }}"
                class="nav-link nav-sublink {{ request()->routeIs('admin.accounts') && $currentAccountType === 'resident' ? 'active' : '' }}">
                <span><i class="fas fa-user-plus nav-icon"></i>Create Resident Account</span>
            </a>
            <a href="{{ route('admin.accounts', ['type' => 'admin']) }}"
                class="nav-link nav-sublink {{ request()->routeIs('admin.accounts') && $currentAccountType === 'admin' ? 'active' : '' }}">
                <span><i class="fas fa-user-shield nav-icon"></i>Create Admin Account</span>
            </a>
        </div>
    </div>

    <div class="nav-section-label">Reports</div>
    <a href="{{ route('admin.summary') }}"
        class="nav-link {{ request()->routeIs('admin.summary') ? 'active' : '' }}">
        <span><i class="fas fa-list-check nav-icon"></i>List of Requests</span>
    </a>
    <a href="{{ route('admin.audit') }}"
        class="nav-link {{ request()->routeIs('admin.audit') ? 'active' : '' }}">
        <span><i class="fas fa-clock-rotate-left nav-icon"></i>Audit Trail</span>
    </a>
    <a href="{{ route('admin.logout') }}" class="nav-link logout-link">
        <span><i class="fas fa-right-from-bracket nav-icon"></i>Log Out</span>
    </a>
</div>
<script src="{{ asset('script/admin_sidebar.js') }}"></script>
<script src="{{ asset('script/logout_animation.js') }}"></script>