<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard - E-Serbisyo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('stylesheet/admin_dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('stylesheet/admin_dashboard_page.css') }}">
    @if ($view === 'overview')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.0/chart.umd.min.js"></script>
        <link rel="stylesheet" href="{{ asset('stylesheet/admin_analytics.css') }}">
        <style>
            .stat-grid-4 { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
            .analytics-grid-3 { grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
        </style>
    @endif
</head>

<body>
    @if (view()->exists('admin.partials.sidebar'))
        @include('admin.partials.sidebar', [
            'counts' => $counts,
            'total_residents' => $total_residents,
            'admin_name' => $admin_name,
        ])
    @endif

    <div class="main">
        <div class="topbar">
            <h2>{{ $tab_titles[$view] }}</h2>
            <div class="who">Signed in as <b>{{ $admin_name }}</b></div>
        </div>

        @if ($flash)
            <div class="flash {{ $flash_type === 'error' ? 'error' : 'success' }}">{{ $flash }}</div>
        @endif

        @if ($view === 'overview')
            @php($requestTotal = $counts['Pending'] + $counts['PickUp'] + $counts['Released'])
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="icon icon-pending"><i class="fas fa-hourglass-half"></i></div>
                    <div class="num">{{ $counts['Pending'] }}</div>
                    <div class="label">Pending</div>
                </div>
                <div class="stat-card">
                    <div class="icon icon-pickup"><i class="fas fa-box-archive"></i></div>
                    <div class="num">{{ $counts['PickUp'] }}</div>
                    <div class="label">For Pick Up</div>
                </div>
                <div class="stat-card">
                    <div class="icon icon-completed"><i class="fas fa-circle-check"></i></div>
                    <div class="num">{{ $counts['Released'] }}</div>
                    <div class="label">Completed</div>
                </div>
                <div class="stat-card">
                    <div class="icon icon-processing"><i class="fas fa-folder-open"></i></div>
                    <div class="num">{{ $requestTotal }}</div>
                    <div class="label">Total Requests</div>
                </div>
                <div class="stat-card">
                    <div class="icon icon-review"><i class="fas fa-users"></i></div>
                    <div class="num">{{ $total_residents }}</div>
                    <div class="label">Residents</div>
                </div>
                <div class="stat-card">
                    <div class="icon icon-review"><i class="fas fa-percent"></i></div>
                    <div class="num">{{ $requestTotal > 0 ? round($counts['Released'] / $requestTotal * 100) : 0 }}%</div>
                    <div class="label">Completion Rate</div>
                </div>
            </div>

            <div class="panel">
                <h3>Recent Requests</h3>
                @if (empty($recent_rows))
                    <div class="empty-state"><i class="fas fa-inbox"></i>No document requests yet.</div>
                @else
                    <table class="resp-table">
                        <tr class="pt-head">
                            <th>Tracking Code</th><th>Requestor</th><th>Document</th><th>Status</th>
                            <th>Date</th><th>Details</th><th>Print</th>
                        </tr>
                        @foreach ($recent_rows as $r)
                            <tr>
                                <td data-label="Tracking Code">{{ $r['tracking_code'] ?? '' }}</td>
                                <td data-label="Requestor">{{ trim(($r['first_name'] ?? '').' '.($r['last_name'] ?? '')) }}</td>
                                <td data-label="Document">{{ $r['document_type'] ?? '' }}</td>
                                <td data-label="Status"><span class="badge {{ $status_badge_class[$r['status']] ?? '' }}">{{ $status_labels[$r['status']] ?? ($r['status'] ?? '') }}</span></td>
                                <td data-label="Date">{{ !empty($r['request_date']) ? \Carbon\Carbon::parse($r['request_date'])->format('M j, Y') : '' }}</td>
                                <td data-label="Details">
                                    <button type="button" class="btn btn-view"
                                        data-details="{{ json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
                                        onclick="openDetailsModal(this)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                                <td data-label="Print">
                                    @if (!in_array($r['status'], ['For Pick Up', 'Released'], true))
                                        <button type="button" class="btn btn-view" onclick="printCertificate({{ (int) $r['id'] }})">
                                            <i class="fas fa-print"></i> Print
                                        </button>
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </div>

            <div class="panel">
                <h3>Analytics Filter</h3>
                <form method="get" action="{{ route('admin.dashboard') }}"
                    style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin:0;">
                    <input type="hidden" name="view" value="overview">
                    <label style="display:flex;flex-direction:column;font-size:12px;gap:4px;">Month
                        <select name="month" style="padding:6px 10px;border-radius:6px;border:1px solid #cbd5e1;">
                            <option value="0">All</option>
                            @for ($month = 1; $month <= 12; $month++)
                                <option value="{{ $month }}" @selected($f_month === $month)>{{ date('F', mktime(0, 0, 0, $month, 1)) }}</option>
                            @endfor
                        </select>
                    </label>
                    <label style="display:flex;flex-direction:column;font-size:12px;gap:4px;">Day
                        <select name="day" style="padding:6px 10px;border-radius:6px;border:1px solid #cbd5e1;">
                            <option value="0">All</option>
                            @for ($day = 1; $day <= 31; $day++)
                                <option value="{{ $day }}" @selected($f_day === $day)>{{ $day }}</option>
                            @endfor
                        </select>
                    </label>
                    <label style="display:flex;flex-direction:column;font-size:12px;gap:4px;">Year
                        <select name="year" style="padding:6px 10px;border-radius:6px;border:1px solid #cbd5e1;">
                            <option value="0">All</option>
                            @foreach ($analytics['yearOptions'] as $year)
                                <option value="{{ $year }}" @selected($f_year === $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="btn btn-view"><i class="fas fa-filter"></i> Apply</button>
                    @if ($f_year || $f_month || $f_day)
                        <a href="{{ route('admin.dashboard', ['view' => 'overview']) }}" class="btn btn-view" style="text-decoration:none;">
                            <i class="fas fa-rotate-left"></i> Reset
                        </a>
                    @endif
                </form>
                <div style="margin-top:10px;font-size:13px;color:#64748b;">
                    Showing <b>{{ $analytics['total'] }}</b> request{{ $analytics['total'] === 1 ? '' : 's' }}
                    @if ($f_year || $f_month || $f_day)
                        for <b>{{ trim(($f_month ? date('F', mktime(0, 0, 0, $f_month, 1)) : '').' '.($f_day ?: '').($f_day && $f_year ? ',' : '').' '.($f_year ?: '')) }}</b>
                    @else
                        for all time
                    @endif
                </div>
            </div>

            <div class="analytics-grid analytics-grid-3">
                <div class="panel"><h3>Requests Over Time</h3><div class="chart-box"><canvas id="monthChart"></canvas></div></div>
                <div class="panel"><h3>Requests by Status</h3><div class="chart-box"><canvas id="statusChart"></canvas></div></div>
                <canvas id="profileChart" hidden></canvas>
            </div>

            <div class="panel">
                <h3>Requests by Document Type</h3>
                <div class="doctype-breakdown">
                    <div class="chart-box" style="height:260px;"><canvas id="typeChart"></canvas></div>
                    <div class="doctype-list">
                        @php($maxTypeCount = $analytics['byType'] ? max($analytics['byType']) : 0)
                        @forelse ($analytics['byType'] as $documentType => $count)
                            <div class="doctype-row">
                                <div class="doctype-row-top"><span class="doctype-name">{{ $documentType }}</span><span class="doctype-count">{{ $count }}</span></div>
                                <div class="doctype-bar-track"><div class="doctype-bar-fill" style="width:{{ $maxTypeCount > 0 ? round($count / $maxTypeCount * 100) : 0 }}%;"></div></div>
                            </div>
                        @empty
                            <div class="empty-state"><i class="fas fa-inbox"></i>No requests yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @else
            <div class="panel">
                @if (empty($listing_rows))
                    <div class="empty-state"><i class="fas fa-folder-open"></i>No requests in this list right now.</div>
                @else
                    <div class="{{ $view === 'pickup' ? 'table-scroll' : '' }}">
                        <table class="resp-table{{ $view === 'pickup' ? ' pickup-table' : '' }}">
                            <tr class="pt-head">
                                <th>Tracking Code</th><th>Requestor</th><th>Document Type</th><th>Purpose</th>
                                <th>Contact</th><th>Date Filed</th><th>Status</th>
                                @if ($view !== 'completed') <th>Details</th> @endif
                                @if ($view === 'pickup')
                                    <th>Notify</th><th>Update Status</th>
                                @elseif ($view === 'pending')
                                    <th>Print</th>
                                @endif
                            </tr>
                            @foreach ($listing_rows as $r)
                                <tr>
                                    <td data-label="Tracking Code">{{ $r['tracking_code'] ?? '' }}</td>
                                    <td data-label="Requestor">{{ trim(($r['first_name'] ?? '').' '.($r['middle_name'] ?? '').' '.($r['last_name'] ?? '').' '.($r['suffix'] ?? '')) }}</td>
                                    <td data-label="Document Type">{{ $r['document_type'] ?? '' }}</td>
                                    <td data-label="Purpose">{{ $r['purpose'] ?? '' }}</td>
                                    <td data-label="Contact">{{ $r['mobile_number'] ?? '' }}@if (!empty($r['email_address']))<br>{{ $r['email_address'] }}@endif</td>
                                    <td data-label="Date Filed">{{ !empty($r['request_date']) ? \Carbon\Carbon::parse($r['request_date'])->format('M j, Y g:i A') : '' }}</td>
                                    <td data-label="Status"><span class="badge {{ $status_badge_class[$r['status']] ?? '' }}">{{ $status_labels[$r['status']] ?? ($r['status'] ?? '') }}</span></td>
                                    @if ($view !== 'completed')
                                        <td data-label="Details">
                                            <button type="button" class="btn btn-view"
                                                data-details="{{ json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
                                                onclick="openDetailsModal(this)"><i class="fas fa-eye"></i> View</button>
                                        </td>
                                    @endif
                                    @if ($view === 'pickup')
                                        <td data-label="Notify">
                                            <button type="button" class="btn btn-view btn-notify" data-id="{{ (int) $r['id'] }}"
                                                onclick="notifyResident({{ (int) $r['id'] }}, this)"><i class="fas fa-comment-sms"></i> Notify</button>
                                        </td>
                                        <td data-label="Update Status">
                                            <form method="post" action="{{ route('admin.dashboard.update') }}" style="margin:0;">
                                                @csrf
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="request_id" value="{{ (int) $r['id'] }}">
                                                <input type="hidden" name="current_view" value="pickup">
                                                <input type="hidden" name="new_status" value="Released">
                                                <button type="button" class="btn btn-view" data-code="{{ $r['tracking_code'] ?? '' }}"
                                                    onclick="markCompleted(this)"><i class="fas fa-circle-check"></i> Completed</button>
                                            </form>
                                        </td>
                                    @elseif ($view === 'pending')
                                        <td data-label="Print">
                                            <button type="button" class="btn btn-view"
                                                title="Opens the certificate. It is moved to For Pick Up only after you confirm it printed."
                                                onclick="printCertificate({{ (int) $r['id'] }})"><i class="fas fa-print"></i> Print</button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <div class="modal-overlay" id="detailsModal" onclick="closeDetailsModalOnOverlay(event)">
        <div class="modal-box">
            <div class="modal-header">
                <div class="modal-title-group">
                    <div class="modal-doc-icon"><i class="fas fa-file-lines"></i></div>
                    <div><h3 id="modalTitle">Request Details</h3><div class="modal-subtitle" id="modalSubtitle"></div></div>
                </div>
                <button type="button" class="modal-close" onclick="closeDetailsModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody"></div>
        </div>
    </div>

    <div class="sys-overlay" id="sysDialog">
        <div class="sys-box">
            <div class="sys-icon info" id="sysIcon"></div>
            <h3 id="sysTitle"></h3><p id="sysMsg"></p>
            <div class="sys-actions" id="sysActions"></div>
        </div>
    </div>

    <script>
        window.ADMIN_SUMMARY_URL = @json(route('admin.summary'));
        window.ADMIN_NOTIFICATION_URL = @json(route('admin.notification.send'));
        window.ADMIN_ATTACHMENT_BASE = @json(url('/admin/dashboard/attachments'));
        var STATUS_BADGE_CLASS = @json($status_badge_class);
        var STATUS_LABELS = @json($status_labels);
    </script>
    <script src="{{ asset('script/admin_dashboard_page.js') }}"></script>
    @if ($view === 'pickup')
        <script>
            function markCompleted(btn) {
                var code = btn.getAttribute('data-code');
                showConfirm('Mark ' + code + ' as Completed?', function () { btn.form.submit(); }, null, 'Yes, complete it');
            }
        </script>
    @endif
    @if ($view === 'overview')
        <script>
            var ANALYTICS_DATA = {
                statusValues: @json([$analytics['counts']['Pending'], $analytics['counts']['PickUp'], $analytics['counts']['Released']]),
                typeLabels: @json(array_keys($analytics['byType'])),
                typeValues: @json(array_values($analytics['byType'])),
                monthLabels: @json(array_keys($analytics['byMonth'])),
                monthValues: @json(array_values($analytics['byMonth'])),
                profileValues: [0, 0]
            };
        </script>
        <script src="{{ asset('script/admin_analytics.js') }}"></script>
    @endif
</body>
</html>
