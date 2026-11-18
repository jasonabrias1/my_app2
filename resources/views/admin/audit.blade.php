<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - E-Serbisyo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('stylesheet/admin_dashboard.css') }}">
    <style>
        .audit-meta { font-size: 12px; color: var(--muted); margin-bottom: 10px; }
        .audit-pager { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 16px; font-size: 13px; flex-wrap: wrap; }
        .audit-pager a, .audit-pager span.disabled { padding: 7px 14px; border-radius: 8px; border: 1px solid #d1d5db; text-decoration: none; color: inherit; }
        .audit-pager span.disabled { opacity: .45; }
        .audit-table td:first-child { white-space: nowrap; }
        .audit-table { table-layout: fixed; }
        .audit-table th, .audit-table td { text-align: left; vertical-align: top; overflow-wrap: anywhere; }
        .audit-table th:first-child { width: 160px; }
        .audit-table th:nth-child(2) { width: 15%; }
        .audit-table th:nth-child(3) { width: 10%; }
        .audit-table th:nth-child(4) { width: 20%; }
        @media screen and (min-width: 1001px) {
            .audit-main { display: flex; flex-direction: column; }
            .audit-panel { flex: 1 0 auto; display: flex; flex-direction: column; }
            .audit-pager { margin-top: auto; padding-top: 16px; }
        }
    </style>
</head>
<body>
    @include('admin.partials.sidebar')

    <div class="main audit-main">
        <div class="topbar">
            <div class="who">Signed in as <b>{{ $admin_name }}</b></div>
        </div>
        <div class="panel audit-panel">
            <h3>Activity Log</h3>
            @if ($logs->isEmpty())
                <div class="empty-state"><i class="fas fa-clock-rotate-left"></i>No activity found.</div>
            @else
                <div class="audit-meta">{{ $logs->total() }} record{{ $logs->total() === 1 ? '' : 's' }} found</div>
                <table class="resp-table audit-table">
                    <tr class="pt-head">
                        <th>Date/Time</th><th>User</th><th>Role</th><th>Action</th><th>Details</th>
                    </tr>
                    @foreach ($logs as $log)
                        <tr>
                            <td data-label="Date/Time">{{ \Illuminate\Support\Carbon::parse($log->created_at)->format('Y-m-d H:i') }}</td>
                            <td data-label="User">{{ $log->user_name }}</td>
                            <td data-label="Role">{{ $log->role }}</td>
                            <td data-label="Action">{{ $log->action }}</td>
                            <td data-label="Details">{{ $log->details }}</td>
                        </tr>
                    @endforeach
                </table>
                @if ($logs->lastPage() > 1)
                    <div class="audit-pager">
                        @if ($logs->currentPage() > 1)
                            <a href="{{ route('admin.audit', ['page' => $logs->currentPage() - 1]) }}">&laquo; Prev</a>
                        @else
                            <span class="disabled">&laquo; Prev</span>
                        @endif
                        <span>Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</span>
                        @if ($logs->currentPage() < $logs->lastPage())
                            <a href="{{ route('admin.audit', ['page' => $logs->currentPage() + 1]) }}">Next &raquo;</a>
                        @else
                            <span class="disabled">Next &raquo;</span>
                        @endif
                    </div>
                @endif
            @endif
        </div>
    </div>
</body>
</html>
