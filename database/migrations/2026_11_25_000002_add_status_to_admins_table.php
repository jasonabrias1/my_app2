<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admins') && ! Schema::hasColumn('admins', 'status')) {
            Schema::table('admins', function (Blueprint $table): void {
                $table->string('status', 20)->default('Active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admins') && Schema::hasColumn('admins', 'status')) {
            Schema::table('admins', function (Blueprint $table): void {
                $table->dropColumn('status');
            });
        }
    }
};
