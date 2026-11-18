<?php

namespace App\Http\Controllers;

use App\Services\AdminAuditLogger;
use App\Services\SmsNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminNotificationController extends Controller
{
    public function __construct(
        private readonly SmsNotificationService $sms,
        private readonly AdminAuditLogger $auditLogger,
    ) {
    }

    public function send(Request $request): JsonResponse
    {
        if (! $request->session()->get('admin_id')) {
            return response()->json(['success' => false, 'message' => 'Not logged in.'], 401);
        }

        if (! $request->isMethod('post')) {
            return response()->json(['success' => false, 'message' => 'Invalid request method.'], 405);
        }

        $requestId = filter_var($request->input('request_id'), FILTER_VALIDATE_INT);
        if (! $requestId || $requestId < 1) {
            return response()->json(['success' => false, 'message' => 'Invalid request ID.']);
        }

        $info = DB::table('document_requests')
            ->select([
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'mobile_number',
                'requestor_first_name',
                'requestor_middle_name',
                'requestor_last_name',
                'requestor_suffix',
                'requestor_mobile_number',
                'document_type',
            ])
            ->where('id', $requestId)
            ->first();

        if (! $info) {
            return response()->json(['success' => false, 'message' => 'Request not found.']);
        }

        $requestorName = $this->fullName([
            $info->requestor_first_name,
            $info->requestor_middle_name,
            $info->requestor_last_name,
            $info->requestor_suffix,
        ]);
        if ($requestorName === '') {
            $requestorName = $this->fullName([
                $info->first_name,
                $info->middle_name,
                $info->last_name,
                $info->suffix,
            ]);
        }

        $mobileNumber = trim((string) $info->requestor_mobile_number);
        if ($mobileNumber === '') {
            $mobileNumber = trim((string) $info->mobile_number);
        }
        if ($mobileNumber === '') {
            return response()->json([
                'success' => false,
                'message' => 'The requestor has no mobile number on file.',
            ]);
        }

        $result = $this->sms->notifyRequestCompleted(
            $requestorName,
            $mobileNumber,
            (string) $info->document_type,
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send SMS: ' . $result['response'],
            ]);
        }

        $this->auditLogger->logAdmin('Sent SMS notification', $this->auditLogger->requestLabel($requestId));

        return response()->json([
            'success' => true,
            'message' => 'SMS sent successfully.',
        ]);
    }

    private function fullName(array $parts): string
    {
        return trim(implode(' ', array_filter(
            $parts,
            static fn ($part): bool => trim((string) $part) !== '',
        )));
    }
}
