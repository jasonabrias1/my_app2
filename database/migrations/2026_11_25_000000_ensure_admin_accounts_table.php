<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('fullname', 100);
                $table->string('email', 100)->unique();
                $table->string('password');
                $table->string('position', 100);
                $table->string('mobile_number', 11)->unique();
                $table->timestamps();
            });

            return;
        }

        Schema::table('admins', function (Blueprint $table): void {
            if (! Schema::hasColumn('admins', 'position')) {
                $table->string('position', 100)->nullable();
            }
            if (! Schema::hasColumn('admins', 'mobile_number')) {
                $table->string('mobile_number', 11)->nullable();
            }
            if (! Schema::hasColumn('admins', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (! Schema::hasColumn('admins', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Admin accounts can predate this migration; rollback must not remove their data.
    }
};
