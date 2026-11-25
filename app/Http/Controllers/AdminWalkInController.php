<?php

namespace App\Http\Controllers;

use App\Services\AdminAuditLogger;
use App\Services\AdminPageData;
use App\Support\NameRules;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminWalkInController extends Controller
{
    public function __construct(
        private readonly AdminPageData $pageData,
        private readonly AdminAuditLogger $auditLogger,
    ) {
    }

    private const DOCUMENT_TYPES = [
        'Barangay Certificate / Clearance',
        'Certificate of Residency',
        'Certificate of Indigency',
        'Certificate of Good Moral Character',
        'First-Time Job Seeker',
        'Barangay Business Clearance',
    ];

    private const PUROKS = ['Purok 1', 'Purok 2', 'Purok 3'];

    private const CIVIL_STATUSES = ['Single', 'Married', 'Widowed', 'Separated'];

    private const RELATIONSHIPS = [
        'You',
    ];

    private const JOB_SEEKER_ID_TYPES = [
        'National ID (PhilID) / ePhilID',
        'Birth Certificate (PSA / Local Civil Registrar)',
        'Student ID',
        'Passport',
        'UMID',
        'Postal ID',
    ];

    private const FIELDS = [
        'request_type',
        'purpose',
        'business_name',
        'business_nature',
        'business_address',
        'relationship_to_resident',
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
        'email_address',
        'requestor_first_name',
        'requestor_middle_name',
        'requestor_last_name',
        'requestor_suffix',
        'requestor_address',
        'requestor_mobile_number',
        'requestor_email_address',
        'js_id_type',
    ];

    public function index(Request $request): View|RedirectResponse
    {
        if (! $request->session()->get('admin_id')) {
            return redirect()->route('admin.login');
        }

        $counts = ['Pending' => 0, 'PickUp' => 0, 'Released' => 0];
        foreach (DB::table('document_requests')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get() as $row) {
            if ($row->status === 'Pending') {
                $counts['Pending'] = (int) $row->total;
            } elseif ($row->status === 'For Pick Up') {
                $counts['PickUp'] = (int) $row->total;
            } elseif ($row->status === 'Released') {
                $counts['Released'] = (int) $row->total;
            }
        }

        $adminName = $request->session()->get('admin_name', 'Admin');
        $values = array_fill_keys(self::FIELDS, '');
        $values['nationality'] = 'Filipino';
        $values['relationship_to_resident'] = 'You';

        foreach (self::FIELDS as $field) {
            $values[$field] = $request->old($field, $values[$field]);
        }

        $dataPrivacy = (bool) $request->old('data_privacy', false);
        $done = $request->session()->get('walkin_done');

        return view('admin.walkin', [
            'counts' => $counts,
            'total_residents' => $this->pageData->totalResidents(),
            'admin_name' => $adminName,
            'values' => $values,
            'data_privacy' => $dataPrivacy,
            'done' => $done,
            'document_types' => self::DOCUMENT_TYPES,
            'puroks' => self::PUROKS,
            'civil_statuses' => self::CIVIL_STATUSES,
            'relationships' => self::RELATIONSHIPS,
            'job_seeker_id_types' => self::JOB_SEEKER_ID_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->session()->get('admin_id')) {
            return redirect()->route('admin.login');
        }

        $nationality = trim((string) $request->input('nationality', ''));
        $relationship = trim((string) $request->input('relationship_to_resident', ''));
        $request->merge([
            'nationality' => $nationality !== '' ? $nationality : 'Filipino',
            'relationship_to_resident' => $relationship !== '' ? $relationship : 'You',
        ]);

        $documentType = (string) $request->input('request_type', '');
        $relationship = (string) $request->input('relationship_to_resident', 'You');
        $isSelf = $relationship === 'You';
        $isBusiness = $documentType === 'Barangay Business Clearance';
        $isJobSeeker = $documentType === 'First-Time Job Seeker';

        $rules = [
            'request_type' => ['required', 'string', 'in:'.implode(',', self::DOCUMENT_TYPES)],
            'purpose' => ['required', 'string'],
            'relationship_to_resident' => ['required', 'string', 'in:'.implode(',', self::RELATIONSHIPS)],
            'first_name' => NameRules::required(),
            'middle_name' => NameRules::optional(),
            'last_name' => NameRules::required(),
            'suffix' => NameRules::optional(20),
            'address_purok' => ['required', 'string', 'in:'.implode(',', self::PUROKS)],
            'civil_status' => ['required', 'string', 'in:'.implode(',', self::CIVIL_STATUSES)],
            'nationality' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date_format:Y-m-d'],
            'place_of_birth' => ['required', 'string', 'max:255'],
            'mobile_number' => ['nullable', 'regex:/^09[0-9]{9}$/', 'max:20'],
            'email_address' => ['nullable', 'email', 'max:150'],
            'business_name' => [$isBusiness ? 'required' : 'nullable', 'string', 'max:255'],
            'business_nature' => [$isBusiness ? 'required' : 'nullable', 'string', 'max:255'],
            'business_address' => [$isBusiness ? 'required' : 'nullable', 'string', 'max:255'],
            'requestor_first_name' => $isSelf ? NameRules::optional() : NameRules::required(),
            'requestor_middle_name' => NameRules::optional(),
            'requestor_last_name' => $isSelf ? NameRules::optional() : NameRules::required(),
            'requestor_suffix' => NameRules::optional(20),
            'requestor_address' => [$isSelf ? 'nullable' : 'required', 'string', 'max:255'],
            'requestor_mobile_number' => [$isSelf ? 'nullable' : 'required', 'regex:/^09[0-9]{9}$/', 'max:20'],
            'requestor_email_address' => ['nullable', 'email', 'max:150'],
            'js_id_type' => [$isJobSeeker ? 'required' : 'nullable', 'string', 'in:'.implode(',', self::JOB_SEEKER_ID_TYPES)],
            'data_privacy' => [$isJobSeeker ? 'accepted' : 'nullable'],
        ];

        $messages = [
            'request_type.required' => 'Please choose a document.',
            'request_type.in' => 'Please choose a document.',
            'purpose.required' => 'Purpose is required.',
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'address_purok.in' => 'Please select a valid Purok.',
            'civil_status.in' => 'Please select a valid civil status.',
            'nationality.required' => 'Nationality is required.',
            'date_of_birth.required' => 'A valid date of birth is required.',
            'date_of_birth.date_format' => 'A valid date of birth is required.',
            'place_of_birth.required' => 'Place of birth is required.',
            'mobile_number.regex' => 'Mobile number must be in the format 09xxxxxxxxx.',
            'email_address.email' => 'Email address is not valid.',
            'relationship_to_resident.in' => 'Please select a valid relationship.',
            'business_name.required' => 'Business name is required.',
            'business_nature.required' => 'Nature of business is required.',
            'business_address.required' => 'Business address is required.',
            'js_id_type.required' => 'Please select the type of proof of identity.',
            'data_privacy.accepted' => 'The resident must agree to the Data Privacy statement.',
            'requestor_first_name.required' => "Requestor's first name is required.",
            'requestor_last_name.required' => "Requestor's last name is required.",
            'requestor_address.required' => "Requestor's address is required.",
            'requestor_mobile_number.required' => "Requestor's mobile number must be in the format 09xxxxxxxxx.",
            'requestor_mobile_number.regex' => "Requestor's mobile number must be in the format 09xxxxxxxxx.",
        ];

        $validated = $request->validate($rules, $messages);
        $validated['nationality'] = trim($validated['nationality'] ?: 'Filipino');
        $validated['relationship_to_resident'] = $relationship ?: 'You';

        if ($isSelf) {
            foreach ([
                'requestor_first_name',
                'requestor_middle_name',
                'requestor_last_name',
                'requestor_suffix',
                'requestor_address',
                'requestor_mobile_number',
                'requestor_email_address',
            ] as $field) {
                $validated[$field] = '';
            }
        }

        try {
            [$requestId, $trackingCode, $fullName] = DB::transaction(function () use ($validated, $isBusiness, $isJobSeeker): array {
                $residentId = $this->walkInPlaceholderId();
                $trackingCode = $this->newTrackingCode();

                $requestId = DB::table('document_requests')->insertGetId([
                    'resident_id' => $residentId,
                    'document_type' => trim($validated['request_type']),
                    'purpose' => trim($validated['purpose']),
                    'first_name' => trim($validated['first_name']),
                    'middle_name' => trim($validated['middle_name'] ?? ''),
                    'last_name' => trim($validated['last_name']),
                    'suffix' => trim($validated['suffix'] ?? ''),
                    'address_street' => '',
                    'address_block' => '',
                    'address_purok' => trim($validated['address_purok']),
                    'civil_status' => $validated['civil_status'],
                    'nationality' => $validated['nationality'],
                    'date_of_birth' => $validated['date_of_birth'],
                    'place_of_birth' => trim($validated['place_of_birth']),
                    'mobile_number' => trim($validated['mobile_number'] ?? ''),
                    'email_address' => trim($validated['email_address'] ?? ''),
                    'relationship_to_resident' => $validated['relationship_to_resident'],
                    'requestor_first_name' => trim($validated['requestor_first_name'] ?? ''),
                    'requestor_middle_name' => trim($validated['requestor_middle_name'] ?? ''),
                    'requestor_last_name' => trim($validated['requestor_last_name'] ?? ''),
                    'requestor_suffix' => trim($validated['requestor_suffix'] ?? ''),
                    'requestor_address' => trim($validated['requestor_address'] ?? ''),
                    'requestor_mobile_number' => trim($validated['requestor_mobile_number'] ?? ''),
                    'requestor_email_address' => trim($validated['requestor_email_address'] ?? ''),
                    'status' => 'Pending',
                    'tracking_code' => $trackingCode,
                    'attachment_photo' => null,
                    'business_name' => $isBusiness ? trim($validated['business_name']) : '',
                    'business_address' => $isBusiness ? trim($validated['business_address']) : '',
                    'business_nature' => $isBusiness && trim($validated['business_nature']) !== ''
                        ? trim($validated['business_nature'])
                        : null,
                    'js_id_type' => $isJobSeeker ? $validated['js_id_type'] : null,
                    'js_identity' => null,
                    'js_residency' => null,
                    'requestor_valid_id' => null,
                    'relationship_proof' => null,
                    'data_privacy' => $isJobSeeker && $validated['data_privacy'] ? 1 : 0,
                ]);

                $fullName = trim(implode(' ', array_filter([
                    $validated['first_name'],
                    $validated['middle_name'] ?? '',
                    $validated['last_name'],
                    $validated['suffix'] ?? '',
                ], static fn (string $part): bool => trim($part) !== '')));

                return [$requestId, $trackingCode, $fullName];
            });
        } catch (QueryException $exception) {
            Log::error('Walk-in request insert failed.', [
                'exception' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('admin.walkin')
                ->withInput()
                ->withErrors(['request' => 'Could not save the request. Please try again.']);
        }

        $this->auditLogger->log(
            $fullName,
            'Walk-in',
            'Walk-in request',
            $this->auditLogger->requestLabel((int) $requestId)
                .' - encoded by '.$request->session()->get('admin_name', 'Admin'),
        );

        return redirect()
            ->route('admin.walkin')
            ->with('walkin_done', [
                'id' => (int) $requestId,
                'code' => $trackingCode,
                'name' => $fullName,
                'doc' => $validated['request_type'],
            ]);
    }

    private function walkInPlaceholderId(): int
    {
        $placeholder = DB::table('residents')
            ->where('fullname', self::walkInPlaceholderName())
            ->where('email', 'like', 'walkin-placeholder%@system.invalid')
            ->first(['id']);

        if ($placeholder) {
            return (int) $placeholder->id;
        }

        $email = 'walkin-placeholder@system.invalid';
        if (DB::table('residents')->where('email', $email)->exists()) {
            $email = 'walkin-placeholder-'.Str::lower(Str::random(12)).'@system.invalid';
        }

        $placeholderData = [
            'fullname' => self::walkInPlaceholderName(),
            'email' => $email,
            'password' => Hash::make(Str::random(64)),
        ];

        if (Schema::hasColumn('residents', 'status')) {
            $placeholderData['status'] = 'Disabled';
        }
        if (Schema::hasColumn('residents', 'created_at')) {
            $placeholderData['created_at'] = now();
        }

        return (int) DB::table('residents')->insertGetId($placeholderData);
    }

    private function newTrackingCode(): string
    {
        do {
            $code = 'WI-'.now()->format('Y').'-'.Str::upper(Str::random(6));
        } while (DB::table('document_requests')->where('tracking_code', $code)->exists());

        return $code;
    }

    private static function walkInPlaceholderName(): string
    {
        return 'Walk-in request placeholder';
    }

}
