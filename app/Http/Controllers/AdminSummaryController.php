<?php

namespace App\Http\Controllers;

use App\Services\AdminAuditLogger;
use App\Services\AdminPageData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminSummaryController extends Controller
{
    public function __construct(
        private readonly AdminPageData $pageData,
        private readonly AdminAuditLogger $auditLogger,
    ) {
    }

    private const PAPER_SIZES = [
        'A3' => ['w' => 297, 'h' => 420, 'zoom' => 1.35],
        'A4' => ['w' => 210, 'h' => 297, 'zoom' => 1],
        'A5' => ['w' => 148, 'h' => 210, 'zoom' => 0.72],
        'Letter' => ['w' => 216, 'h' => 279, 'zoom' => 1],
        'Legal' => ['w' => 216, 'h' => 356, 'zoom' => 1.05],
        'Long' => ['w' => 216, 'h' => 330, 'zoom' => 1.05],
    ];

    public function index(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('admin_id')) {
            return redirect()->route('admin.login');
        }

        if ($request->query->has('id') || $request->query->has('preview')) {
            return $this->certificate($request);
        }

        $summaryRows = DB::table('document_requests')
            ->orderByDesc('request_date')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

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

        $byType = DB::table('document_requests')
            ->select('document_type', DB::raw('COUNT(*) as total'))
            ->groupBy('document_type')
            ->orderByDesc('total')
            ->pluck('total', 'document_type')
            ->map(fn ($count) => (int) $count)
            ->all();

        $total_residents = $this->pageData->totalResidents();
        $admin_name = $request->session()->get('admin_name', 'Admin');

        return view('admin.summary', compact(
            'summaryRows',
            'counts',
            'byType',
            'total_residents',
            'admin_name',
        ));
    }

    public function printed(Request $request): JsonResponse|RedirectResponse
    {
        if (! $request->session()->has('admin_id')) {
            return redirect()->route('admin.login');
        }

        if ($request->input('action') !== 'mark_printed') {
            return response()->json(['ok' => false], 422);
        }

        $requestId = filter_var($request->input('id'), FILTER_VALIDATE_INT);
        if (! $requestId || $requestId < 1) {
            return response()->json(['ok' => false], 422);
        }

        $requestRecord = DB::table('document_requests')
            ->where('id', $requestId)
            ->first(['status', 'tracking_code']);
        $isWalkIn = $requestRecord
            && str_starts_with((string) $requestRecord->tracking_code, 'WI-');

        if ($isWalkIn) {
            $changed = DB::table('document_requests')
                ->where('id', $requestId)
                ->where('status', '!=', 'Released')
                ->update(['status' => 'Released']);
        } else {
            $changed = DB::table('document_requests')
                ->where('id', $requestId)
                ->whereNotIn('status', ['For Pick Up', 'Released'])
                ->update(['status' => 'For Pick Up']);
        }

        if ($changed > 0) {
            $this->auditLogger->logAdmin(
                'Printed certificate',
                $this->auditLogger->requestLabel($requestId),
            );
        }

        return response()->json(['ok' => true]);
    }

    private function certificate(Request $request): View
    {
        $requestId = $request->query('id');
        if ($requestId !== null && (! ctype_digit((string) $requestId) || (int) $requestId < 1)) {
            abort(404);
        }

        $isSample = $requestId === null;
        if ($isSample) {
            $data = [
                'first_name' => 'Juan',
                'middle_name' => 'Santos',
                'last_name' => 'Dela Cruz',
                'suffix' => '',
                'civil_status' => 'Single',
                'nationality' => 'Filipino',
                'address_purok' => 'Purok 3',
                'document_type' => $request->query('type', 'indigency'),
                'purpose' => 'Medical Assistance',
                'business_name' => "Juan's Sari-Sari Store",
                'business_address' => 'Purok 3, Homestead, Bambang, Nueva Vizcaya',
                'request_date' => now()->toDateString(),
            ];
        } else {
            $data = (array) DB::table('document_requests')
                ->where('id', (int) $requestId)
                ->first() ?? [];
            if ($data === []) {
                abort(404, 'Request not found.');
            }

            if (! empty($data['resident_id'])) {
                $resident = DB::table('residents')
                    ->select([
                        'first_name',
                        'middle_name',
                        'last_name',
                        'suffix',
                        'address_purok',
                        'civil_status',
                        'nationality',
                    ])
                    ->where('id', $data['resident_id'])
                    ->first();
                if ($resident) {
                    $resident = (array) $resident;
                    foreach ($resident as $key => $value) {
                        if (! array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
                            $data[$key] = $value;
                        }
                    }
                    if (empty($data['address_purok']) && ! empty($resident['address_purok'])) {
                        $data['address_purok'] = $resident['address_purok'];
                    }
                }
            }
        }

        if (empty($data['address_purok'])) {
            $data['address_purok'] = $data['purok'] ?? '';
        }
        $data['purok_label'] = $this->purokLabel((string) $data['address_purok']);
        $data['citizenship'] = $data['citizenship'] ?? $data['nationality'] ?? 'Filipino';

        $fullName = trim(implode(' ', array_filter([
            $data['first_name'] ?? '',
            $data['middle_name'] ?? '',
            $data['last_name'] ?? '',
            $data['suffix'] ?? '',
        ], fn ($part) => trim((string) $part) !== '')));
        $requestedBy = trim(implode(' ', array_filter([
            $data['requestor_first_name'] ?? '',
            $data['requestor_middle_name'] ?? '',
            $data['requestor_last_name'] ?? '',
            $data['requestor_suffix'] ?? '',
        ], fn ($part) => trim((string) $part) !== '')));
        $data['requested_by'] = $data['requested_by'] ?? ($requestedBy !== '' ? $requestedBy : $fullName);

        $docType = $this->normalizeDocType((string) ($isSample
            ? $request->query('type', $data['document_type'] ?? 'indigency')
            : ($data['document_type'] ?? 'indigency')));
        $isWalkIn = str_starts_with((string) ($data['tracking_code'] ?? ''), 'WI-');
        $paperKey = (string) $request->query('paper', 'A4');
        if (! array_key_exists($paperKey, self::PAPER_SIZES)) {
            $paperKey = 'A4';
        }

        try {
            $issueDate = \Illuminate\Support\Carbon::parse($data['request_date'] ?? now());
        } catch (\Throwable) {
            $issueDate = now();
        }

        $businessAddress = trim((string) ($data['business_address'] ?? ''));
        if ($businessAddress === '') {
            $businessAddress = trim($this->purokLabel((string) $data['address_purok']) . ', Homestead', ' ,');
        }

        return view('admin.certificate', [
            'data' => $data,
            'requestId' => (int) ($requestId ?? 0),
            'isSample' => $isSample,
            'isWalkIn' => $isWalkIn,
            'needsCompletion' => ! $isSample && ! in_array($data['status'] ?? '', ['For Pick Up', 'Released'], true),
            'docType' => $docType,
            'fullName' => $fullName,
            'businessName' => trim((string) ($data['business_name'] ?? '')),
            'businessAddress' => $businessAddress,
            'selectedPurpose' => (string) ($data['purpose'] ?? ''),
            'purposeOrDefault' => ($data['purpose'] ?? '') !== '' ? $data['purpose'] : 'whatever legal purpose it may serve',
            'issueDay' => $issueDate->format('jS'),
            'issueMonth' => $issueDate->format('F'),
            'issueYear' => $issueDate->format('Y'),
            'paperSizes' => self::PAPER_SIZES,
            'paperKey' => $paperKey,
            'paper' => self::PAPER_SIZES[$paperKey],
            'markPrintedUrl' => route('admin.summary.printed'),
            'dashboardUrl' => route('admin.dashboard', ['view' => $isWalkIn ? 'completed' : 'pickup']),
        ]);
    }

    private function normalizeDocType(string $raw): string
    {
        $raw = strtolower($raw);
        if (str_contains($raw, 'indigen')) {
            return 'indigency';
        }
        if (str_contains($raw, 'business')) {
            return 'business_permit';
        }
        if (str_contains($raw, 'jobseeker') || str_contains($raw, 'job seeker')) {
            return 'jobseeker';
        }
        if (str_contains($raw, 'clearance')) {
            return 'clearance';
        }
        if (str_contains($raw, 'reside')) {
            return 'residency';
        }
        if (str_contains($raw, 'moral')) {
            return 'good_moral';
        }
        if (str_contains($raw, 'certificate')) {
            return 'barangay_certificate';
        }

        return 'indigency';
    }

    private function purokLabel(string $value): string
    {
        $value = trim($value);

        return $value === '' || stripos($value, 'purok') !== false ? $value : 'Purok ' . $value;
    }

}
