<?php

namespace App\Http\Controllers;

use App\Services\AdminAuditLogger;
use App\Services\AdminPageData;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    private const STATUSES = ['Pending', 'For Pick Up', 'Released'];

    private const ATTACHMENT_COLUMNS = [
        'attachment_photo',
        'js_identity',
        'js_residency',
        'requestor_valid_id',
        'relationship_proof',
    ];

    public function __construct(
        private readonly AdminPageData $pageData,
        private readonly AdminAuditLogger $auditLogger,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('admin_id')) {
            return redirect()->route('admin.login');
        }

        $view = $request->query('view', 'overview');
        $view = is_string($view) && in_array($view, ['overview', 'pending', 'pickup', 'completed', 'cancelled'], true)
            ? $view
            : 'overview';
        $adminName = $request->session()->get('admin_name', 'Admin');

        $counts = ['Pending' => 0, 'PickUp' => 0, 'Released' => 0, 'Cancelled' => 0];
        foreach (DB::table('document_requests')->select('status', DB::raw('COUNT(*) AS total'))->groupBy('status')->get() as $row) {
            $key = $row->status === 'For Pick Up' ? 'PickUp' : $row->status;
            if (array_key_exists($key, $counts)) {
                $counts[$key] = (int) $row->total;
            }
        }

        $totalResidents = $this->pageData->totalResidents();
        $yearFilter = $view === 'overview' ? $this->filterNumber($request->query('year'), 2000, 2100) : 0;
        $monthFilter = $view === 'overview' ? $this->filterNumber($request->query('month'), 1, 12) : 0;
        $dayFilter = $view === 'overview' ? $this->filterNumber($request->query('day'), 1, 31) : 0;

        $baseQuery = DB::table('document_requests');
        if ($yearFilter) {
            $baseQuery->whereYear('request_date', $yearFilter);
        }
        if ($monthFilter) {
            $baseQuery->whereMonth('request_date', $monthFilter);
        }
        if ($dayFilter) {
            $baseQuery->whereDay('request_date', $dayFilter);
        }

        $analytics = [
            'byType' => [],
            'byMonth' => [],
            'counts' => ['Pending' => 0, 'PickUp' => 0, 'Released' => 0, 'Cancelled' => 0],
            'total' => 0,
            'yearOptions' => [],
        ];
        $recentRows = [];
        $listingRows = [];

        if ($view === 'overview') {
            $analytics['yearOptions'] = DB::table('document_requests')
                ->whereNotNull('request_date')
                ->orderByDesc('request_date')
                ->pluck('request_date')
                ->map(fn ($date) => Carbon::parse($date)->year)
                ->unique()
                ->sortDesc()
                ->values()
                ->all();
            if ($yearFilter && ! in_array($yearFilter, $analytics['yearOptions'], true)) {
                $analytics['yearOptions'][] = $yearFilter;
                rsort($analytics['yearOptions']);
            }

            $analyticsRows = $baseQuery->get(['status', 'document_type', 'request_date']);
            foreach ($analyticsRows as $row) {
                $statusKey = $row->status === 'For Pick Up' ? 'PickUp' : $row->status;
                if (array_key_exists($statusKey, $analytics['counts'])) {
                    $analytics['counts'][$statusKey]++;
                }

                $type = $row->document_type ?? '';
                $analytics['byType'][$type] = ($analytics['byType'][$type] ?? 0) + 1;
                if ($row->request_date) {
                    $date = Carbon::parse($row->request_date);
                    $groupKey = $yearFilter && $monthFilter ? $date->format('Y-m-d') : $date->format('Y-m');
                    $analytics['byMonth'][$groupKey] = ($analytics['byMonth'][$groupKey] ?? 0) + 1;
                }
            }
            $analytics['total'] = array_sum($analytics['counts']);
            arsort($analytics['byType']);
            ksort($analytics['byMonth']);

            $recentRows = $this->enrichRows(
                DB::table('document_requests')->where('status', 'Pending')->orderByDesc('request_date')->limit(6)->get()
            );
        } elseif ($view !== 'overview') {
            $status = [
                'pending' => 'Pending',
                'pickup' => 'For Pick Up',
                'completed' => 'Released',
                'cancelled' => 'Cancelled',
            ][$view];
            $listingRows = $this->enrichRows(
                DB::table('document_requests')->where('status', $status)->orderByDesc('request_date')->get()
            );
        }

        $flash = $request->session()->get('dashboard_flash', '');
        $flashType = $request->session()->get('dashboard_flash_type', 'success');

        return view('admin.dashboard', [
            'view' => $view,
            'admin_name' => $adminName,
            'counts' => $counts,
            'total_residents' => $totalResidents,
            'analytics' => $analytics,
            'f_year' => $yearFilter,
            'f_month' => $monthFilter,
            'f_day' => $dayFilter,
            'recent_rows' => $recentRows,
            'listing_rows' => $listingRows,
            'flash' => $flash,
            'flash_type' => $flashType,
            'status_badge_class' => [
                'Pending' => 'badge-pending',
                'For Pick Up' => 'badge-pickup',
                'Released' => 'badge-completed',
                'Cancelled' => 'badge-cancelled',
            ],
            'status_labels' => [
                'Pending' => 'Pending',
                'For Pick Up' => 'For Pick Up',
                'Released' => 'Completed',
                'Cancelled' => 'Cancelled',
            ],
            'tab_titles' => [
                'overview' => 'Dashboard',
                'pending' => 'Pending Requests',
                'pickup' => 'For Pick Up',
                'completed' => 'Completed Requests',
                'cancelled' => 'Cancelled Requests',
            ],
        ]);
    }

    public function attachment(Request $request, string $filename): BinaryFileResponse|RedirectResponse
    {
        if (! $request->session()->has('admin_id')) {
            return redirect()->route('admin.login');
        }

        abort_unless(
            basename($filename) === $filename && preg_match('/\A[\w.-]+\z/', $filename) === 1,
            404,
        );

        $requestQuery = DB::table('document_requests');
        $requestQuery->where(function ($query) use ($filename): void {
            foreach (self::ATTACHMENT_COLUMNS as $column) {
                $query->orWhere($column, $filename);
            }
        });
        abort_unless($requestQuery->exists(), 404);

        $privatePath = Storage::disk('local')->path('resident-uploads/' . $filename);
        $path = realpath($privatePath);

        if ($path === false || ! is_file($path)) {
            $uploadDirectory = realpath(base_path('../final2/backfrontend/residentpage/uploads'));
            abort_if($uploadDirectory === false, 404);
            $path = realpath($uploadDirectory . DIRECTORY_SEPARATOR . $filename);
            abort_unless(
                $path !== false
                    && is_file($path)
                    && str_starts_with($path, $uploadDirectory . DIRECTORY_SEPARATOR),
                404,
            );
        } else {
            $privateDirectory = realpath(dirname($privatePath));
            abort_unless(
                $privateDirectory !== false
                    && str_starts_with($path, $privateDirectory . DIRECTORY_SEPARATOR),
                404,
            );
        }

        abort_unless(
            in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'], true),
            404,
        );

        return response()->file($path, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function update(Request $request): RedirectResponse
    {
        if (! $request->session()->has('admin_id')) {
            return redirect()->route('admin.login');
        }

        $requestId = (int) $request->input('request_id', 0);
        $newStatus = $request->input('new_status');
        $currentView = $this->safeView($request->input('current_view', 'overview'));
        $flashType = 'success';

        if ($requestId < 1 || ! is_string($newStatus) || ! in_array($newStatus, self::STATUSES, true)) {
            $message = 'Invalid status.';
            $flashType = 'error';
            $redirectView = $currentView;
        } else {
            try {
                $row = DB::table('document_requests')->where('id', $requestId)->first();
                if (! $row) {
                    $message = "Could not update request #{$requestId}.";
                    $flashType = 'error';
                    $redirectView = $currentView;
                } else {
                    DB::table('document_requests')->where('id', $requestId)->update(['status' => $newStatus]);
                    $this->auditLogger->logAdmin(
                        'Changed request status to '.$newStatus,
                        $this->auditLogger->requestLabel($requestId),
                    );
                    $message = "Request #{$requestId} updated.";
                    $redirectView = [
                        'Released' => 'completed',
                        'Pending' => 'pending',
                        'For Pick Up' => 'pickup',
                    ][$newStatus];
                }
            } catch (QueryException $exception) {
                Log::warning('Admin request status update failed.', [
                    'request_id' => $requestId,
                    'exception' => $exception->getMessage(),
                ]);
                $message = "Could not update request #{$requestId}.";
                $flashType = 'error';
                $redirectView = $currentView;
            }
        }

        return redirect()
            ->route('admin.dashboard', ['view' => $redirectView])
            ->with('dashboard_flash', $message)
            ->with('dashboard_flash_type', $flashType);
    }

    private function filterNumber(mixed $value, int $min, int $max): int
    {
        if (! is_scalar($value) || ! is_numeric($value)) {
            return 0;
        }
        $number = (int) $value;

        return $number >= $min && $number <= $max ? $number : 0;
    }

    private function safeView(mixed $view): string
    {
        return is_string($view) && in_array($view, ['overview', 'pending', 'pickup', 'completed'], true)
            ? $view
            : 'overview';
    }

    private function enrichRows(iterable $rows): array
    {
        $rows = collect($rows);
        if ($rows->isEmpty()) {
            return [];
        }

        $residentIds = $rows->pluck('resident_id')->filter()->unique()->values();
        $residents = DB::table('residents')
            ->whereIn('id', $residentIds)
            ->get([
                'id', 'fullname', 'first_name', 'middle_name', 'last_name', 'suffix',
                'address_purok', 'civil_status', 'nationality', 'date_of_birth',
                'place_of_birth', 'mobile_number', 'email',
            ])
            ->keyBy('id');

        return $rows->map(function ($row) use ($residents): array {
            $data = (array) $row;
            $resident = $residents->get($row->resident_id);
            if ($resident) {
                foreach ((array) $resident as $key => $value) {
                    if (! array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
                        $data[$key] = $value;
                    }
                }
            }

            return $data;
        })->all();
    }

}
