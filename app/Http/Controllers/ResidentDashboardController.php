<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResidentDashboardController extends Controller
{
    private const ATTACHMENT_COLUMNS = [
        'attachment_photo',
        'js_identity',
        'js_residency',
        'requestor_valid_id',
        'relationship_proof',
    ];

    public function index(Request $request): View|Response|RedirectResponse
    {
        $residentId = $request->session()->get('resident_id');

        if (! $residentId) {
            return redirect()->route('resident.login');
        }

        $notificationScope = $request->session()->get('resident_notification_scope');
        if (! is_string($notificationScope) || $notificationScope === '') {
            $notificationScope = (string) Str::uuid();
            $request->session()->put('resident_notification_scope', $notificationScope);
        }

        $userName = $request->session()->get('resident_name', 'Resident');
        $initials = collect(preg_split('/\s+/', trim($userName)) ?: [])
            ->filter()
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->take(2)
            ->implode('');

        $statusLabels = [
            'Pending' => 'Pending',
            'For Pick Up' => 'For Pick Up',
            'Released' => 'Completed',
        ];

        $badgeMap = [
            'Pending' => 'badge-pending',
            'For Pick Up' => 'badge-processing',
            'Released' => 'badge-completed',
        ];

        $recentRequests = DB::table('document_requests')
            ->where('resident_id', $residentId)
            ->orderByDesc('request_date')
            ->limit(5)
            ->get();

        $readyRequest = DB::table('document_requests')
            ->select(['id', 'document_type', 'tracking_code'])
            ->where('resident_id', $residentId)
            ->where('status', 'For Pick Up')
            ->orderByDesc('request_date')
            ->first();

        return response()->view('resident.dashboard', compact(
            'userName',
            'initials',
            'statusLabels',
            'badgeMap',
            'recentRequests',
            'readyRequest',
            'notificationScope',
        ))->header('Cache-Control', 'no-store, private, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function attachment(Request $request, string $filename): BinaryFileResponse|RedirectResponse
    {
        $residentId = $request->session()->get('resident_id');

        if (! $residentId) {
            return redirect()->route('resident.login');
        }

        abort_unless(
            basename($filename) === $filename && preg_match('/\A[\w.-]+\z/', $filename) === 1,
            404,
        );

        $requestQuery = DB::table('document_requests')->where('resident_id', $residentId);
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

    public function logout(Request $request): RedirectResponse
    {
        if ($request->session()->has('resident_id')) {
            try {
                DB::table('audit_trail')->insert([
                    'user_name' => $request->session()->get('resident_name', 'Resident'),
                    'role' => 'Resident',
                    'action' => 'Logged out',
                    'details' => '',
                ]);
            } catch (\Illuminate\Database\QueryException $exception) {
                Log::warning('Resident audit log failed.', [
                    'action' => 'Logged out',
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('resident.login');
    }
}
