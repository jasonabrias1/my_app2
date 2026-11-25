<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminPageData
{
    public function for(Request $request): array
    {
        $counts = ['Pending' => 0, 'PickUp' => 0, 'Released' => 0, 'Cancelled' => 0];

        foreach (DB::table('document_requests')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get() as $row) {
            if ($row->status === 'Released') {
                $counts['Released'] += (int) $row->total;
            } elseif ($row->status === 'For Pick Up') {
                $counts['PickUp'] += (int) $row->total;
            } elseif ($row->status === 'Cancelled') {
                $counts['Cancelled'] += (int) $row->total;
            } elseif (in_array($row->status, ['Pending', 'In Review', 'Ready'], true)) {
                $counts['Pending'] += (int) $row->total;
            }
        }

        return [
            'counts' => $counts,
            'total_residents' => $this->totalResidents(),
            'total_admins' => $this->totalAdmins(),
            'admin_name' => $request->session()->get('admin_name', 'Admin'),
        ];
    }

    public function residents(): Builder
    {
        $query = DB::table('residents');

        if (Schema::hasColumn('residents', 'fullname') && Schema::hasColumn('residents', 'email')) {
            $query->whereNot(function (Builder $placeholder): void {
                $placeholder->where('fullname', 'Walk-in request placeholder')
                    ->where('email', 'like', 'walkin-placeholder%@system.invalid');
            });
        }

        return $query;
    }

    public function totalResidents(): int
    {
        return (int) $this->residents()->count();
    }

    public function totalAdmins(): int
    {
        return Schema::hasTable('admins') ? (int) DB::table('admins')->count() : 0;
    }
}
