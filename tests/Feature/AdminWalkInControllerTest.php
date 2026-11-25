<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminWalkInController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminWalkInControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('document_requests');
        Schema::dropIfExists('audit_trail');
        Schema::dropIfExists('residents');

        Schema::create('residents', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('fullname', 100);
            $table->string('email', 100)->unique();
            $table->string('password');
            $table->string('first_name', 100)->nullable();
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('suffix', 20)->nullable();
            $table->string('address_purok', 100)->nullable();
            $table->string('civil_status', 20)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth', 255)->nullable();
            $table->string('mobile_number', 20)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->string('status', 20)->default('Active');
        });

        Schema::create('document_requests', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('resident_id');
            $table->string('document_type', 100);
            $table->text('purpose')->nullable();
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('suffix', 20)->nullable();
            $table->string('address_street', 255);
            $table->string('address_block', 100)->nullable();
            $table->string('address_purok', 100);
            $table->string('civil_status', 20);
            $table->string('nationality', 100)->default('Filipino');
            $table->date('date_of_birth');
            $table->string('place_of_birth', 255);
            $table->string('mobile_number', 20);
            $table->string('email_address', 150)->nullable();
            $table->string('relationship_to_resident', 50)->nullable();
            $table->string('requestor_first_name', 100)->nullable();
            $table->string('requestor_middle_name', 100)->nullable();
            $table->string('requestor_last_name', 100)->nullable();
            $table->string('requestor_suffix', 20)->nullable();
            $table->string('requestor_address', 255)->nullable();
            $table->string('requestor_mobile_number', 20)->nullable();
            $table->string('requestor_email_address', 150)->nullable();
            $table->string('status', 20)->default('Pending');
            $table->text('remarks')->nullable();
            $table->string('tracking_code', 50)->nullable();
            $table->timestamp('request_date')->useCurrent();
            $table->string('attachment_photo', 255)->nullable();
            $table->string('business_name', 255)->nullable();
            $table->string('business_nature', 255)->nullable();
            $table->string('business_address', 255)->nullable();
            $table->string('js_id_type', 100)->nullable();
            $table->string('js_identity', 255)->nullable();
            $table->string('js_residency', 255)->nullable();
            $table->string('requestor_valid_id', 255)->nullable();
            $table->string('relationship_proof', 255)->nullable();
            $table->boolean('data_privacy')->default(false);
            $table->foreign('resident_id')->references('id')->on('residents');
        });

        Schema::create('audit_trail', function (Blueprint $table): void {
            $table->increments('id');
            $table->dateTime('created_at')->useCurrent();
            $table->string('user_name', 150);
            $table->string('role', 30);
            $table->string('action', 100);
            $table->string('details', 255)->default('');
        });

        Route::middleware('web')->group(function (): void {
            $assumedRoutes = [
                'admin.dashboard' => '/admin/dashboard',
                'admin.accounts' => '/admin/accounts',
                'admin.residents' => '/admin/residents',
                'admin.summary' => '/admin/summary',
                'admin.audit' => '/admin/audit',
                'admin.logout' => '/admin/logout',
            ];

            foreach ($assumedRoutes as $name => $uri) {
                if (! Route::has($name)) {
                    Route::get($uri, static fn () => response(''))->name($name);
                }
            }

            if (! Route::has('admin.walkin')) {
                Route::get('/admin/walkin', [AdminWalkInController::class, 'index'])->name('admin.walkin');
            }
            if (! Route::has('admin.walkin.store')) {
                Route::post('/admin/walkin', [AdminWalkInController::class, 'store'])->name('admin.walkin.store');
            }
        });
        app('router')->getRoutes()->refreshNameLookups();
        app('url')->setRoutes(app('router')->getRoutes());
    }

    public function test_walk_in_page_requires_an_admin_and_renders_the_form_with_sidebar_counts(): void
    {
        $this->get('/admin/walkin')
            ->assertRedirect(route('admin.login'));

        $residentId = $this->createResident();
        $this->createRequest($residentId, 'Pending');
        $this->createRequest($residentId, 'For Pick Up');
        $this->createRequest($residentId, 'Released');

        $this->withSession(['admin_id' => 7, 'admin_name' => 'Office Tester'])
            ->get('/admin/walkin')
            ->assertOk()
            ->assertSee('Walk-in Request')
            ->assertSee('Type of Document')
            ->assertSee('Relationship to the Resident')
            ->assertSee('oninput="this.value=this.value.replace(/[0-9]/g, \'\')"', false)
            ->assertSee('stylesheet/admin_dashboard.css')
            ->assertSee('stylesheet/admin_create_account.css')
            ->assertSee('script/admin_sidebar.js')
            ->assertSee('name="_token"', false)
            ->assertViewHas('counts', [
                'Pending' => 1,
                'PickUp' => 1,
                'Released' => 1,
            ])
            ->assertViewHas('total_residents', 1)
            ->assertViewHas('admin_name', 'Office Tester');
    }

    public function test_walk_in_name_fields_reject_numeric_characters(): void
    {
        foreach (['first_name' => 'Walk2', 'requestor_first_name' => 'Requestor2'] as $field => $value) {
            $this->withSession(['admin_id' => 7, 'admin_name' => 'Office Tester'])
                ->from('/admin/walkin')
                ->post('/admin/walkin', $this->validSubmission([$field => $value]))
                ->assertRedirect('/admin/walkin')
                ->assertSessionHasErrors($field);
        }

        $this->assertSame(0, DB::table('document_requests')->count());
    }

    public function test_self_walk_in_submission_uses_a_disabled_placeholder_and_records_tracking_and_audit(): void
    {
        $residentId = $this->createResident();

        $this->withSession(['admin_id' => 7, 'admin_name' => 'Office Tester'])
            ->post('/admin/walkin', $this->validSubmission())
            ->assertRedirect(route('admin.walkin'));

        $request = DB::table('document_requests')->first();
        $placeholder = DB::table('residents')->where('id', $request->resident_id)->first();

        $this->assertNotNull($request);
        $this->assertNotSame($residentId, (int) $request->resident_id);
        $this->assertSame('Walk-in request placeholder', $placeholder->fullname);
        $this->assertSame('Disabled', $placeholder->status);
        $this->assertSame('Certificate of Residency', $request->document_type);
        $this->assertSame('Pending', $request->status);
        $this->assertSame('You', $request->relationship_to_resident);
        $this->assertStringStartsWith('WI-'.date('Y').'-', $request->tracking_code);
        $this->assertSame('', $request->requestor_first_name);

        $audit = DB::table('audit_trail')->first();
        $this->assertSame('Walk-in Tester', $audit->user_name);
        $this->assertSame('Walk-in', $audit->role);
        $this->assertSame('Walk-in request', $audit->action);
        $this->assertStringContainsString('REQ-0001 (Certificate of Residency)', $audit->details);
        $this->assertStringContainsString('encoded by Office Tester', $audit->details);

        $this->withSession(['admin_id' => 7, 'admin_name' => 'Office Tester'])
            ->get('/admin/walkin')
            ->assertOk()
            ->assertSee('Walk-in request saved')
            ->assertSee($request->tracking_code)
            ->assertSee('View / Print certificate')
            ->assertSee('id='.$request->id)
            ->assertViewHas('counts', [
                'Pending' => 1,
                'PickUp' => 0,
                'Released' => 0,
            ])
            ->assertViewHas('total_residents', 1);
    }

    public function test_conditional_fields_are_required_and_a_job_seeker_proxy_request_can_be_saved(): void
    {
        $this->withSession(['admin_id' => 7, 'admin_name' => 'Office Tester'])
            ->from('/admin/walkin')
            ->post('/admin/walkin', $this->validSubmission(['first_name' => 'Walk2']))
            ->assertRedirect('/admin/walkin')
            ->assertSessionHasErrors('first_name');

        $this->withSession(['admin_id' => 7, 'admin_name' => 'Office Tester'])
            ->from('/admin/walkin')
            ->post('/admin/walkin', $this->validSubmission([
                'request_type' => 'Barangay Business Clearance',
            ]))
            ->assertRedirect('/admin/walkin')
            ->assertSessionHasErrors(['business_name', 'business_nature', 'business_address']);

        $this->assertSame(0, DB::table('document_requests')->count());

        $this->withSession(['admin_id' => 7, 'admin_name' => 'Office Tester'])
            ->from('/admin/walkin')
            ->post('/admin/walkin', $this->validSubmission([
                'request_type' => 'First-Time Job Seeker',
                'relationship_to_resident' => 'Mother/Father',
            ]))
            ->assertRedirect('/admin/walkin')
            ->assertSessionHasErrors([
                'requestor_first_name',
                'requestor_last_name',
                'requestor_address',
                'requestor_mobile_number',
                'js_id_type',
                'data_privacy',
            ]);

        $this->assertSame(0, DB::table('document_requests')->count());

        $this->withSession(['admin_id' => 7, 'admin_name' => 'Office Tester'])
            ->post('/admin/walkin', $this->validSubmission([
                'request_type' => 'First-Time Job Seeker',
                'relationship_to_resident' => 'Mother/Father',
                'requestor_first_name' => 'Casey',
                'requestor_last_name' => 'Tester',
                'requestor_address' => 'Homestead',
                'requestor_mobile_number' => '09123456789',
                'js_id_type' => 'Student ID',
                'data_privacy' => '1',
            ]))
            ->assertRedirect(route('admin.walkin'));

        $request = DB::table('document_requests')->first();
        $this->assertSame('First-Time Job Seeker', $request->document_type);
        $this->assertSame('Mother/Father', $request->relationship_to_resident);
        $this->assertSame('Casey', $request->requestor_first_name);
        $this->assertSame('Student ID', $request->js_id_type);
        $this->assertSame(1, (int) $request->data_privacy);
    }

    private function validSubmission(array $overrides = []): array
    {
        return array_merge([
            'request_type' => 'Certificate of Residency',
            'purpose' => 'School enrollment',
            'relationship_to_resident' => 'You',
            'first_name' => 'Walk-in',
            'last_name' => 'Tester',
            'address_purok' => 'Purok 1',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'date_of_birth' => '2000-01-01',
            'place_of_birth' => 'Homestead',
            'mobile_number' => '09123456789',
        ], $overrides);
    }

    private function createResident(): int
    {
        return (int) DB::table('residents')->insertGetId([
            'fullname' => 'Registered Resident',
            'email' => 'resident@example.com',
            'password' => bcrypt('secret-password'),
            'status' => 'Active',
        ]);
    }

    private function createRequest(int $residentId, string $status): void
    {
        DB::table('document_requests')->insert([
            'resident_id' => $residentId,
            'document_type' => 'Certificate of Residency',
            'first_name' => 'Registered',
            'last_name' => 'Resident',
            'address_street' => '',
            'address_purok' => 'Purok 1',
            'civil_status' => 'Single',
            'date_of_birth' => '2000-01-01',
            'place_of_birth' => 'Homestead',
            'mobile_number' => '',
            'status' => $status,
        ]);
    }
}
