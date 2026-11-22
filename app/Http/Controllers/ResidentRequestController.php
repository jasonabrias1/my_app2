<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResidentRequestController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $residentId = $request->session()->get('resident_id');

        if (! $residentId) {
            return redirect()->route('resident.login');
        }

        $statusGroups = [
            'Pending' => 'Pending',
            'For Pick Up' => 'For Pick Up',
            'Completed' => 'Released',
        ];
        $filter = $request->query('status');
        $filter = is_string($filter) && isset($statusGroups[$filter]) ? $filter : '';

        $query = DB::table('document_requests')->where('resident_id', $residentId);

        if ($filter !== '') {
            $query->where('status', $statusGroups[$filter]);
        }

        $requests = $query->orderByDesc('request_date')->get()->map(fn ($row) => (array) $row);
        $userName = $request->session()->get('resident_name', 'Resident');
        $uploadPath = url('/resident/dashboard/attachments') . '/';
        $initials = collect(preg_split('/\s+/', trim($userName)) ?: [])
            ->filter()
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->take(2)
            ->implode('');

        return view('resident.requests', compact(
            'statusGroups',
            'filter',
            'requests',
            'userName',
            'initials',
            'uploadPath',
        ));
    }

    public function tracking(Request $request): View|RedirectResponse
    {
        $residentId = $request->session()->get('resident_id');

        if (! $residentId) {
            return redirect()->route('resident.login');
        }

        $trackingCode = '';

        if ($request->isMethod('post')) {
            $trackingCode = trim((string) $request->input('tracking_no', ''));
        } elseif ($request->query('auto_track') === '1') {
            $trackingCode = trim((string) $request->query('tracking_no', ''));
        }

        $record = null;
        $notFound = false;

        if ($trackingCode !== '') {
            $record = DB::table('document_requests')
                ->where('resident_id', $residentId)
                ->where('tracking_code', $trackingCode)
                ->first();

            if ($record) {
                try {
                    DB::table('audit_trail')->insert([
                        'user_name' => $request->session()->get('resident_name', 'Resident'),
                        'role' => 'Resident',
                        'action' => 'Tracked request',
                        'details' => 'REQ-' . str_pad((string) $record->id, 4, '0', STR_PAD_LEFT)
                            . ' (' . $record->document_type . ')',
                        'created_at' => now('UTC')->toDateTimeString(),
                    ]);
                } catch (\Illuminate\Database\QueryException $exception) {
                    Log::warning('Resident audit log failed.', [
                        'action' => 'Tracked request',
                        'exception' => $exception->getMessage(),
                    ]);
                }

                $record = (array) $record;
            } else {
                $notFound = true;
            }
        }

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
        $steps = $statusLabels;
        $stepKeys = array_keys($steps);
        $currentStepIndex = $record ? array_search($record['status'], $stepKeys, true) : -1;
        $currentStepIndex = $currentStepIndex === false ? -1 : $currentStepIndex;
        $isCompleted = $record && $record['status'] === 'Released';
        $userName = $request->session()->get('resident_name', 'Resident');
        $uploadPath = url('/resident/dashboard/attachments') . '/';
        $initials = collect(preg_split('/\s+/', trim($userName)) ?: [])
            ->filter()
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->take(2)
            ->implode('');

        return view('resident.tracking', compact(
            'trackingCode',
            'record',
            'notFound',
            'steps',
            'stepKeys',
            'currentStepIndex',
            'isCompleted',
            'statusLabels',
            'badgeMap',
            'userName',
            'initials',
            'uploadPath',
        ));
    }
}
