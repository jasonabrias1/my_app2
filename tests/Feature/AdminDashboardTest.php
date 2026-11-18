<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminDashboardController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('audit_trail');
        Schema::dropIfExists('document_requests');
        Schema::dropIfExists('residents');

        Schema::create('residents', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('fullname')->nullable();
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
            $table->string('email')->nullable();
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
            $table->string('mobile_number')->nullable();
            $table->string('email_address')->nullable();
            $table->string('status');
            $table->string('tracking_code')->nullable();
            $table->timestamp('request_date')->nullable();
            $table->string('attachment_photo')->nullable();
            $table->string('js_identity')->nullable();
            $table->string('js_residency')->nullable();
            $table->string('requestor_valid_id')->nullable();
            $table->string('relationship_proof')->nullable();
        });
        Schema::create('audit_trail', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('user_name');
            $table->string('role');
            $table->string('action');
            $table->string('details');
        });

        if (! Route::has('admin.dashboard')) {
            Route::middleware('web')->get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        }
        if (! Route::has('admin.dashboard.update')) {
            Route::middleware('web')->post('/admin/dashboard/update', [AdminDashboardController::class, 'update'])->name('admin.dashboard.update');
        }
        foreach (['accounts', 'walkin', 'residents', 'summary', 'audit', 'logout'] as $name) {
            $routeName = 'admin.'.$name;
            if (! Route::has($routeName)) {
                Route::middleware('web')->get('/__test/admin/'.$name, fn () => '')->name($routeName);
            }
        }
        Route::getRoutes()->refreshNameLookups();
        $this->app['url']->setRoutes(Route::getRoutes());
    }

    public function test_dashboard_requires_the_admin_session_and_renders_all_tabs(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.dashboard.update'))->assertRedirect(route('admin.login'));

        $this->withSession(['admin_id' => 1, 'admin_name' => 'Dashboard Tester'])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Tester')
            ->assertSee('Log Out')
            ->assertSee('script/logout_animation.js')
            ->assertSee('Recent Requests')
            ->assertSee('Analytics Filter')
            ->assertSee('Requests Over Time')
            ->assertSee('Requests by Status')
            ->assertSee('Requests by Document Type');

        foreach ([
            'pending' => 'Pending Requests',
            'pickup' => 'For Pick Up',
            'completed' => 'Completed Requests',
        ] as $tab => $title) {
            $response = $this->withSession(['admin_id' => 1])
                ->get(route('admin.dashboard', ['view' => $tab]));

            $response
                ->assertOk()
                ->assertSee($title)
                ->assertSee('class="nav-link nav-sublink active"', false)
                ->assertDontSee('class="nav-link active"', false)
                ->assertSee('class="nav-submenu open"', false)
                ->assertSee('aria-expanded="true"', false);

            $this->assertMatchesRegularExpression(
                '/<a href="'.preg_quote(route('admin.dashboard', ['view' => $tab]), '/').'"[^>]*class="nav-link nav-sublink active">/',
                $response->getContent()
            );
        }
    }

    public function test_overview_filters_analytics_and_shows_recent_pending_requests(): void
    {
        $residentId = DB::table('residents')->insertGetId([
            'fullname' => 'Jordan Resident',
            'first_name' => 'Jordan',
            'last_name' => 'Resident',
            'mobile_number' => '09123456789',
            'email' => 'jordan@example.com',
        ]);
        $this->insertRequest($residentId, 'Pending', 'Certificate', '2026-03-09 10:00:00', 'BR-MAR-PENDING');
        $this->insertRequest($residentId, 'For Pick Up', 'Certificate', '2026-03-09 11:00:00', 'BR-MAR-PICKUP');
        $this->insertRequest($residentId, 'Released', 'Clearance', '2026-03-10 10:00:00', 'BR-MAR-RELEASED');
        $this->insertRequest($residentId, 'Pending', 'Indigency', '2026-04-09 10:00:00', 'BR-APR-PENDING');

        $this->withSession(['admin_id' => 1, 'admin_name' => 'Dashboard Tester'])
            ->get(route('admin.dashboard', [
                'view' => 'overview',
                'year' => '2026',
                'month' => '3',
                'day' => '9',
            ]))
            ->assertOk()
            ->assertSee('Showing <b>2</b> requests', false)
            ->assertSee('March 9, 2026')
            ->assertSee('BR-APR-PENDING')
            ->assertSee('Jordan Resident')
            ->assertSee('statusValues: [1,1,0]', false)
            ->assertSee('id="analytics-filter"', false)
            ->assertSee('action="'.route('admin.dashboard').'#analytics-filter"', false);
    }

    public function test_pickup_status_update_releases_request_and_writes_audit_entry(): void
    {
        $requestId = $this->insertRequest(null, 'For Pick Up', 'Residency Certificate', '2026-03-09 10:00:00', 'BR-UPDATE');

        $this->withSession(['admin_id' => 1])
            ->get(route('admin.dashboard', ['view' => 'pickup']))
            ->assertOk()
            ->assertSee('name="_token"', false)
            ->assertSee('name="new_status" value="Released"', false);

        $this->withSession(['admin_id' => 1, 'admin_name' => 'Dashboard Tester'])
            ->post(route('admin.dashboard.update'), [
                'action' => 'update_status',
                'request_id' => $requestId,
                'new_status' => 'Released',
                'current_view' => 'pickup',
            ])
            ->assertRedirect(route('admin.dashboard', ['view' => 'completed']))
            ->assertSessionHas('dashboard_flash', "Request #{$requestId} updated.")
            ->assertSessionHas('dashboard_flash_type', 'success');

        $this->assertSame('Released', DB::table('document_requests')->where('id', $requestId)->value('status'));
        $this->assertSame('Changed request status to Released', DB::table('audit_trail')->value('action'));
        $this->assertSame('REQ-'.str_pad((string) $requestId, 4, '0', STR_PAD_LEFT).' (Residency Certificate)', DB::table('audit_trail')->value('details'));
    }

    public function test_invalid_status_is_rejected_without_changing_request(): void
    {
        $requestId = $this->insertRequest(null, 'For Pick Up', 'Clearance', '2026-03-09 10:00:00', 'BR-INVALID');

        $this->withSession(['admin_id' => 1])
            ->post(route('admin.dashboard.update'), [
                'request_id' => $requestId,
                'new_status' => 'Deleted',
                'current_view' => 'pickup',
            ])
            ->assertRedirect(route('admin.dashboard', ['view' => 'pickup']))
            ->assertSessionHas('dashboard_flash', 'Invalid status.')
            ->assertSessionHas('dashboard_flash_type', 'error');

        $this->assertSame('For Pick Up', DB::table('document_requests')->where('id', $requestId)->value('status'));
        $this->assertSame(0, DB::table('audit_trail')->count());
    }

    public function test_admin_can_view_a_request_attachment_from_private_storage(): void
    {
        $filename = 'valid_id_1_example.png';
        $requestId = $this->insertRequest(null, 'For Pick Up', 'Clearance', '2026-03-09 10:00:00', 'BR-IMAGE');
        DB::table('document_requests')->where('id', $requestId)->update(['attachment_photo' => $filename]);
        Storage::fake('local');
        Storage::disk('local')->put('resident-uploads/'.$filename, 'fake-image-content');

        $url = route('admin.dashboard.attachment', ['filename' => $filename]);
        $this->get($url)->assertRedirect(route('admin.login'));

        $this->withSession(['admin_id' => 1])
            ->get($url)
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    private function insertRequest(?int $residentId, string $status, string $documentType, string $date, string $code): int
    {
        return DB::table('document_requests')->insertGetId([
            'resident_id' => $residentId,
            'document_type' => $documentType,
            'purpose' => 'Testing',
            'status' => $status,
            'tracking_code' => $code,
            'request_date' => $date,
        ]);
    }
}
