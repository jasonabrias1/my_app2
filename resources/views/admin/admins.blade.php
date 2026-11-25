<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Accounts - E-Serbisyo</title>
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
            <h3>Admin Accounts</h3>
            @if ($admins->isEmpty())
                <div class="empty-state"><i class="fas fa-user-shield"></i>No admin accounts yet.</div>
            @else
                <table class="resp-table">
                    <tr class="pt-head">
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Position</th>
                        <th>Mobile Number</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    @foreach ($admins as $index => $acct)
                        @php($acctStatus = $acct->status ?: 'Active')
                        <tr>
                            <td data-label="No.">{{ $index + 1 }}</td>
                            <td data-label="Full Name">{{ $acct->fullname }}</td>
                            <td data-label="Email">{{ $acct->email }}</td>
                            <td data-label="Position">{{ $acct->position }}</td>
                            <td data-label="Mobile Number">{{ $acct->mobile_number }}</td>
                            <td data-label="Status"><span class="badge badge-status">{{ $acctStatus }}</span></td>
                            <td data-label="Actions">
                                <form method="POST" action="{{ route('admin.admins.toggle') }}" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="admin_id" value="{{ $acct->id }}">
                                    @if ($acctStatus === 'Disabled')
                                        <input type="hidden" name="new_status" value="Active">
                                        <button type="submit" class="btn btn-view">
                                            <i class="fas fa-toggle-on"></i> Enable
                                        </button>
                                    @else
                                        <input type="hidden" name="new_status" value="Disabled">
                                        <button type="submit" class="btn btn-view">
                                            <i class="fas fa-toggle-off"></i> Disable
                                        </button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>
    </div>
</body>
</html>
