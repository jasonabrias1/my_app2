<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DocumentRequestStatusMigrationTest extends TestCase
{
    public function test_document_request_status_column_accepts_cancelled(): void
    {
        Schema::dropIfExists('document_requests');
        Schema::create('document_requests', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('status', 20)->default('Pending');
        });

        try {
            $migration = require database_path(
                'migrations/2026_11_25_000002_allow_cancelled_document_request_status.php',
            );
            $migration->up();

            DB::table('document_requests')->insert(['status' => 'Cancelled']);

            $this->assertSame(
                'Cancelled',
                DB::table('document_requests')->value('status'),
            );
        } finally {
            Schema::dropIfExists('document_requests');
        }
    }
}
