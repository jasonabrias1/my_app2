<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminLoginPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('audit_trail');
        Schema::dropIfExists('admins');
        Schema::create('admins', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('fullname', 100);
            $table->string('email', 100);
            $table->string('password');
            $table->string('status', 20)->default('Active');
        });
    }

    public function test_official_portal_link_opens_the_migrated_login_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('/admin/login');

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Official Login')
            ->assertSee('stylesheet/admin_dashboard.css')
            ->assertSee('stylesheet/admin_login.css')
            ->assertSee('script/admin_login.js')
            ->assertSee('name="_token"', false);
    }

    public function test_admin_credentials_create_the_legacy_admin_session_and_audit_entry(): void
    {
        DB::table('admins')->insert([
            'fullname' => 'Office Tester',
            'email' => 'office@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $this->post('/admin/login', [
            'email' => 'office@example.com',
            'password' => 'secret-password',
        ])
            ->assertRedirect('/admin/dashboard')
            ->assertSessionHas('admin_id', 1)
            ->assertSessionHas('admin_name', 'Office Tester');

        $this->assertSame('Logged in', DB::table('audit_trail')->value('action'));
    }

    public function test_invalid_admin_credentials_show_the_original_error_and_are_audited(): void
    {
        DB::table('admins')->insert([
            'fullname' => 'Office Tester',
            'email' => 'office@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $this->post('/admin/login', [
            'email' => 'office@example.com',
            'password' => 'wrong-password',
        ])
            ->assertRedirect('/admin/login')
            ->assertSessionHas('admin_login_error', 'Invalid admin credentials.')
            ->assertSessionHas('admin_login_email', 'office@example.com');

        $this->assertSame('Failed login attempt', DB::table('audit_trail')->value('action'));
    }

    public function test_disabled_admin_accounts_cannot_log_in(): void
    {
        DB::table('admins')->insert([
            'fullname' => 'Disabled Admin',
            'email' => 'disabled@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'Disabled',
        ]);

        $this->post('/admin/login', [
            'email' => 'disabled@example.com',
            'password' => 'secret-password',
        ])
            ->assertRedirect('/admin/login')
            ->assertSessionHas('admin_login_error', 'Invalid admin credentials.')
            ->assertSessionMissing('admin_id');

        $this->assertSame('Failed login attempt', DB::table('audit_trail')->value('action'));
    }
}
