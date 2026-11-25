<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_requests') && ! Schema::hasColumn('document_requests', 'copies')) {
            Schema::table('document_requests', function (Blueprint $table): void {
                $table->unsignedTinyInteger('copies')->default(1);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('document_requests') && Schema::hasColumn('document_requests', 'copies')) {
            Schema::table('document_requests', function (Blueprint $table): void {
                $table->dropColumn('copies');
            });
        }
    }
};
