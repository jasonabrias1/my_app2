<?php

namespace App\Http\Controllers;

use App\Services\AdminAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    public function __construct(private readonly AdminAuditLogger $auditLogger)
    {
    }

    public function create(): View
    {
        return view('admin.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $email = trim((string) $request->input('email', ''));
        $password = trim((string) $request->input('password', ''));

        $admin = DB::table('admins')
            ->select(['id', 'fullname', 'email', 'password', 'status'])
            ->where('email', $email)
            ->first();

        if (! $admin || $admin->status !== 'Active' || ! Hash::check($password, $admin->password)) {
            $this->auditLogger->log($email, 'Admin', 'Failed login attempt');

            return redirect()
                ->route('admin.login')
                ->with('admin_login_error', 'Invalid admin credentials.')
                ->with('admin_login_email', $email);
        }

        $request->session()->regenerate();
        $request->session()->put([
            'admin_id' => $admin->id,
            'admin_name' => $admin->fullname,
        ]);
        $this->auditLogger->logAdmin('Logged in');

        return redirect()->route('admin.dashboard');
    }
}
