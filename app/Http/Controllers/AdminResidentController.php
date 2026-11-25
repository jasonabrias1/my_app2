<?php

namespace App\Http\Controllers;

use App\Services\AdminAuditLogger;
use App\Services\AdminPageData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AdminResidentController extends Controller
{
    public function __construct(
        private readonly AdminPageData $pageData,
        private readonly AdminAuditLogger $auditLogger,
    ) {
    }

    public function createAccount(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->adminGuard($request)) {
            return $redirect;
        }

        return view('admin.create-account', $this->pageData->for($request));
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        if ($redirect = $this->adminGuard($request)) {
            return $redirect;
        }

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'address_purok' => ['required', 'in:Purok 1,Purok 2,Purok 3'],
            'civil_status' => ['required', 'in:Single,Married,Widowed,Separated'],
            'nationality' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date'],
            'place_of_birth' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'regex:/^09\d{9}$/', 'max:11', 'unique:residents,mobile_number'],
            'email' => ['required', 'email', 'max:100', 'unique:residents,email'],
            'password' => ['required', 'string', 'min:8'],
            'confirm_password' => ['required', 'same:password'],
        ], [
            'address_purok.in' => 'Please select a valid Sitio / Purok.',
            'civil_status.in' => 'Please select a valid civil status.',
            'mobile_number.regex' => 'Mobile number must be in the format 09xxxxxxxxx.',
            'mobile_number.unique' => 'An account with that mobile number already exists.',
            'email.unique' => 'An account with that email already exists.',
            'confirm_password.same' => 'Passwords do not match.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput($request->except([
                'password',
                'confirm_password',
            ]));
        }

        $data = $validator->validated();
        $fullName = collect([
            trim($data['first_name']),
            trim($data['middle_name'] ?? ''),
            trim($data['last_name']),
            trim($data['suffix'] ?? ''),
        ])->filter()->implode(' ');

        DB::table('residents')->insert([
            'fullname' => $fullName,
            'email' => trim($data['email']),
            'password' => Hash::make($data['password']),
            'first_name' => trim($data['first_name']),
            'middle_name' => trim($data['middle_name'] ?? ''),
            'last_name' => trim($data['last_name']),
            'suffix' => trim($data['suffix'] ?? ''),
            'address_purok' => $data['address_purok'],
            'civil_status' => $data['civil_status'],
            'nationality' => trim($data['nationality']),
            'date_of_birth' => $data['date_of_birth'],
            'place_of_birth' => trim($data['place_of_birth']),
            'mobile_number' => trim($data['mobile_number']),
        ]);

        $this->auditLogger->logAdmin('Created resident account', $fullName);

        return redirect()->back()->with('created_resident_name', $fullName);
    }

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->adminGuard($request)) {
            return $redirect;
        }

        $residents = $this->pageData->residents()
            ->select([
                'id',
                'fullname',
                'email',
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'address_purok',
                'civil_status',
                'nationality',
                'date_of_birth',
                'place_of_birth',
                'mobile_number',
                'status',
                'created_at',
            ])
            ->orderByDesc('id')
            ->get();

        return view('admin.residents', array_merge(
            $this->pageData->for($request),
            ['residents' => $residents],
        ));
    }

    public function toggleStatus(Request $request): RedirectResponse
    {
        if ($redirect = $this->adminGuard($request)) {
            return $redirect;
        }

        $data = $request->validate([
            'resident_id' => ['required', 'integer', 'min:1', 'exists:residents,id'],
            'new_status' => ['required', 'in:Active,Disabled'],
        ]);

        DB::table('residents')
            ->where('id', $data['resident_id'])
            ->update(['status' => $data['new_status']]);

        $this->auditLogger->logAdmin(
            $data['new_status'] === 'Disabled' ? 'Disabled resident account' : 'Enabled resident account',
            'Resident ID ' . $data['resident_id'],
        );

        return redirect()->route('admin.residents');
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($request->session()->has('admin_id')) {
            $this->auditLogger->logAdmin('Logged out');
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function adminGuard(Request $request): ?RedirectResponse
    {
        return $request->session()->get('admin_id')
            ? null
            : redirect()->route('admin.login');
    }
}
