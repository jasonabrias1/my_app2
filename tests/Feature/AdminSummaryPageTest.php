<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminSummaryController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminSummaryPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('document_requests');
        Schema::dropIfExists('residents');
        Schema::create('residents', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('suffix')->nullable();
            $table->string('address_purok')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('nationality')->nullable();
        });
        Schema::create('document_requests', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('resident_id')->nullable();
            $table->string('document_type');
            $table->text('purpose')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('suffix')->nullable();
            $table->string('address_purok')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('nationality')->nullable();
            $table->string('requestor_first_name')->nullable();
            $table->string('requestor_middle_name')->nullable();
            $table->string('requestor_last_name')->nullable();
            $table->string('requestor_suffix')->nullable();
            $table->string('status')->default('Pending');
            $table->string('tracking_code')->nullable();
            $table->dateTime('request_date')->nullable();
            $table->string('business_name')->nullable();
            $table->string('business_address')->nullable();
        });

        $this->app['view']->getFinder()->prependLocation(base_path('tests/Fixtures/views'));

        Route::get('/admin/dashboard', fn () => 'dashboard')->name('admin.dashboard');
        Route::middleware('web')->group(function (): void {
            Route::get('/admin/summary', [AdminSummaryController::class, 'index'])->name('admin.summary');
            Route::post('/admin/summary/printed', [AdminSummaryController::class, 'printed'])->name('admin.summary.printed');
            Route::get('/admin/accounts', fn () => 'accounts')->name('admin.accounts');
            Route::get('/admin/walkin', fn () => 'walkin')->name('admin.walkin');
            Route::get('/admin/residents', fn () => 'residents')->name('admin.residents');
            Route::get('/admin/audit', fn () => 'audit')->name('admin.audit');
            Route::get('/admin/logout', fn () => 'logout')->name('admin.logout');
        });
        $this->app['router']->getRoutes()->refreshNameLookups();
    }

    public function test_summary_requires_an_admin_session(): void
    {
        $this->get('/admin/summary')->assertRedirect('/admin/login');
    }

    public function test_summary_lists_request_cards_walk_ins_and_sidebar_counts(): void
    {
        $this->insertResident();
        $this->insertRequest([
            'id' => 1,
            'document_type' => 'Certificate of Residency',
            'status' => 'Pending',
            'tracking_code' => 'BR-0001',
            'first_name' => 'Maya',
            'last_name' => 'Resident',
        ]);
        $this->insertRequest([
            'id' => 2,
            'document_type' => 'Certificate of Residency',
            'status' => 'For Pick Up',
            'tracking_code' => 'WI-0002',
            'first_name' => 'Walk',
            'last_name' => 'In',
        ]);
        $this->insertRequest([
            'id' => 3,
            'document_type' => 'Certificate of Indigency',
            'status' => 'Released',
            'tracking_code' => 'BR-0003',
            'first_name' => 'Done',
            'last_name' => 'Resident',
        ]);

        $this->withSession(['admin_id' => 7, 'admin_name' => 'Summary Admin'])
            ->get('/admin/summary')
            ->assertOk()
            ->assertSee('List of Requests')
            ->assertSee('stylesheet/admin_summary.css')
            ->assertSee('script/admin_summary.js')
            ->assertSee('data-sidebar-admin="Summary Admin"', false)
            ->assertSee('data-pending="1"', false)
            ->assertSee('data-pickup="1"', false)
            ->assertSee('data-released="1"', false)
            ->assertSee('data-residents="1"', false)
            ->assertSee('All requests')
            ->assertSee('data-walkin="1"', false)
            ->assertSee('Walk-in')
            ->assertSee('Maya Resident');
    }

    public function test_certificate_preview_and_request_render_preserve_document_data_and_csrf(): void
    {
        $this->assertTrue(Route::has('admin.summary.printed'));
        $this->insertResident();
        $this->insertRequest([
            'id' => 10,
            'resident_id' => 1,
            'document_type' => 'Certificate of Residency',
            'status' => 'Pending',
            'tracking_code' => 'BR-0010',
            'first_name' => 'Maya',
            'middle_name' => '',
            'last_name' => 'Resident',
            'address_purok' => '',
            'purpose' => 'School enrollment',
        ]);

        $this->withSession(['admin_id' => 7, 'admin_name' => 'Summary Admin'])
            ->get('/admin/summary?preview=1&type=Certificate%20of%20Residency&paper=A5')
            ->assertOk()
            ->assertSee('Preview mode')
            ->assertSee('CERTIFICATE OF RESIDENCY')
            ->assertSee('A5 (148 × 210 mm)')
            ->assertSee('stylesheet/admin_certificate.css');

        $this->withSession(['admin_id' => 7, 'admin_name' => 'Summary Admin'])
            ->get('/admin/summary?id=10')
            ->assertOk()
            ->assertSee('Maya S. Resident')
            ->assertSee('Purok 4')
            ->assertSee("formData.append('_token', csrfToken)", false)
            ->assertSee('formData.append(\'id\', CERT_REQUEST_ID)', false)
            ->assertSee('School enrollment')
            ->assertSee('BR-0010')
            ->assertDontSee('Issued on:')
            ->assertSee('Yes, mark For Pick Up');
    }

    public function test_confirming_a_print_updates_only_requests_not_already_ready_or_released(): void
    {
        $this->insertRequest(['id' => 1, 'document_type' => 'Certificate', 'status' => 'Pending']);
        $this->insertRequest(['id' => 2, 'document_type' => 'Certificate', 'status' => 'For Pick Up']);
        $this->insertRequest(['id' => 3, 'document_type' => 'Certificate', 'status' => 'Released']);

        $this->withSession(['admin_id' => 7, 'admin_name' => 'Summary Admin'])
            ->post('/admin/summary/printed', ['id' => 1, 'action' => 'mark_printed'])
            ->assertOk()
            ->assertExactJson(['ok' => true]);

        $this->assertSame('For Pick Up', DB::table('document_requests')->where('id', 1)->value('status'));
        $this->assertSame('For Pick Up', DB::table('document_requests')->where('id', 2)->value('status'));
        $this->assertSame('Released', DB::table('document_requests')->where('id', 3)->value('status'));

        $this->withSession(['admin_id' => 7])
            ->post('/admin/summary/printed', ['id' => 'not-an-id', 'action' => 'mark_printed'])
            ->assertUnprocessable()
            ->assertExactJson(['ok' => false]);

        $this->withSession(['admin_id' => 7])
            ->post('/admin/summary/printed', ['id' => 2])
            ->assertUnprocessable()
            ->assertExactJson(['ok' => false]);
    }

    private function insertResident(): void
    {
        DB::table('residents')->insert([
            'id' => 1,
            'first_name' => 'Maya',
            'middle_name' => 'S.',
            'last_name' => 'Resident',
            'address_purok' => 'Purok 4',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
        ]);
    }

    private function insertRequest(array $overrides): void
    {
        DB::table('document_requests')->insert(array_merge([
            'resident_id' => 1,
            'document_type' => 'Certificate of Indigency',
            'purpose' => 'Medical Assistance',
            'first_name' => 'Resident',
            'middle_name' => '',
            'last_name' => 'Tester',
            'suffix' => '',
            'address_purok' => 'Purok 1',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'status' => 'Pending',
            'tracking_code' => 'BR-0000',
            'request_date' => '2026-11-01 10:00:00',
        ], $overrides));
    }
}
