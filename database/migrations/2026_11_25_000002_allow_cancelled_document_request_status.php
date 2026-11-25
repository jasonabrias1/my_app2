<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_requests') && Schema::hasColumn('document_requests', 'status')) {
            Schema::table('document_requests', function (Blueprint $table): void {
                $table->string('status', 32)->default('Pending')->change();
            });
        }
    }

    public function down(): void
    {
        // Keep the widened status column so cancelled requests remain valid.
    }
};
