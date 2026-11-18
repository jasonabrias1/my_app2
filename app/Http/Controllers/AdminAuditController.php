<?php

namespace App\Http\Controllers;

use App\Services\AdminAuditLogger;
use App\Services\AdminPageData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditController extends Controller
{
    public function __construct(
        private readonly AdminPageData $pageData,
        private readonly AdminAuditLogger $auditLogger,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (! $request->session()->get('admin_id')) {
            return redirect()->route('admin.login');
        }

        $this->auditLogger->ensureTableExists();
        $logs = \Illuminate\Support\Facades\DB::table('audit_trail')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.audit', array_merge(
            $this->pageData->for($request),
            ['logs' => $logs],
        ));
    }
}
