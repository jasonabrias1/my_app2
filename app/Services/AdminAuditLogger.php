<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdminAuditLogger
{
    public function logAdmin(string $action, string $details = ''): bool
    {
        return $this->log(
            (string) session('admin_name', 'Admin'),
            'Admin',
            $action,
            $details,
        );
    }

    public function log(string $user, string $role, string $action, string $details = ''): bool
    {
        $this->ensureTable();

        try {
            DB::table('audit_trail')->insert([
                'user_name' => $user !== '' ? $user : 'Admin',
                'role' => $role,
                'action' => $action,
                'details' => $details,
                'created_at' => Carbon::now('UTC')->toDateTimeString(),
            ]);

            return true;
        } catch (QueryException $exception) {
            Log::warning('Admin audit log failed.', [
                'action' => $action,
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function requestLabel(int $requestId): string
    {
        $label = 'REQ-' . str_pad((string) $requestId, 4, '0', STR_PAD_LEFT);

        try {
            $documentType = DB::table('document_requests')
                ->where('id', $requestId)
                ->value('document_type');

            if (is_string($documentType) && $documentType !== '') {
                $label .= ' (' . $documentType . ')';
            }
        } catch (QueryException $exception) {
            Log::warning('Could not load request details for audit log.', [
                'exception' => $exception->getMessage(),
            ]);
        }

        return $label;
    }

    public function ensureTableExists(): void
    {
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        try {
            if (! Schema::hasTable('audit_trail')) {
                Schema::create('audit_trail', function (Blueprint $table): void {
                    $table->increments('id');
                    $table->dateTime('created_at')->useCurrent();
                    $table->string('user_name', 150);
                    $table->string('role', 30);
                    $table->string('action', 100);
                    $table->string('details', 255)->default('');
                    $table->index('created_at', 'idx_created');
                    $table->index('role', 'idx_role');
                });
            }
        } catch (\Throwable $exception) {
            Log::warning('Could not ensure audit trail table exists.', [
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
