<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResidentAccountController extends Controller
{
    private const OTP_LENGTH = 6;

    private const OTP_TTL_SECONDS = 300;

    private const OTP_RESEND_COOLDOWN = 60;

    private const OTP_MAX_ATTEMPTS = 5;

    public function index(Request $request): View|RedirectResponse
    {
        $residentId = $request->session()->get('resident_id');

        if (! $residentId) {
            return redirect()->route('resident.login');
        }

        $account = DB::table('residents')
            ->select(['fullname', 'email', 'mobile_number', 'password'])
            ->where('id', $residentId)
            ->first();

        abort_if(! $account, 404);

        $userName = $request->session()->get('resident_name', $account->fullname ?: 'Resident');
        $initials = collect(preg_split('/\s+/', trim($userName)) ?: [])
            ->filter()
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->take(2)
            ->implode('') ?: 'R';
        $currentEmail = $account->email ?? '';
        $currentMobile = $account->mobile_number ?? '';
        $mobilePending = $request->session()->get('mobile_otp');
        $mobileOtpExpired = false;

        if ($mobilePending && (int) $mobilePending['resident_id'] !== (int) $residentId) {
            $request->session()->forget('mobile_otp');
            $mobilePending = null;
        } elseif ($mobilePending && now()->timestamp > $mobilePending['expires']) {
            $request->session()->forget('mobile_otp');
            $mobilePending = null;
            $mobileOtpExpired = true;
        }

        $resendWait = $mobilePending
            ? max(0, self::OTP_RESEND_COOLDOWN - (now()->timestamp - $mobilePending['last_sent']))
            : 0;
        $action = (string) $request->query('tab', $request->session()->get('resident_account_tab', 'email'));
        $activeTab = in_array($action, ['email', 'mobile', 'password'], true) ? $action : 'email';
        if ($request->session()->has('password_error') || $request->session()->has('password_success')) {
            $activeTab = 'password';
        }
        if ($mobilePending || $mobileOtpExpired || $request->session()->has('mobile_error') || $request->session()->has('mobile_success')) {
            $activeTab = 'mobile';
        }

        return view('resident.account', [
            'userName' => $userName,
            'initials' => $initials,
            'currentEmail' => $currentEmail,
            'currentMobile' => $currentMobile,
            'emailInProfile' => $currentEmail !== '' ? $currentEmail : 'No email set',
            'emailInCard' => $currentEmail !== '' ? $currentEmail : 'Not set',
            'mobileInProfile' => $this->maskMobile($currentMobile) ?: 'No number set',
            'mobileInCard' => $this->maskMobile($currentMobile) ?: 'Not set',
            'mobilePending' => $mobilePending,
            'resendWait' => $resendWait,
            'activeTab' => $activeTab,
            'emailTabClass' => $activeTab === 'email' ? 'active' : '',
            'mobileTabClass' => $activeTab === 'mobile' ? 'active' : '',
            'passwordTabClass' => $activeTab === 'password' ? 'active' : '',
            'emailTabSelected' => $activeTab === 'email' ? 'true' : 'false',
            'mobileTabSelected' => $activeTab === 'mobile' ? 'true' : 'false',
            'passwordTabSelected' => $activeTab === 'password' ? 'true' : 'false',
            'step1Class' => $mobilePending ? '' : 'on',
            'step2Class' => $mobilePending ? 'on' : '',
            'resendDisabled' => $resendWait > 0 ? 'disabled' : '',
            'emailError' => $request->session()->get('email_error', ''),
            'emailSuccess' => $request->session()->get('email_success', ''),
            'passwordError' => $request->session()->get('password_error', ''),
            'passwordSuccess' => $request->session()->get('password_success', ''),
            'mobileError' => $mobileOtpExpired
                ? 'Your verification code expired. Please start again.'
                : $request->session()->get('mobile_error', ''),
            'mobileSuccess' => $request->session()->get('mobile_success', ''),
            'mobileInfo' => $request->session()->get('mobile_info', ''),
            'otpLength' => self::OTP_LENGTH,
            'currentMobileMasked' => $this->maskMobile($currentMobile),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $residentId = $request->session()->get('resident_id');

        if (! $residentId) {
            return redirect()->route('resident.login');
        }

        $action = (string) $request->input('action', '');
        $account = DB::table('residents')
            ->select(['email', 'mobile_number', 'password'])
            ->where('id', $residentId)
            ->first();
        abort_if(! $account, 404);

        if ($action === 'change_email') {
            $newEmail = trim((string) $request->input('new_email', ''));

            if (! filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                return $this->accountRedirect('email', 'email_error', 'Please enter a valid email address.');
            }
            if (! Hash::check((string) $request->input('current_password_email', ''), $account->password)) {
                return $this->accountRedirect('email', 'email_error', 'Your current password is incorrect.');
            }
            if (Str::lower($newEmail) === Str::lower((string) $account->email)) {
                return $this->accountRedirect('email', 'email_error', "That's already your current email address.");
            }
            if (DB::table('residents')->where('email', $newEmail)->where('id', '!=', $residentId)->exists()) {
                return $this->accountRedirect('email', 'email_error', 'That email address is already in use.');
            }

            DB::table('residents')->where('id', $residentId)->update(['email' => $newEmail]);
            $this->logResident($request, 'Changed email address', $newEmail);

            return $this->accountRedirect('email', 'email_success', 'Your email address has been updated.');
        }

        if ($action === 'change_password') {
            $currentPassword = (string) $request->input('current_password', '');
            $newPassword = (string) $request->input('new_password', '');

            if (! Hash::check($currentPassword, $account->password)) {
                return $this->accountRedirect('password', 'password_error', 'Your current password is incorrect.');
            }
            if (strlen($newPassword) < 8) {
                return $this->accountRedirect('password', 'password_error', 'New password must be at least 8 characters long.');
            }
            if ($newPassword !== (string) $request->input('confirm_password', '')) {
                return $this->accountRedirect('password', 'password_error', 'New password and confirmation do not match.');
            }
            if (Hash::check($newPassword, $account->password)) {
                return $this->accountRedirect('password', 'password_error', 'New password must be different from your current password.');
            }

            DB::table('residents')->where('id', $residentId)->update(['password' => Hash::make($newPassword)]);
            $this->logResident($request, 'Changed password');

            return $this->accountRedirect('password', 'password_success', 'Your password has been updated.');
        }

        if ($action === 'send_mobile_otp') {
            $newMobile = $this->normalizeMobile((string) $request->input('new_mobile', ''));

            if (! $account->mobile_number) {
                return $this->accountRedirect('mobile', 'mobile_error', "There's no mobile number saved on your account to send the code to. Please contact the barangay office.");
            }
            if ($newMobile === '') {
                return $this->accountRedirect('mobile', 'mobile_error', 'Please enter a valid mobile number (09xxxxxxxxx).');
            }
            if ($newMobile === $account->mobile_number) {
                return $this->accountRedirect('mobile', 'mobile_error', "That's already your current mobile number.");
            }
            if (! Hash::check((string) $request->input('current_password_mobile', ''), $account->password)) {
                return $this->accountRedirect('mobile', 'mobile_error', 'Your current password is incorrect.');
            }
            if (! $this->issueMobileOtp($request, $account->mobile_number, $newMobile, $residentId)) {
                return $this->accountRedirect('mobile', 'mobile_error', "We couldn't send the verification code. Please try again.");
            }

            $this->logResident($request, 'Requested mobile number change', $newMobile);

            return $this->accountRedirect(
                'mobile',
                'mobile_info',
                'We sent a ' . self::OTP_LENGTH . '-digit code to your saved number ' . $this->maskMobile($account->mobile_number) . '.',
            );
        }

        $pending = $request->session()->get('mobile_otp');

        if ($action === 'resend_mobile_otp' && $pending) {
            $wait = self::OTP_RESEND_COOLDOWN - (now()->timestamp - $pending['last_sent']);

            if ($wait > 0) {
                $word = $wait === 1 ? 'second' : 'seconds';

                return $this->accountRedirect('mobile', 'mobile_error', "Please wait {$wait} more {$word} before requesting a new code.");
            }
            if (! $this->issueMobileOtp($request, $account->mobile_number, $pending['new_mobile'], $residentId)) {
                return $this->accountRedirect('mobile', 'mobile_error', "We couldn't send the verification code. Please try again.");
            }

            return $this->accountRedirect('mobile', 'mobile_info', 'A new code was sent to ' . $this->maskMobile($account->mobile_number) . '.');
        }

        if ($action === 'verify_mobile_otp' && $pending) {
            $enteredCode = preg_replace('/\D/', '', (string) $request->input('otp_code', ''));

            if (strlen($enteredCode) !== self::OTP_LENGTH) {
                return $this->accountRedirect('mobile', 'mobile_error', 'Please enter the ' . self::OTP_LENGTH . '-digit code.');
            }
            if (! hash_equals((string) $pending['code'], $enteredCode)) {
                $pending['attempts']++;

                if ($pending['attempts'] >= self::OTP_MAX_ATTEMPTS) {
                    $request->session()->forget('mobile_otp');

                    return $this->accountRedirect('mobile', 'mobile_error', 'Too many incorrect attempts. Please start again.');
                }

                $request->session()->put('mobile_otp', $pending);
                $left = self::OTP_MAX_ATTEMPTS - $pending['attempts'];
                $word = $left === 1 ? 'attempt' : 'attempts';

                return $this->accountRedirect('mobile', 'mobile_error', "That code is incorrect. {$left} {$word} left.");
            }

            DB::table('residents')->where('id', $residentId)->update(['mobile_number' => $pending['new_mobile']]);
            $request->session()->forget('mobile_otp');
            $this->logResident($request, 'Changed mobile number', $pending['new_mobile']);

            return $this->accountRedirect('mobile', 'mobile_success', 'Your mobile number has been updated.');
        }

        if ($action === 'cancel_mobile_otp') {
            $request->session()->forget('mobile_otp');

            return redirect()->route('resident.account')->with('resident_account_tab', 'mobile');
        }

        return redirect()->route('resident.account');
    }

    private function issueMobileOtp(Request $request, string $savedMobile, string $newMobile, int|string $residentId): bool
    {
        $token = (string) config('services.iprog_sms.token');

        if ($token === '') {
            Log::error('IPROG SMS token is not configured.');

            return false;
        }

        $code = (string) random_int(100000, 999999);
        $message = 'Your OTP is ' . $code . '. Valid for ' . (self::OTP_TTL_SECONDS / 60)
            . ' minutes. Do not share this code with anyone.';

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->post('https://www.iprogsms.com/api/v1/sms_messages', [
                    'api_token' => $token,
                    'message' => $message,
                    'phone_number' => $savedMobile,
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            Log::error('IPROG SMS request failed.', ['exception' => $exception->getMessage()]);

            return false;
        }

        if (! $response->successful()) {
            Log::error('IPROG SMS request failed.', ['status' => $response->status()]);

            return false;
        }

        $request->session()->put('mobile_otp', [
            'resident_id' => $residentId,
            'new_mobile' => $newMobile,
            'code' => $code,
            'expires' => now()->timestamp + self::OTP_TTL_SECONDS,
            'attempts' => 0,
            'last_sent' => now()->timestamp,
        ]);

        return true;
    }

    private function accountRedirect(string $tab, string $key, string $message): RedirectResponse
    {
        return redirect()->route('resident.account')->with([
            'resident_account_tab' => $tab,
            $key => $message,
        ]);
    }

    private function normalizeMobile(string $raw): string
    {
        $clean = str_replace([' ', '-'], '', trim($raw));

        return preg_match('/^09[0-9]{9}$/', $clean) ? $clean : '';
    }

    private function maskMobile(?string $number): string
    {
        if (! $number || strlen($number) < 7) {
            return $number ?? '';
        }

        return substr($number, 0, 2) . str_repeat('•', strlen($number) - 6) . substr($number, -4);
    }

    private function logResident(Request $request, string $action, string $details = ''): void
    {
        try {
            DB::table('audit_trail')->insert([
                'user_name' => $request->session()->get('resident_name', 'Resident'),
                'role' => 'Resident',
                'action' => $action,
                'details' => $details,
            ]);
        } catch (\Illuminate\Database\QueryException $exception) {
            Log::warning('Resident audit log failed.', [
                'action' => $action,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
