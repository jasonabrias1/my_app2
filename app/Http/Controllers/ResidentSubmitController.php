<?php

namespace App\Http\Controllers;

use App\Support\NameRules;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class ResidentSubmitController extends Controller
{
    private const DOCUMENT_TYPES = [
        'Barangay Certificate / Clearance',
        'Certificate of Residency',
        'Certificate of Indigency',
        'Certificate of Good Moral Character',
        'First-Time Job Seeker',
        'Barangay Business Clearance',
    ];

    private const RELATIONSHIPS = [
        'You',
        'Mother/Father',
        'Brother/Sister',
        'Grandmother/Grandfather',
        'Son/Daughter',
        'Grandson/Granddaughter',
    ];

    private const FILE_FIELDS = [
        'valid_id' => 'valid_id',
        'js_identity' => 'js_identity',
        'js_residency' => 'js_residency',
        'requestor_valid_id' => 'requestor_id',
        'relationship_proof' => 'relationship',
    ];

    public function create(Request $request): View|RedirectResponse
    {
        $residentId = $request->session()->get('resident_id');

        if (! $residentId) {
            return redirect()->route('resident.login');
        }

        $resident = DB::table('residents')
            ->select([
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
                'email',
            ])
            ->where('id', $residentId)
            ->first();

        $residentInfo = [
            'first_name' => '',
            'middle_name' => '',
            'last_name' => '',
            'suffix' => '',
            'address_purok' => '',
            'civil_status' => '',
            'nationality' => 'Filipino',
            'date_of_birth' => '',
            'place_of_birth' => '',
            'mobile_number' => '',
            'email_address' => '',
        ];

        if ($resident) {
            foreach (array_keys($residentInfo) as $field) {
                $source = $field === 'email_address' ? 'email' : $field;

                if ($resident->{$source} !== null && $resident->{$source} !== '') {
                    $residentInfo[$field] = $resident->{$source};
                }
            }
        }

        $userName = $request->session()->get('resident_name', 'Resident');
        $initials = collect(preg_split('/\s+/', trim($userName)) ?: [])
            ->filter()
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->take(2)
            ->implode('');

        return view('resident.submit', compact('residentInfo', 'userName', 'initials'));
    }

    public function store(Request $request): RedirectResponse
    {
        $residentId = $request->session()->get('resident_id');

        if (! $residentId) {
            return redirect()->route('resident.login');
        }

        $isSelf = in_array($request->input('relationship_to_resident', 'You'), ['', 'You'], true);
        $documentType = (string) $request->input('request_type', '');
        $isJobSeeker = $documentType === 'First-Time Job Seeker';
        $isBusiness = $documentType === 'Barangay Business Clearance';

        $rules = [
            'request_type' => ['required', 'string', 'in:' . implode(',', self::DOCUMENT_TYPES)],
            'copies' => ['required', 'integer', 'min:1', 'max:99'],
            'purpose' => ['required', 'string'],
            'relationship_to_resident' => ['required', 'string', 'in:' . implode(',', self::RELATIONSHIPS)],
            'first_name' => NameRules::required(),
            'middle_name' => NameRules::optional(),
            'last_name' => NameRules::required(),
            'suffix' => NameRules::optional(20),
            'address_purok' => ['required', 'string', 'in:Purok 1,Purok 2,Purok 3'],
            'civil_status' => ['required', 'string', 'in:Single,Married,Widowed,Separated'],
            'nationality' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date_format:Y-m-d'],
            'place_of_birth' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'regex:/^09[0-9]{9}$/', 'max:11'],
            'email_address' => ['nullable', 'email', 'max:150'],
            'business_name' => [$isBusiness ? 'required' : 'nullable', 'string', 'max:255'],
            'business_nature' => [$isBusiness ? 'required' : 'nullable', 'string', 'max:255'],
            'business_address' => [$isBusiness ? 'required' : 'nullable', 'string', 'max:255'],
            'requestor_first_name' => $isSelf ? NameRules::optional() : NameRules::required(),
            'requestor_middle_name' => NameRules::optional(),
            'requestor_last_name' => $isSelf ? NameRules::optional() : NameRules::required(),
            'requestor_suffix' => NameRules::optional(20),
            'requestor_address' => [$isSelf ? 'nullable' : 'required', 'string', 'max:255'],
            'requestor_mobile_number' => [$isSelf ? 'nullable' : 'required', 'regex:/^09[0-9]{9}$/', 'max:11'],
            'requestor_email_address' => ['nullable', 'email', 'max:150'],
            'js_id_type' => [$isJobSeeker ? 'required' : 'nullable', 'string', 'max:100'],
            'data_privacy' => [$isJobSeeker ? 'accepted' : 'nullable'],
            'valid_id' => [$isSelf && ! $isJobSeeker ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'extensions:jpg,jpeg,png,pdf', 'max:5120'],
            'js_identity' => [$isJobSeeker ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'extensions:jpg,jpeg,png,pdf', 'max:5120'],
            'js_residency' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'extensions:jpg,jpeg,png,pdf', 'max:5120'],
            'requestor_valid_id' => [$isSelf ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png,pdf', 'extensions:jpg,jpeg,png,pdf', 'max:5120'],
            'relationship_proof' => [$isSelf ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png,pdf', 'extensions:jpg,jpeg,png,pdf', 'max:5120'],
        ];

        $validated = $request->validate($rules, [
            'data_privacy.accepted' => 'Please agree to the Data Privacy statement.',
            '*.required' => 'Please complete all required fields.',
            '*.mimes' => 'Uploaded documents must be JPG, PNG, or PDF files.',
            '*.extensions' => 'Uploaded documents must be JPG, PNG, or PDF files.',
            '*.max' => 'Uploaded documents must not exceed 5MB.',
        ]);

        $storedFiles = [];

        try {
            $files = [];

            foreach (self::FILE_FIELDS as $field => $prefix) {
                if (! $request->hasFile($field)) {
                    $files[$field] = null;
                    continue;
                }

                $upload = $request->file($field);
                $filename = $this->storeUpload($upload, $prefix, $residentId);
                $files[$field] = $filename;
                $storedFiles[] = $filename;
            }

            $jobSeekerIdentity = $isJobSeeker ? $files['js_identity'] : null;
            $requestorId = $isSelf ? null : $files['requestor_valid_id'];
            $attachment = $isJobSeeker
                ? $jobSeekerIdentity
                : ($isSelf ? $files['valid_id'] : $requestorId);

            $trackingCode = 'BR-' . date('Y') . '-' . Str::upper(Str::random(6));

            $requestId = DB::table('document_requests')->insertGetId([
                'resident_id' => $residentId,
                'document_type' => trim($validated['request_type']),
                'copies' => (int) $validated['copies'],
                'purpose' => trim($validated['purpose']),
                'first_name' => trim($validated['first_name']),
                'middle_name' => trim($validated['middle_name'] ?? ''),
                'last_name' => trim($validated['last_name']),
                'suffix' => trim($validated['suffix'] ?? ''),
                'address_street' => '',
                'address_block' => '',
                'address_purok' => trim($validated['address_purok']),
                'civil_status' => $validated['civil_status'],
                'nationality' => trim($validated['nationality']),
                'date_of_birth' => $validated['date_of_birth'],
                'place_of_birth' => trim($validated['place_of_birth']),
                'mobile_number' => trim($validated['mobile_number']),
                'email_address' => trim($validated['email_address'] ?? ''),
                'relationship_to_resident' => $validated['relationship_to_resident'] ?: 'You',
                'requestor_first_name' => trim($validated['requestor_first_name'] ?? ''),
                'requestor_middle_name' => trim($validated['requestor_middle_name'] ?? ''),
                'requestor_last_name' => trim($validated['requestor_last_name'] ?? ''),
                'requestor_suffix' => trim($validated['requestor_suffix'] ?? ''),
                'requestor_address' => trim($validated['requestor_address'] ?? ''),
                'requestor_mobile_number' => trim($validated['requestor_mobile_number'] ?? ''),
                'requestor_email_address' => trim($validated['requestor_email_address'] ?? ''),
                'status' => 'Pending',
                'tracking_code' => $trackingCode,
                'attachment_photo' => $attachment,
                'business_name' => trim($validated['business_name'] ?? ''),
                'business_address' => trim($validated['business_address'] ?? ''),
                'business_nature' => ($validated['business_nature'] ?? '') !== ''
                    ? trim($validated['business_nature'])
                    : null,
                'js_id_type' => $isJobSeeker ? trim($validated['js_id_type']) : null,
                'js_identity' => $jobSeekerIdentity,
                'js_residency' => $isJobSeeker ? $files['js_residency'] : null,
                'requestor_valid_id' => $requestorId,
                'relationship_proof' => $isSelf ? null : $files['relationship_proof'],
                'data_privacy' => $isJobSeeker ? 1 : 0,
            ]);
        } catch (QueryException | RuntimeException $exception) {
            foreach ($storedFiles as $filename) {
                Storage::disk('local')->delete('resident-uploads/' . $filename);
            }

            Log::error('Resident request submission failed.', [
                'resident_id' => $residentId,
                'exception' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('resident.submit')
                ->with('resident_submit_error', 'Request submission failed. Check your connection.');
        }

        try {
            DB::table('audit_trail')->insert([
                'user_name' => $request->session()->get('resident_name', 'Resident'),
                'role' => 'Resident',
                'action' => 'Submitted request',
                'details' => 'REQ-' . str_pad((string) $requestId, 4, '0', STR_PAD_LEFT)
                    . ' (' . $validated['request_type'] . ')',
                'created_at' => now('UTC')->toDateTimeString(),
            ]);
        } catch (QueryException $exception) {
            Log::warning('Resident audit log failed.', [
                'action' => 'Submitted request',
                'exception' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('resident.submit')
            ->with('resident_submit_success', $trackingCode);
    }

    private function storeUpload(UploadedFile $upload, string $prefix, int|string $residentId): string
    {
        $extension = strtolower($upload->getClientOriginalExtension());
        $filename = $prefix . '_' . $residentId . '_' . Str::lower(Str::random(13)) . '.' . $extension;

        if (! $upload->storeAs('resident-uploads', $filename, 'local')) {
            throw new RuntimeException('Unable to store uploaded resident request document.');
        }

        return $filename;
    }
}
