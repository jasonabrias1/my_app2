<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Accounts - E-Serbisyo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('stylesheet/admin_dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('stylesheet/admin_residents_list.css') }}">
</head>
<body>
    @include('admin.partials.sidebar')

    <div class="main">
        <div class="topbar">
            <div class="who">Signed in as <b>{{ $admin_name }}</b></div>
        </div>
        <div class="panel">
            <h3>Resident Accounts</h3>
            @if ($residents->isEmpty())
                <div class="empty-state"><i class="fas fa-users"></i>No resident accounts yet.</div>
            @else
                <table class="resp-table">
                    <tr class="pt-head">
                        <th>#</th><th>Full Name</th><th>Email</th><th>Mobile Number</th>
                        <th>Status</th><th>Details</th><th>Actions</th>
                    </tr>
                    @foreach ($residents as $index => $acct)
                        @php
                            $fullName = trim($acct->fullname ?: implode(' ', array_filter([
                                $acct->first_name, $acct->middle_name, $acct->last_name, $acct->suffix,
                            ])));
                            $acctStatus = $acct->status ?: 'Active';
                            $details = (array) $acct;
                        @endphp
                        <tr>
                            <td data-label="No.">{{ $index + 1 }}</td>
                            <td data-label="Full Name">{{ $fullName }}</td>
                            <td data-label="Email">{{ $acct->email }}</td>
                            <td data-label="Mobile Number">{{ $acct->mobile_number }}</td>
                            <td data-label="Status"><span class="badge badge-status">{{ $acctStatus }}</span></td>
                            <td data-label="Details">
                                <button type="button" class="btn btn-view"
                                    data-details="{{ json_encode($details, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                                    onclick="openResidentModal(this)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                            <td data-label="Actions">
                                <form method="POST" action="{{ route('admin.residents.toggle') }}" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="resident_id" value="{{ $acct->id }}">
                                    @if ($acctStatus === 'Disabled')
                                        <input type="hidden" name="new_status" value="Active">
                                        <button type="submit" class="btn btn-view"><i class="fas fa-toggle-on"></i> Enable</button>
                                    @else
                                        <input type="hidden" name="new_status" value="Disabled">
                                        <button type="submit" class="btn btn-view"><i class="fas fa-toggle-off"></i> Disable</button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>
    </div>

    <div class="modal-overlay" id="residentModal" onclick="closeResidentModalOnOverlay(event)">
        <div class="modal-box">
            <div class="modal-header">
                <div class="modal-title-group">
                    <div class="modal-doc-icon"><i class="fas fa-user"></i></div>
                    <div>
                        <h3 id="residentModalTitle">Resident Details</h3>
                        <div class="modal-subtitle" id="residentModalSubtitle"></div>
                    </div>
                </div>
                <button type="button" class="modal-close" onclick="closeResidentModal()">&times;</button>
            </div>
            <div class="modal-body" id="residentModalBody"></div>
        </div>
    </div>
    <script src="{{ asset('script/admin_residents_list.js') }}"></script>
</body>
</html>
