<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminAuditController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AdminResidentController;
use App\Services\AdminAuditLogger;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminResidentPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('audit_trail');
        Schema::dropIfExists('document_requests');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('residents');
        Schema::create('admins', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('fullname', 100);
            $table->string('email', 100)->unique();
            $table->string('password');
            $table->string('position', 100);
            $table->string('mobile_number', 11)->unique();
            $table->timestamps();
        });
        Schema::create('residents', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('fullname');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('suffix')->nullable();
            $table->string('address_purok')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('nationality')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('status')->default('Active');
            $table->timestamp('created_at')->useCurrent();
        });
        Schema::create('document_requests', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('status');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('mobile_number')->nullable();
            $table->string('document_type');
            $table->string('requestor_first_name')->nullable();
            $table->string('requestor_middle_name')->nullable();
            $table->string('requestor_last_name')->nullable();
            $table->string('requestor_suffix')->nullable();
            $table->string('requestor_mobile_number')->nullable();
        });

        Route::get('/__test/admin/dashboard', fn () => response())->name('admin.dashboard')->middleware('web');
        Route::get('/__test/admin/accounts', [AdminResidentController::class, 'createAccount'])
            ->name('admin.accounts')->middleware('web');
        Route::post('/__test/admin/accounts', [AdminResidentController::class, 'storeAccount'])
            ->name('admin.accounts.store')->middleware('web');
        Route::get('/__test/admin/residents', [AdminResidentController::class, 'index'])
            ->name('admin.residents')->middleware('web');
        Route::post('/__test/admin/residents', [AdminResidentController::class, 'toggleStatus'])
            ->name('admin.residents.toggle')->middleware('web');
        Route::get('/__test/admin/audit', [AdminAuditController::class, 'index'])
            ->name('admin.audit')->middleware('web');
        Route::post('/__test/admin/notification', [AdminNotificationController::class, 'send'])
            ->name('admin.notification.send')->middleware('web');
        Route::get('/__test/admin/walkin', fn () => response())->name('admin.walkin')->middleware('web');
        Route::get('/__test/admin/summary', fn () => response())->name('admin.summary')->middleware('web');
        Route::get('/__test/admin/logout', [AdminResidentController::class, 'logout'])
            ->name('admin.logout')->middleware('web');
        Route::getRoutes()->refreshNameLookups();
        app('url')->setRoutes(Route::getRoutes());
    }

    public function test_account_form_is_admin_only_and_creates_a_hashed_resident(): void
    {
        $this->get(route('admin.accounts'))
            ->assertRedirect(route('admin.login'));

        $this->withSession(['admin_id' => 1, 'admin_name' => 'Office Tester'])
            ->get(route('admin.accounts'))
            ->assertOk()
            ->assertSee('Create Resident Account')
            ->assertSee('name="_token"', false)
            ->assertSee('maxlength="11"', false)
            ->assertSee(route('admin.residents'), false)
            ->assertSee(route('admin.audit'), false);

        $this->withSession(['admin_id' => 1, 'admin_name' => 'Office Tester'])
            ->post(route('admin.accounts.store'), $this->validResident())
            ->assertRedirect(route('admin.accounts'));

        $resident = DB::table('residents')->where('email', 'resident@example.com')->first();
        $this->assertNotNull($resident);
        $this->assertSame('Ada M Resident Jr.', $resident->fullname);
        $this->assertTrue(Hash::check('resident-password', $resident->password));
        $this->assertNotSame('resident-password', $resident->password);
        $this->assertSame('Created resident account', DB::table('audit_trail')->value('action'));

        $this->withSession(['admin_id' => 1, 'admin_name' => 'Office Tester'])
            ->from(route('admin.accounts'))
            ->post(route('admin.accounts.store'), array_merge(
                $this->validResident(),
                ['email' => 'another-resident@example.com'],
            ))
            ->assertRedirect(route('admin.accounts'))
            ->assertSessionHasErrors('mobile_number');

        $this->assertSame(1, DB::table('residents')->count());
    }

    public function test_admin_account_form_saves_a_hashed_admin_account(): void
    {
        $this->withSession(['admin_id' => 1, 'admin_name' => 'Office Tester'])
            ->get(route('admin.accounts', ['type' => 'admin']))
            ->assertOk()
            ->assertSee('Create Admin Account')
            ->assertSee('name="account_type" value="admin"', false)
            ->assertSee('name="position"', false);

        $this->withSession(['admin_id' => 1, 'admin_name' => 'Office Tester'])
            ->from(route('admin.accounts', ['type' => 'admin']))
            ->post(route('admin.accounts.store', ['type' => 'admin']), $this->validAdmin())
            ->assertRedirect(route('admin.accounts', ['type' => 'admin']))
            ->assertSessionHas('created_account_name', 'Morgan A. Admin');

        $admin = DB::table('admins')->where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertSame('Morgan A. Admin', $admin->fullname);
        $this->assertSame('Barangay Secretary', $admin->position);
        $this->assertSame('09987654321', $admin->mobile_number);
        $this->assertTrue(Hash::check('admin-password', $admin->password));
        $this->assertNotSame('admin-password', $admin->password);
        $this->assertSame('Created admin account', DB::table('audit_trail')->value('action'));

        $this->post(route('admin.login.store'), [
            'email' => 'admin@example.com',
            'password' => 'admin-password',
        ])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('admin_id', $admin->id)
            ->assertSessionHas('admin_name', 'Morgan A. Admin');
    }

    public function test_resident_list_never_renders_password_and_status_can_be_toggled(): void
    {
        DB::table('residents')->insert([
            'fullname' => 'Riley Resident',
            'email' => 'riley@example.com',
            'password' => 'private-password-hash',
            'first_name' => 'Riley',
            'last_name' => 'Resident',
            'mobile_number' => '09123456789',
            'status' => 'Active',
        ]);

        $this->withSession(['admin_id' => 1, 'admin_name' => 'Office Tester'])
            ->get(route('admin.residents'))
            ->assertOk()
            ->assertSee('Riley Resident')
            ->assertSee('riley@example.com')
            ->assertSee('action="' . route('admin.residents.toggle') . '"', false)
            ->assertDontSee('private-password-hash');

        $this->withSession(['admin_id' => 1, 'admin_name' => 'Office Tester'])
            ->post(route('admin.residents.toggle'), [
                'resident_id' => 1,
                'new_status' => 'Disabled',
            ])
            ->assertRedirect(route('admin.residents'));

        $this->assertSame('Disabled', DB::table('residents')->where('id', 1)->value('status'));
        $this->assertSame('Disabled resident account', DB::table('audit_trail')->value('action'));

        $this->withSession(['admin_id' => 1, 'admin_name' => 'Office Tester'])
            ->get(route('admin.residents'))
            ->assertOk()
            ->assertSee('Enable')
            ->assertSee('action="' . route('admin.residents.toggle') . '"', false);

        $this->withSession(['admin_id' => 1, 'admin_name' => 'Office Tester'])
            ->post(route('admin.residents.toggle'), [
                'resident_id' => 1,
                'new_status' => 'Active',
            ])
            ->assertRedirect(route('admin.residents'));

        $this->assertSame('Active', DB::table('residents')->where('id', 1)->value('status'));
        $this->assertSame(
            'Enabled resident account',
            DB::table('audit_trail')->orderByDesc('id')->value('action'),
        );
    }

    public function test_audit_page_paginates_latest_entries(): void
    {
        app(AdminAuditLogger::class)->ensureTableExists();

        for ($i = 1; $i <= 21; $i++) {
            DB::table('audit_trail')->insert([
                'created_at' => now()->addSeconds($i),
                'user_name' => 'Office Tester',
                'role' => 'Admin',
                'action' => 'Activity ' . $i,
                'details' => '',
            ]);
        }

        $this->withSession(['admin_id' => 1, 'admin_name' => 'Office Tester'])
            ->get(route('admin.audit'))
            ->assertOk()
            ->assertSee('21 records found')
            ->assertSee('Activity 21')
            ->assertDontSee('>Activity 1</td>', false)
            ->assertSee('Page 1 of 2')
            ->assertSee('class="main audit-main"', false)
            ->assertSee('class="panel audit-panel"', false)
            ->assertSee('class="resp-table audit-table"', false);

        $this->withSession(['admin_id' => 1, 'admin_name' => 'Office Tester'])
            ->get(route('admin.audit', ['page' => 2]))
            ->assertOk()
            ->assertSee('Activity 1')
            ->assertSee('Page 2 of 2')
            ->assertSee('class="main audit-main"', false)
            ->assertSee('class="panel audit-panel"', false)
            ->assertSee('class="resp-table audit-table"', false);
    }

    public function test_sms_notification_uses_provider_service_and_audits_success(): void
    {
        DB::table('document_requests')->insert([
            'id' => 7,
            'status' => 'Released',
            'first_name' => 'Riley',
            'last_name' => 'Resident',
            'mobile_number' => '09123456789',
            'document_type' => 'Barangay Clearance',
            'requestor_first_name' => 'Lance',
            'requestor_last_name' => 'Hanz',
            'requestor_mobile_number' => '09987654321',
        ]);
        Http::fake([
            'www.iprogsms.com/*' => Http::response(['status' => 200], 200),
        ]);

        $this->withSession(['admin_id' => 1, 'admin_name' => 'Office Tester'])
            ->postJson(route('admin.notification.send'), ['request_id' => 7])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'SMS sent successfully.',
            ]);

        Http::assertSent(fn (ClientRequest $request): bool =>
            $request->url() === 'https://www.iprogsms.com/api/v1/sms_messages'
            && $request['phone_number'] === '639987654321'
            && $request['message'] === "Hi Lance Hanz, your \"Barangay Clearance\" at E-Serbisyo Homestead is complete and ready for pick up. Please bring a valid ID.\n\n"
                .'If someone else will claim it on your behalf, they must bring an authorization letter, a photocopy of your valid ID, and a photocopy of their valid ID.'
        );
        $this->assertSame('Sent SMS notification', DB::table('audit_trail')->value('action'));
        $this->assertSame('REQ-0007 (Barangay Clearance)', DB::table('audit_trail')->value('details'));
    }

    public function test_logout_clears_the_admin_session_and_records_the_event(): void
    {
        $this->withSession(['admin_id' => 1, 'admin_name' => 'Office Tester'])
            ->get(route('admin.logout'))
            ->assertRedirect(route('admin.login'))
            ->assertSessionMissing('admin_id')
            ->assertSessionMissing('admin_name');

        $this->assertSame('Logged out', DB::table('audit_trail')->value('action'));
    }

    private function validResident(): array
    {
        return [
            'first_name' => 'Ada',
            'middle_name' => 'M',
            'last_name' => 'Resident',
            'suffix' => 'Jr.',
            'address_purok' => 'Purok 2',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'date_of_birth' => '2000-01-02',
            'place_of_birth' => 'Homestead',
            'mobile_number' => '09123456789',
            'email' => 'resident@example.com',
            'password' => 'resident-password',
            'confirm_password' => 'resident-password',
        ];
    }

    private function validAdmin(): array
    {
        return [
            'account_type' => 'admin',
            'first_name' => 'Morgan',
            'middle_name' => 'A.',
            'last_name' => 'Admin',
            'position' => 'Barangay Secretary',
            'mobile_number' => '09987654321',
            'email' => 'admin@example.com',
            'password' => 'admin-password',
            'confirm_password' => 'admin-password',
        ];
    }
}
