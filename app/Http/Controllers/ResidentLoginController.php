<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResidentLoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 300;

    public function create(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('resident_id')) {
            return redirect()->route('resident.dashboard');
        }

        return view('resident.login', ['email' => '']);
    }

    public function store(Request $request): RedirectResponse
    {
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $session = $request->session();
        $lockedUntil = (int) $session->get('resident_login_locked_until', 0);

        if (now()->timestamp < $lockedUntil) {
            $minutes = (int) ceil(($lockedUntil - now()->timestamp) / 60);

            return $this->loginError($request, "Too many failed attempts. Please try again in {$minutes} minute(s).", $email);
        }

        if ($email === '' || $password === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->loginError($request, 'Incorrect email or password.', $email);
        }

        $resident = DB::table('residents')
            ->select(['id', 'fullname', 'email', 'password', 'status'])
            ->where('email', $email)
            ->first();

        if (! $resident || ! Hash::check($password, $resident->password)) {
            $this->logResident('Failed login attempt', '', $email);
            $attempts = (int) $session->get('resident_login_attempts', 0) + 1;

            if ($attempts >= self::MAX_ATTEMPTS) {
                $this->logResident('Login locked after too many failed attempts', '', $email);
                $session->put('resident_login_locked_until', now()->timestamp + self::LOCKOUT_SECONDS);
                $session->put('resident_login_attempts', 0);

                return $this->loginError($request, 'Too many failed attempts. Please try again in 5 minute(s).', $email);
            }

            $session->put('resident_login_attempts', $attempts);

            return $this->loginError($request, 'Incorrect email or password.', $email);
        }

        if ($resident->status === 'Disabled') {
            $this->logResident('Login blocked (account disabled)', '', $resident->fullname);

            return $this->loginError($request, 'Your account has been disabled. Please contact the barangay office.', $email);
        }

        $session->regenerate();
        $session->forget(['resident_login_attempts', 'resident_login_locked_until']);
        $session->put([
            'resident_id' => $resident->id,
            'resident_name' => $resident->fullname,
            'resident_email' => $resident->email,
            'resident_notification_scope' => (string) Str::uuid(),
        ]);
        $this->logResident('Logged in');

        return redirect()->route('resident.dashboard');
    }

    private function loginError(Request $request, string $message, string $email): RedirectResponse
    {
        return redirect()
            ->route('resident.login')
            ->with('resident_login_error', $message)
            ->with('resident_login_email', $email);
    }

    private function logResident(string $action, string $details = '', ?string $name = null): void
    {
        try {
            DB::table('audit_trail')->insert([
                'user_name' => $name ?: session('resident_name', 'Resident'),
                'role' => 'Resident',
                'action' => $action,
                'details' => $details,
                'created_at' => now('UTC')->toDateTimeString(),
            ]);
        } catch (QueryException $exception) {
            Log::warning('Resident audit log failed.', [
                'action' => $action,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
