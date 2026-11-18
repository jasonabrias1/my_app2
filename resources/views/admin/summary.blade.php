<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List of Requests - E-Serbisyo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('stylesheet/admin_dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('stylesheet/admin_summary.css') }}">
</head>
<body>
    @include('admin.partials.sidebar')

    <div class="main">
        <div class="topbar">
            <h2>List of Requests</h2>
            <div class="topbar-actions">
                <button type="button" class="btn-print-summary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
                <button type="button" class="btn-print-summary" id="btnPdf" onclick="downloadPDF(this)">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </button>
                <div class="who">Signed in as <b>{{ $admin_name }}</b></div>
            </div>
        </div>

        <div class="panel">
            <div class="sum-head" style="display:flex;align-items:center;justify-content:center;gap:20px;text-align:center;margin-bottom:10px;">
                @if (file_exists(public_path('logo/MunicipalSeal.jpg')))
                    <img src="{{ asset('logo/MunicipalSeal.jpg') }}" alt="Municipality of Bambang Seal"
                        class="sum-logo" style="width:70px;height:70px;object-fit:contain;flex-shrink:0;">
                @else
                    <div class="sum-logo" style="width:70px;height:70px;border:1px dashed #999;display:flex;align-items:center;justify-content:center;font-size:9px;color:#999;text-align:center;flex-shrink:0;">BAMBANG<br>LOGO</div>
                @endif
                <div>
                    <p style="margin:1px 0;">Republic of the Philippines</p>
                    <p style="margin:1px 0;font-weight:bold;">Province of Nueva Vizcaya</p>
                    <p style="margin:1px 0;">Municipality of Bambang</p>
                    <p class="sum-office" style="margin:6px 0 0 0;font-size:17px;font-weight:bold;letter-spacing:0.5px;">OFFICE OF THE PUNONG BARANGAY</p>
                </div>
                @if (file_exists(public_path('logo/HomesteadLogo.jpg')))
                    <img src="{{ asset('logo/HomesteadLogo.jpg') }}" alt="Barangay Logo"
                        class="sum-logo" style="width:70px;height:70px;object-fit:contain;flex-shrink:0;border:2px solid #000;border-radius:50%;">
                @else
                    <div class="sum-logo" style="width:70px;height:70px;border:2px solid #000;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#999;text-align:center;flex-shrink:0;">BARANGAY<br>LOGO</div>
                @endif
            </div>
            <hr style="border:none;border-top:2px solid #222;width:55%;margin:0 auto 20px auto;">

            @if (empty($summaryRows))
                <div class="empty-state"><i class="fas fa-folder-open"></i>No requests recorded yet.</div>
            @else
                @php
                    $cardList = ['__all__' => count($summaryRows)] + $byType;
                    $cardColors = [
                        ['#dbeafe', '#1d4ed8'], ['#dcfce7', '#16a34a'], ['#fef3c7', '#d97706'],
                        ['#ede9fe', '#7c3aed'], ['#fee2e2', '#dc2626'], ['#cffafe', '#0e7490'],
                    ];
                    $walkinCount = collect($summaryRows)->filter(fn ($row) =>
                        str_starts_with((string) ($row['tracking_code'] ?? ''), 'WI-')
                        || ($row['request_source'] ?? '') === 'walkin'
                    )->count();
                    $colorIndex = 0;
                @endphp
                <div class="sum-cards" style="--cols:{{ count($cardList) + 1 }};">
                    @foreach ($cardList as $type => $count)
                        @php
                            $all = $type === '__all__';
                            [$background, $foreground] = $all
                                ? ['#f1f5f9', '#475569']
                                : $cardColors[($colorIndex++) % count($cardColors)];
                            $title = $all ? 'All requests' : (string) $type;
                            $normalizedType = strtolower((string) $type);
                            $iconMap = [
                                'indigen' => 'fa-hand-holding-heart', 'business' => 'fa-store',
                                'job' => 'fa-briefcase', 'reside' => 'fa-house-user',
                                'moral' => 'fa-handshake', 'clearance' => 'fa-file-circle-check',
                            ];
                            $icon = 'fa-file-lines';
                            foreach ($iconMap as $needle => $mappedIcon) {
                                if (str_contains($normalizedType, $needle)) {
                                    $icon = $mappedIcon;
                                    break;
                                }
                            }
                        @endphp
                        <button type="button" class="sum-card" data-all="{{ $all ? 1 : 0 }}"
                            data-type="{{ $all ? '' : $type }}" data-title="{{ $title }}" onclick="showList(this)">
                            <i class="fas fa-arrow-right sum-arrow"></i>
                            <div class="sum-icon" style="background:{{ $background }};color:{{ $foreground }};">
                                <i class="fas {{ $all ? 'fa-layer-group' : $icon }}"></i>
                            </div>
                            <div class="sum-title">{{ $title }}</div>
                            <div class="sum-desc">{{ $count }} request{{ $count === 1 ? '' : 's' }} submitted</div>
                        </button>
                    @endforeach
                    <button type="button" class="sum-card" id="walkinCard" data-all="0" data-type=""
                        data-walkin="1" data-title="Walk-in requests" onclick="showList(this)">
                        <i class="fas fa-arrow-right sum-arrow"></i>
                        <div class="sum-icon" style="background:#fce7f3;color:#be185d;"><i class="fas fa-person-walking"></i></div>
                        <div class="sum-title">Walk-in</div>
                        <div class="sum-desc">{{ $walkinCount }} request{{ $walkinCount === 1 ? '' : 's' }} submitted</div>
                    </button>
                </div>
                <p class="sum-hint" id="sumHint">Click a box to see its list of requests.</p>

                <div id="listWrap" style="display:none;">
                    <table id="listTable">
                        <tr>
                            <th>No.</th><th>Name</th><th>Type of Documents</th><th>Date</th>
                        </tr>
                        @foreach ($summaryRows as $index => $row)
                            @php
                                $isWalkin = str_starts_with((string) ($row['tracking_code'] ?? ''), 'WI-')
                                    || ($row['request_source'] ?? '') === 'walkin';
                                $date = strtotime((string) ($row['request_date'] ?? ''));
                            @endphp
                            <tr data-type="{{ $row['document_type'] ?? '' }}" data-walkin="{{ $isWalkin ? 1 : 0 }}">
                                <td class="rownum">{{ $index + 1 }}</td>
                                <td>{{ trim(implode(' ', array_filter([
                                    $row['first_name'] ?? '', $row['middle_name'] ?? '',
                                    $row['last_name'] ?? '', $row['suffix'] ?? '',
                                ], fn ($part) => trim((string) $part) !== ''))) }}</td>
                                <td>{{ $row['document_type'] ?? '' }}</td>
                                <td>{{ $date ? date('M j, Y', $date) : '' }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="{{ asset('script/admin_summary.js') }}"></script>
    <script>
        (function () {
            var originalShowList = window.showList;
            window.showList = function (card) {
                if (card.getAttribute('data-walkin') !== '1') {
                    return originalShowList.apply(this, arguments);
                }
                var cards = document.querySelectorAll('.sum-card');
                for (var i = 0; i < cards.length; i++) cards[i].classList.remove('active');
                card.classList.add('active');

                var hint = document.getElementById('sumHint');
                if (hint) hint.style.display = 'none';
                document.getElementById('listWrap').style.display = '';

                var rows = document.querySelectorAll('#listTable tr[data-type]');
                var count = 0;
                for (var j = 0; j < rows.length; j++) {
                    var show = rows[j].getAttribute('data-walkin') === '1';
                    rows[j].style.display = show ? '' : 'none';
                    if (show) {
                        count++;
                        rows[j].querySelector('.rownum').textContent = count;
                    }
                }
                if (count === 0 && hint) {
                    hint.textContent = 'No walk-in requests yet.';
                    hint.style.display = '';
                    document.getElementById('listWrap').style.display = 'none';
                }
            };
        })();
    </script>
</body>
</html>
