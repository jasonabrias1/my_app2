<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResidentLoginPageTest extends TestCase
{
    public function test_resident_can_cancel_only_their_own_pending_request(): void
    {
        $this->createResidentRequestTables();
        $this->insertResidentRequest(1, 'Pending', 'BR-PENDING-CANCEL');
        $this->insertResidentRequest(1, 'Released', 'BR-COMPLETE');
        $this->insertResidentRequest(2, 'Pending', 'BR-OTHER-RESIDENT');

        try {
            $this->withSession(['resident_id' => 1, 'resident_name' => 'Resident Tester'])
                ->get(route('resident.requests'))
                ->assertOk()
                ->assertSee('action="' . route('resident.requests.cancel', ['requestId' => 1]) . '"', false)
                ->assertSee(route('resident.requests.certificate', ['requestId' => 2]), false);

            $this->withSession(['resident_id' => 1, 'resident_name' => 'Resident Tester'])
                ->post(route('resident.requests.cancel', ['requestId' => 1]))
                ->assertRedirect(route('resident.requests'))
                ->assertSessionHas('request_notice', 'Your request has been cancelled.');

            $this->assertSame(
                'Cancelled',
                DB::table('document_requests')->where('tracking_code', 'BR-PENDING-CANCEL')->value('status'),
            );
            $this->assertSame('Cancelled request', DB::table('audit_trail')->value('action'));

            $this->withSession(['resident_id' => 1])
                ->post(route('resident.requests.cancel', ['requestId' => 2]))
                ->assertRedirect(route('resident.requests'))
                ->assertSessionHas('request_error', 'Only pending requests can be cancelled.');

            $this->withSession(['resident_id' => 1])
                ->post(route('resident.requests.cancel', ['requestId' => 3]))
                ->assertNotFound();

            $this->withSession(['resident_id' => 1])
                ->get(route('resident.requests', ['status' => 'Cancelled']))
                ->assertOk()
                ->assertSee('This request was cancelled.');
        } finally {
            Schema::dropIfExists('audit_trail');
            Schema::dropIfExists('document_requests');
            Schema::dropIfExists('residents');
        }
    }

    public function test_resident_can_view_only_their_own_released_certificate(): void
    {
        $this->createResidentRequestTables();
        $this->insertResidentRequest(1, 'Released', 'BR-RESIDENT-CERTIFICATE');
        $this->insertResidentRequest(1, 'Pending', 'BR-PENDING-CERTIFICATE');
        $this->insertResidentRequest(2, 'Released', 'BR-OTHER-CERTIFICATE');

        try {
            $this->get(route('resident.requests.certificate', ['requestId' => 1]))
                ->assertRedirect(route('resident.login'));

            $this->withSession(['resident_id' => 1])
                ->get(route('resident.requests.certificate', ['requestId' => 1]))
                ->assertOk()
                ->assertSee('View Completed Request')
                ->assertSee('CERTIFICATE OF RESIDENCY')
                ->assertSee('BR-RESIDENT-CERTIFICATE')
                ->assertSee('Print')
                ->assertDontSee('Did the certificate print successfully?');

            $this->withSession(['resident_id' => 1])
                ->get(route('resident.requests.certificate', ['requestId' => 2]))
                ->assertNotFound();

            $this->withSession(['resident_id' => 1])
                ->get(route('resident.requests.certificate', ['requestId' => 3]))
                ->assertNotFound();
        } finally {
            Schema::dropIfExists('document_requests');
            Schema::dropIfExists('residents');
        }
    }

    public function test_copies_migration_defaults_existing_requests_to_one_copy(): void
    {
        Schema::dropIfExists('document_requests');
        Schema::create('document_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('document_type');
        });
        DB::table('document_requests')->insert(['document_type' => 'Certificate of Residency']);

        try {
            $migration = require database_path('migrations/2026_11_25_000001_add_copies_to_document_requests_table.php');
            $migration->up();

            $this->assertSame(1, DB::table('document_requests')->value('copies'));
        } finally {
            Schema::dropIfExists('document_requests');
        }
    }

    public function test_resident_portal_link_opens_the_laravel_login_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('/resident/login');

        $this->get('/resident/login')
            ->assertOk()
            ->assertSee('Resident Login')
            ->assertSee('stylesheet/resident_login.css');
    }

    public function test_resident_dashboard_requires_a_resident_session(): void
    {
        $this->get('/resident/dashboard')
            ->assertRedirect('/resident/login');
    }

    public function test_tracking_page_shows_help_when_no_request_is_searched(): void
    {
        $this->withSession([
            'resident_id' => 1,
            'resident_name' => 'Resident Tester',
        ])->get('/resident/tracking')
            ->assertOk()
            ->assertSee('Looking for a request? Type a code into the search bar to get started');
    }

    public function test_resident_request_form_is_connected_to_the_resident_session(): void
    {
        $this->get('/resident/submit')
            ->assertRedirect('/resident/login');

        Schema::create('residents', function (Blueprint $table): void {
            $table->id();
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

        DB::table('residents')->insert([
            'id' => 1,
            'first_name' => 'Resident',
            'last_name' => 'Tester',
            'address_purok' => 'Purok 1',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'date_of_birth' => '2000-01-01',
            'place_of_birth' => 'Homestead',
            'mobile_number' => '09123456789',
            'email' => 'resident@example.com',
        ]);

        try {
            $this->withSession([
                'resident_id' => 1,
                'resident_name' => 'Resident Tester',
            ])->get('/resident/submit')
                ->assertOk()
                ->assertSee('Submit a Request')
                ->assertSee('stylesheet/resident_submit.css')
                ->assertSee('stylesheet/logout_animation.css')
                ->assertSee('script/logout_animation.js')
                ->assertSee(route('resident.logout'), false)
                ->assertSee('script/resident_submit.js')
                ->assertSee('maxlength="11"', false)
                ->assertSee('name="copies"', false)
                ->assertSee('value="1"', false)
                ->assertSee('aria-label="Add one copy"', false)
                ->assertSee('"first_name":"Resident"', false)
                ->assertSee('"email_address":"resident@example.com"', false)
                ->assertSee('resident/submit');

            $this->withSession([
                'resident_id' => 1,
                'resident_name' => 'Resident Tester',
                'resident_submit_success' => 'BR-2026-ABC123',
            ])->get('/resident/submit')
                ->assertOk()
                ->assertSee('window.location.href = ' . json_encode(route('resident.requests')) . ';', false);
        } finally {
            Schema::dropIfExists('residents');
        }
    }

    public function test_resident_request_submission_saves_the_request_and_attachment(): void
    {
        Schema::create('document_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('resident_id');
            $table->string('document_type');
            $table->unsignedTinyInteger('copies')->default(1);
            $table->text('purpose')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->string('address_street')->nullable();
            $table->string('address_block')->nullable();
            $table->string('address_purok');
            $table->string('civil_status');
            $table->string('nationality');
            $table->date('date_of_birth');
            $table->string('place_of_birth');
            $table->string('mobile_number');
            $table->string('email_address')->nullable();
            $table->string('relationship_to_resident')->nullable();
            $table->string('requestor_first_name')->nullable();
            $table->string('requestor_middle_name')->nullable();
            $table->string('requestor_last_name')->nullable();
            $table->string('requestor_suffix')->nullable();
            $table->string('requestor_address')->nullable();
            $table->string('requestor_mobile_number')->nullable();
            $table->string('requestor_email_address')->nullable();
            $table->string('status');
            $table->string('tracking_code');
            $table->string('attachment_photo')->nullable();
            $table->string('business_name')->nullable();
            $table->string('business_address')->nullable();
            $table->string('business_nature')->nullable();
            $table->string('js_id_type')->nullable();
            $table->string('js_identity')->nullable();
            $table->string('js_residency')->nullable();
            $table->string('requestor_valid_id')->nullable();
            $table->string('relationship_proof')->nullable();
            $table->boolean('data_privacy')->default(false);
            $table->timestamp('request_date')->nullable();
        });

        Storage::fake('local');

        try {
            $this->from('/resident/submit')
                ->withSession([
                    'resident_id' => 1,
                    'resident_name' => 'Resident Tester',
                ])
                ->post('/resident/submit', [
                    'request_type' => 'Certificate of Residency',
                    'copies' => 4,
                    'purpose' => 'School enrollment',
                    'relationship_to_resident' => 'You',
                    'first_name' => 'Resident',
                    'last_name' => 'Tester',
                    'address_purok' => 'Purok 1',
                    'civil_status' => 'Single',
                    'nationality' => 'Filipino',
                    'date_of_birth' => '2000-01-01',
                    'place_of_birth' => 'Homestead',
                    'mobile_number' => '09123456789',
                    'valid_id' => UploadedFile::fake()->create('resident-id.pdf', 100, 'application/pdf'),
                ])
                ->assertRedirect('/resident/submit')
                ->assertSessionHas('resident_submit_success');

            $request = DB::table('document_requests')->first();

            $this->assertNotNull($request);
            $this->assertSame('Certificate of Residency', $request->document_type);
            $this->assertSame(4, $request->copies);
            $this->assertSame('Pending', $request->status);
            $this->assertStringStartsWith('BR-', $request->tracking_code);
            $this->assertNotEmpty($request->attachment_photo);
            Storage::disk('local')->assertExists('resident-uploads/' . $request->attachment_photo);

            $this->withSession([
                'resident_id' => 1,
                'resident_name' => 'Resident Tester',
            ])->get('/resident/dashboard/attachments/' . $request->attachment_photo)
                ->assertOk();

            $this->from('/resident/submit')
                ->withSession([
                    'resident_id' => 1,
                    'resident_name' => 'Resident Tester',
                ])
                ->post('/resident/submit', [
                    'request_type' => 'Certificate of Residency',
                    'copies' => 1,
                    'purpose' => 'School enrollment',
                    'relationship_to_resident' => 'You',
                    'first_name' => 'Resident',
                    'last_name' => 'Tester',
                    'address_purok' => 'Purok 1',
                    'civil_status' => 'Single',
                    'nationality' => 'Filipino',
                    'date_of_birth' => '2000-01-01',
                    'place_of_birth' => 'Homestead',
                    'mobile_number' => '091234567890',
                ])
                ->assertRedirect('/resident/submit')
                ->assertSessionHasErrors('mobile_number');

            $this->from('/resident/submit')
                ->withSession([
                    'resident_id' => 1,
                    'resident_name' => 'Resident Tester',
                ])
                ->post('/resident/submit', [
                    'request_type' => 'Certificate of Residency',
                    'copies' => 0,
                    'purpose' => 'School enrollment',
                    'relationship_to_resident' => 'You',
                    'first_name' => 'Resident',
                    'last_name' => 'Tester',
                    'address_purok' => 'Purok 1',
                    'civil_status' => 'Single',
                    'nationality' => 'Filipino',
                    'date_of_birth' => '2000-01-01',
                    'place_of_birth' => 'Homestead',
                    'mobile_number' => '09123456789',
                    'valid_id' => UploadedFile::fake()->create('resident-id.pdf', 100, 'application/pdf'),
                ])
                ->assertRedirect('/resident/submit')
                ->assertSessionHasErrors('copies');
        } finally {
            Schema::dropIfExists('document_requests');
        }
    }

    public function test_resident_account_requests_and_tracking_pages_render(): void
    {
        Schema::create('residents', function (Blueprint $table): void {
            $table->id();
            $table->string('fullname');
            $table->string('email')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('password');
        });
        DB::table('residents')->insert([
            'id' => 1,
            'fullname' => 'Resident Tester',
            'email' => 'resident@example.com',
            'mobile_number' => '09123456789',
            'password' => bcrypt('current-password'),
        ]);
        Schema::create('document_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('resident_id');
            $table->timestamp('request_date')->nullable();
            $table->string('status')->nullable();
            $table->string('document_type')->nullable();
            $table->string('tracking_code')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('suffix')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('nationality')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('address_purok')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('email_address')->nullable();
            $table->string('relationship_to_resident')->nullable();
            $table->string('requestor_first_name')->nullable();
            $table->string('requestor_middle_name')->nullable();
            $table->string('requestor_last_name')->nullable();
            $table->string('requestor_suffix')->nullable();
            $table->string('requestor_address')->nullable();
            $table->string('requestor_mobile_number')->nullable();
            $table->string('requestor_email_address')->nullable();
            $table->string('business_name')->nullable();
            $table->string('business_nature')->nullable();
            $table->string('business_address')->nullable();
            $table->text('purpose')->nullable();
            $table->text('remarks')->nullable();
            $table->string('attachment_photo')->nullable();
            $table->string('js_id_type')->nullable();
            $table->string('js_identity')->nullable();
            $table->string('js_residency')->nullable();
            $table->string('requestor_valid_id')->nullable();
            $table->string('relationship_proof')->nullable();
        });
        DB::table('document_requests')->insert([
            'id' => 1,
            'resident_id' => 1,
            'request_date' => '2026-11-13 12:00:00',
            'status' => 'Pending',
            'document_type' => 'Certificate of Residency',
            'tracking_code' => 'BR-2026-ABC123',
            'first_name' => 'Resident',
            'last_name' => 'Tester',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'date_of_birth' => '2000-01-01',
            'place_of_birth' => 'Homestead',
            'address_purok' => 'Purok 1',
            'mobile_number' => '09123456789',
            'relationship_to_resident' => 'You',
            'purpose' => 'School enrollment',
        ]);

        try {
            $this->withSession([
                'resident_id' => 1,
                'resident_name' => 'Resident Tester',
            ])->get('/resident/account')
                ->assertOk()
                ->assertSee('Account Settings')
                ->assertSee('/resident/account');

            $this->withSession([
                'resident_id' => 1,
                'resident_name' => 'Resident Tester',
            ])->post('/resident/account', [
                'action' => 'change_email',
                'new_email' => 'new@example.com',
                'current_password_email' => 'current-password',
            ])
                ->assertRedirect('/resident/account')
                ->assertSessionHas('email_success', 'Your email address has been updated.');

            $this->assertSame('new@example.com', DB::table('residents')->where('id', 1)->value('email'));

            $this->withSession([
                'resident_id' => 1,
                'resident_name' => 'Resident Tester',
            ])->post('/resident/account', [
                'action' => 'change_password',
                'current_password' => 'current-password',
                'new_password' => 'new-current-password',
                'confirm_password' => 'new-current-password',
            ])
                ->assertRedirect('/resident/account')
                ->assertSessionHas('password_success', 'Your password has been updated.');

            $this->assertTrue(Hash::check(
                'new-current-password',
                DB::table('residents')->where('id', 1)->value('password'),
            ));

            config(['services.iprog_sms.token' => 'test-token']);
            Http::fake([
                'www.iprogsms.com/*' => Http::response(['success' => true], 200),
            ]);

            $this->withSession([
                'resident_id' => 1,
                'resident_name' => 'Resident Tester',
            ])->post('/resident/account', [
                'action' => 'send_mobile_otp',
                'new_mobile' => '09987654321',
                'current_password_mobile' => 'new-current-password',
            ])
                ->assertRedirect('/resident/account')
                ->assertSessionHas('mobile_info');

            $otp = session('mobile_otp')['code'];
            $this->post('/resident/account', [
                'action' => 'verify_mobile_otp',
                'otp_code' => $otp,
            ])
                ->assertRedirect('/resident/account')
                ->assertSessionHas('mobile_success', 'Your mobile number has been updated.');

            $this->assertSame('09987654321', DB::table('residents')->where('id', 1)->value('mobile_number'));
            Http::assertSent(fn ($request) => $request->url() === 'https://www.iprogsms.com/api/v1/sms_messages');

            $this->withSession([
                'resident_id' => 1,
                'resident_name' => 'Resident Tester',
            ])->get('/resident/tracking')
                ->assertOk()
                ->assertSee('Looking for a request? Type a code into the search bar to get started')
                ->assertDontSee('Your Requests')
                ->assertDontSee('BR-2026-ABC123')
                ->assertDontSee('School enrollment');

            $this->withSession([
                'resident_id' => 1,
                'resident_name' => 'Resident Tester',
            ])->get('/resident/requests')
                ->assertOk()
                ->assertSee('My Requests')
                ->assertSee('Certificate of Residency')
                ->assertDontSee('badge badge-pending', false)
                ->assertSee('class="request-action-btn cancel-request-btn"', false)
                ->assertSee('Track this request')
                ->assertSee('copy-btn', false)
                ->assertSee('resident_request.js');

            $this->withSession([
                'resident_id' => 1,
                'resident_name' => 'Resident Tester',
            ])->get('/resident/tracking?auto_track=1&tracking_no=BR-2026-ABC123')
                ->assertOk()
                ->assertSee('Track a Request')
                ->assertSee('School enrollment')
                ->assertDontSee('Looking for a request? Type a code into the search bar to get started')
                ->assertSee('resident_tracking.js');

            $this->withSession([
                'resident_id' => 1,
                'resident_name' => 'Resident Tester',
            ])->get('/resident/tracking?auto_track=1&tracking_no=BR-2026-ABC123')
                ->assertOk()
                ->assertSee('Certificate of Residency');

            $this->withSession([
                'resident_id' => 1,
                'resident_name' => 'Resident Tester',
            ])->get('/resident/dashboard')
                ->assertOk()
                ->assertDontSee('Ready for pickup')
                ->assertHeader('Cache-Control', 'no-store, private, max-age=0');

            DB::table('document_requests')
                ->where('tracking_code', 'BR-2026-ABC123')
                ->update(['status' => 'For Pick Up']);

            $this->withSession([
                'resident_id' => 1,
                'resident_name' => 'Resident Tester',
            ])->get('/resident/dashboard')
                ->assertOk()
                ->assertSee('Ready for pickup')
                ->assertSee('BR-2026-ABC123')
                ->assertSee('data-request-id="1"', false)
                ->assertSee('data-dismissal-scope=', false)
                ->assertHeader('Cache-Control', 'no-store, private, max-age=0');

            DB::table('document_requests')
                ->where('tracking_code', 'BR-2026-ABC123')
                ->update(['status' => 'Released']);

            $this->withSession([
                'resident_id' => 1,
                'resident_name' => 'Resident Tester',
            ])->get('/resident/requests?status=Completed')
                ->assertOk()
                ->assertSee('Certificate of Residency')
                ->assertSee('Completed')
                ->assertSee(route('resident.requests.certificate', ['requestId' => 1]), false)
                ->assertSee('class="request-action-btn view-request-btn"', false)
                ->assertDontSee('Track this request')
                ->assertDontSee('copy-btn', false);

            $this->withSession([
                'resident_id' => 1,
                'resident_name' => 'Resident Tester',
            ])->get('/resident/account')
                ->assertOk()
                ->assertSee('Account Settings');
        } finally {
            Schema::dropIfExists('document_requests');
            Schema::dropIfExists('residents');
        }
    }

    private function createResidentRequestTables(): void
    {
        Schema::dropIfExists('audit_trail');
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
        DB::table('residents')->insert([
            ['id' => 1, 'first_name' => 'Resident', 'last_name' => 'Tester', 'address_purok' => 'Purok 1'],
            ['id' => 2, 'first_name' => 'Another', 'last_name' => 'Resident', 'address_purok' => 'Purok 2'],
        ]);

        Schema::create('document_requests', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('resident_id');
            $table->string('document_type');
            $table->string('status');
            $table->string('tracking_code');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->string('address_purok')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('nationality')->nullable();
            $table->text('purpose')->nullable();
            $table->dateTime('request_date')->nullable();
            $table->string('business_name')->nullable();
            $table->string('business_address')->nullable();
            $table->unsignedTinyInteger('copies')->default(1);
        });

        Schema::create('audit_trail', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('user_name');
            $table->string('role');
            $table->string('action');
            $table->string('details');
            $table->dateTime('created_at')->nullable();
        });
    }

    private function insertResidentRequest(int $residentId, string $status, string $trackingCode): void
    {
        DB::table('document_requests')->insert([
            'resident_id' => $residentId,
            'document_type' => 'Certificate of Residency',
            'status' => $status,
            'tracking_code' => $trackingCode,
            'first_name' => 'Resident',
            'last_name' => 'Tester',
            'address_purok' => 'Purok 1',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'purpose' => 'School enrollment',
            'request_date' => now(),
        ]);
    }
}
