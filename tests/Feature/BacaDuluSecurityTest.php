<?php

namespace Tests\Feature;

use App\Models\DataAccessRequest;
use App\Models\AiExtractionJob;
use App\Models\AiExtractionRow;
use App\Models\DataProvider;
use App\Models\Dataset;
use App\Models\DatasetObservation;
use App\Models\DatasetVariable;
use App\Models\SourceDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BacaDuluSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sync_command_can_explicitly_reset_existing_admin_password(): void
    {
        config([
            'bacadulu.admin.name' => 'Administrator BacaDulu',
            'bacadulu.admin.email' => 'admin-sync@bacadulu.test',
            'bacadulu.admin.password' => 'PasswordBaru#2026',
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin Lama',
            'email' => 'admin-sync@bacadulu.test',
            'password' => Hash::make('PasswordLama#2025'),
            'role' => 'researcher',
            'status' => 'inactive',
        ]);

        $exitCode = Artisan::call('bacadulu:admin-sync', ['--reset-password' => true]);

        $this->assertSame(0, $exitCode);
        $admin->refresh();
        $this->assertSame('Administrator BacaDulu', $admin->name);
        $this->assertSame('admin', $admin->role);
        $this->assertSame('active', $admin->status);
        $this->assertTrue(Hash::check('PasswordBaru#2026', $admin->password));

        $this->post(route('admin.login.store'), [
            'email' => 'admin-sync@bacadulu.test',
            'password' => 'PasswordBaru#2026',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertGuest('web');
    }

    public function test_public_responses_include_browser_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $policy = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        $this->assertStringContainsString('frame-src https://maps.google.com https://www.google.com', $policy);

        $response->assertSee('Baca Dulu,');
        $response->assertSee('PT Bina Cendikia Academy');
        $response->assertSee('maps.google.com/maps', false);
    }

    public function test_public_footer_only_renders_whatsapp_link_when_a_number_is_configured(): void
    {
        config([
            'bacadulu.contact.whatsapp_number' => '',
            'bacadulu.contact.whatsapp_label' => 'Call Center BacaDulu',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('https://wa.me/', false);

        config(['bacadulu.contact.whatsapp_number' => '628123456789']);

        $this->get('/')
            ->assertOk()
            ->assertSee('https://wa.me/628123456789', false)
            ->assertSee('Call Center BacaDulu');
    }

    public function test_researcher_cannot_access_administrator_routes(): void
    {
        $researcher = User::factory()->create([
            'role' => 'researcher',
            'status' => 'active',
        ]);

        $this->actingAs($researcher, 'admin')
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_login_uses_a_private_path_and_is_not_linked_from_researcher_login(): void
    {
        $adminLoginPath = '/'.trim((string) config('bacadulu.admin.path'), '/').'/login';

        $this->assertSame($adminLoginPath, route('admin.login', absolute: false));

        $this->get('/login')
            ->assertOk()
            ->assertDontSee($adminLoginPath, false)
            ->assertDontSee('Gunakan login admin');

        $this->get('/admin/login')->assertNotFound();

        $this->get(route('admin.login'))
    ->assertOk()
    ->assertSee('Akses khusus pengelola')
    ->assertSee('action="'.route('admin.login.store').'"', false)
    ->assertDontSee('href="'.route('login').'"', false);
    }

    public function test_admin_workspaces_are_accessible_to_admin_and_hidden_from_guests(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.catalog.variables.index'))
            ->assertOk()
            ->assertSee('Semua variabel dalam satu meja.');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.quality.index'))
            ->assertOk()
            ->assertSee('Satu antrean untuk semua pengecualian.');
    }

    public function test_admin_session_never_hijacks_public_researcher_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $researcher = User::factory()->create([
            'role' => 'researcher',
            'status' => 'active',
            'password' => Hash::make('PenelitiAman#2026'),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('login').'"', false)
            ->assertDontSee('Panel admin');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Akun peneliti')
            ->assertDontSee('Panel administrator');

        $this->post(route('login.store'), [
            'email' => $researcher->email,
            'password' => 'PenelitiAman#2026',
        ])->assertRedirect(route('user.profile'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertAuthenticatedAs($researcher, 'web');
    }

    public function test_researcher_profile_is_the_primary_account_page_and_dashboard_is_legacy_redirect(): void
    {
        $researcher = User::factory()->create([
            'role' => 'researcher',
            'status' => 'active',
        ]);

        $this->actingAs($researcher, 'web')
            ->get(route('user.profile'))
            ->assertOk()
            ->assertSee('Profil saya')
            ->assertSee('Edit profil')
            ->assertSee('researcher-profile.css')
            ->assertSee('researcher-profile.js')
            ->assertDontSee('<style', false)
            ->assertDontSee('Dashboard');

        $this->actingAs($researcher, 'web')
            ->get(route('user.profile.edit'))
            ->assertOk()
            ->assertSee('Ganti foto profil')
            ->assertSee('researcher-profile.css')
            ->assertSee('researcher-profile.js');

        $this->actingAs($researcher, 'web')
            ->get('/dashboard')
            ->assertRedirect(route('user.profile'));
    }

    public function test_researcher_can_update_profile_without_changing_login_email(): void
    {
        $researcher = User::factory()->create([
            'role' => 'researcher',
            'status' => 'active',
            'name' => 'Peneliti Lama',
            'institution' => null,
        ]);

        $this->actingAs($researcher, 'web')
            ->patch(route('user.profile.update'), [
                'name' => 'Peneliti Baru',
                'institution' => 'Universitas BacaDulu',
            ])
            ->assertRedirect(route('user.profile'))
            ->assertSessionHas('success', 'Profil berhasil diperbarui.');

        $this->assertDatabaseHas('users', [
            'id' => $researcher->id,
            'name' => 'Peneliti Baru',
            'email' => $researcher->email,
            'institution' => 'Universitas BacaDulu',
        ]);
    }

    public function test_researcher_can_upload_a_profile_photo(): void
    {
        Storage::fake('public');
        $researcher = User::factory()->create([
            'role' => 'researcher',
            'status' => 'active',
            'name' => 'Peneliti Foto',
        ]);

        $this->actingAs($researcher, 'web')
            ->patch(route('user.profile.update'), [
                'name' => $researcher->name,
                'institution' => 'Universitas BacaDulu',
                'avatar' => UploadedFile::fake()->image('profile.jpg', 320, 320),
            ])
            ->assertRedirect(route('user.profile'))
            ->assertSessionHas('success', 'Profil berhasil diperbarui.');

        $researcher->refresh();

        $this->assertNotNull($researcher->avatar);
        Storage::disk('public')->assertExists($researcher->avatar);

        $photoUrl = route('media.avatar', ['filename' => basename($researcher->avatar)]);

        $this->actingAs($researcher, 'web')
            ->get(route('user.profile'))
            ->assertOk()
            ->assertSee($photoUrl, false);

        $this->actingAs($researcher, 'web')
            ->get(route('user.profile.edit'))
            ->assertOk()
            ->assertSee($photoUrl, false);

        $this->get($photoUrl)->assertOk();
    }

    public function test_login_rate_limit_cannot_be_bypassed_with_email_case_or_spaces(): void
{
    $attempts = [
        'Target@Example.com',
        ' target@example.com',
        'TARGET@EXAMPLE.COM ',
        'TaRgEt@example.com',
        'target@example.com',
    ];

    foreach ($attempts as $email) {
        $this->post(route('login.store'), [
            'email' => $email,
            'password' => 'Password-Salah#2026',
        ])->assertSessionHasErrors('email');
    }

    $this->post(route('login.store'), [
        'email' => 'target@example.com',
        'password' => 'Password-Salah#2026',
    ])->assertStatus(429);
}

public function test_researcher_login_only_accepts_safe_internal_redirect_paths(): void
{
    $this->get(route('login', ['redirect' => '/datasets']))
        ->assertOk()
        ->assertSessionHas('url.intended', '/datasets');

    session()->forget('url.intended');

    $this->get(route('login', ['redirect' => '/\\evil.example']))
        ->assertOk()
        ->assertSessionMissing('url.intended');

    $this->get(route('login', ['redirect' => '//evil.example']))
        ->assertOk()
        ->assertSessionMissing('url.intended');
}

public function test_registration_normalizes_email_and_cannot_mass_assign_account_privileges(): void
{
    $this->post(route('register.store'), [
        'name' => 'Peneliti Aman',
        'email' => '  Peneliti.Aman@Example.COM ',
        'institution' => 'Universitas BacaDulu',
        'password' => 'Password-Aman#2026',
        'password_confirmation' => 'Password-Aman#2026',
        'terms' => '1',
        'role' => 'admin',
        'status' => 'inactive',
    ])->assertRedirect(route('user.profile'));

    $user = User::where('email', 'peneliti.aman@example.com')->firstOrFail();

    $this->assertSame('researcher', $user->role);
    $this->assertSame('active', $user->status);

    $user->fill([
        'role' => 'admin',
        'status' => 'inactive',
        'last_login_at' => now(),
    ]);

    $this->assertSame('researcher', $user->role);
    $this->assertSame('active', $user->status);
    $this->assertNull($user->last_login_at);
}

public function test_csv_export_neutralizes_formula_after_leading_whitespace(): void
{
    [$dataset, $variable] = $this->createPublishedDataset('open');

    $researcher = User::factory()->create([
        'role' => 'researcher',
        'status' => 'active',
    ]);

    $this->createReleasedObservation(
        $dataset,
        $variable,
        'FORMULA',
        '  =2+2'
    );

    $response = $this->actingAs($researcher, 'web')
        ->post(route('datasets.download.open', $dataset->slug));

    $response->assertOk();

    $this->assertStringContainsString(
        "'  =2+2",
        $response->streamedContent()
    );
}


    public function test_inactive_account_is_logged_out_before_entering_protected_pages(): void
    {
        $researcher = User::factory()->create([
            'role' => 'researcher',
            'status' => 'inactive',
        ]);

        $this->actingAs($researcher)
            ->get('/dashboard')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_restricted_dataset_never_exposes_observation_values_in_public_preview(): void
    {
        [$dataset, $variable] = $this->createPublishedDataset('restricted');

        DatasetObservation::create([
            'dataset_id' => $dataset->id,
            'dataset_variable_id' => $variable->id,
            'geography_code' => 'ID-JK',
            'geography_name' => 'DKI Jakarta',
            'period' => '2026',
            'value_text' => 'RAHASIA-SENSITIF-XYZ',
            'quality_status' => 'verified',
        ]);

        $this->get(route('datasets.show', $dataset->slug))
            ->assertOk()
            ->assertSee('Dilindungi')
            ->assertDontSee('RAHASIA-SENSITIF-XYZ');

        $this->get(route('datasets.variables.show', [$dataset->slug, $variable]))
            ->assertOk()
            ->assertSee('Dilindungi')
            ->assertDontSee('RAHASIA-SENSITIF-XYZ');
    }

    public function test_inactive_provider_removes_its_datasets_from_public_access(): void
    {
        [$dataset] = $this->createPublishedDataset('open');
        $dataset->provider->update(['status' => 'inactive']);

        $this->get(route('datasets.show', $dataset->slug))->assertNotFound();
    }

    public function test_researcher_cannot_download_another_users_approved_request(): void
    {
        [$dataset, $variable] = $this->createPublishedDataset('restricted');
        $owner = User::factory()->create(['role' => 'researcher', 'status' => 'active']);
        $intruder = User::factory()->create(['role' => 'researcher', 'status' => 'active']);

        $accessRequest = DataAccessRequest::create([
            'request_number' => 'BD-SECURITY-001',
            'user_id' => $owner->id,
            'dataset_id' => $dataset->id,
            'variable_ids' => [$variable->id],
            'geographies' => [],
            'periods' => [],
            'research_purpose' => 'Pengujian isolasi kepemilikan permintaan akses data.',
            'status' => 'approved',
            'estimated_cells' => 1,
            'estimated_price' => 0,
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($intruder)
            ->post(route('user.requests.download', $accessRequest))
            ->assertNotFound();
    }

    public function test_nested_observation_cannot_be_changed_through_a_different_dataset(): void
    {
        [$restrictedDataset] = $this->createPublishedDataset('restricted');
        [$openDataset, $openVariable] = $this->createPublishedDataset('open');
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $observation = DatasetObservation::create([
            'dataset_id' => $openDataset->id,
            'dataset_variable_id' => $openVariable->id,
            'geography_code' => 'ID',
            'geography_name' => 'Indonesia',
            'period' => '2026',
            'value_numeric' => 1,
            'quality_status' => 'reviewed',
        ]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.datasets.observations.update', [$restrictedDataset, $observation]), [
                'quality_status' => 'verified',
            ])
            ->assertNotFound();
    }

    public function test_non_admin_cannot_open_ai_ingestion_registry(): void
    {
        $researcher = User::factory()->create(['role' => 'researcher', 'status' => 'active']);

        $this->actingAs($researcher, 'admin')
            ->get(route('admin.ai.index'))
            ->assertForbidden();
    }

    public function test_public_pages_do_not_render_dataset_management_actions_for_admins(): void
    {
        [$dataset] = $this->createPublishedDataset('open');
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin, 'admin')
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee(route('admin.datasets.create'))
            ->assertDontSee('Tambahkan dataset pertama');

        $this->actingAs($admin, 'admin')
            ->get(route('datasets.show', $dataset->slug))
            ->assertOk()
            ->assertDontSee(route('admin.datasets.edit', $dataset))
            ->assertDontSee('Kelola dataset');
    }

    public function test_catalog_search_includes_variable_metadata(): void
    {
        [$dataset, $variable] = $this->createPublishedDataset('open');
        $variable->update(['code' => 'UNIKVARIABEL']);
        $this->createReleasedObservation($dataset, $variable);

        $this->get(route('datasets.index', ['q' => 'UNIKVARIABEL']))
            ->assertOk()
            ->assertSee($dataset->title)
            ->assertSee('UNIKVARIABEL');
    }

    public function test_variable_catalog_separates_corporate_and_regional_scopes(): void
    {
        [$corporateDataset, $corporateVariable] = $this->createPublishedDataset('open', 'corporate');
        [$regionalDataset, $regionalVariable] = $this->createPublishedDataset('restricted', 'regional');
        $corporateVariable->update(['name' => 'Emisi perusahaan unik']);
        $regionalVariable->update(['name' => 'Penduduk wilayah unik']);
        $this->createReleasedObservation($corporateDataset, $corporateVariable, 'BBCA', 'Bank Contoh');
        $this->createReleasedObservation($regionalDataset, $regionalVariable, 'ID-JB', 'Jawa Barat');

        $this->get(route('datasets.index', ['scope' => 'corporate']))
            ->assertOk()
            ->assertSee('Emisi perusahaan unik')
            ->assertDontSee('Penduduk wilayah unik');

        $this->get(route('datasets.index', ['scope' => 'regional']))
            ->assertOk()
            ->assertSee('Penduduk wilayah unik')
            ->assertDontSee('Emisi perusahaan unik');
    }

    public function test_variable_detail_cannot_be_opened_through_a_different_dataset(): void
    {
        [$corporateDataset, $corporateVariable] = $this->createPublishedDataset('open', 'corporate');
        [$regionalDataset, $regionalVariable] = $this->createPublishedDataset('restricted', 'regional');
        $this->createReleasedObservation($corporateDataset, $corporateVariable);
        $this->createReleasedObservation($regionalDataset, $regionalVariable);

        $this->get(route('datasets.variables.show', [$corporateDataset->slug, $regionalVariable]))
            ->assertNotFound();
    }

    public function test_pdf_extraction_requires_explicit_external_processing_consent(): void
    {
        [$dataset] = $this->createPublishedDataset('open');
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $document = $this->createSourceDocument($dataset, $admin, 'pdf');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.source-documents.extract', $document))
            ->assertSessionHasErrors('confirm_external_processing');

        $this->assertDatabaseCount('ai_extraction_jobs', 0);
    }

    public function test_csv_can_be_staged_locally_without_an_openai_key(): void
    {
        Storage::fake('local');
        config(['bacadulu.ai.enabled' => false, 'bacadulu.ai.api_key' => null, 'bacadulu.ai.disk' => 'local']);
        [$dataset] = $this->createPublishedDataset('open');
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $document = $this->createSourceDocument($dataset, $admin, 'csv');
        $csv = "variable_code,geography_code,geography_name,period,value,source_reference\nVALUE,ID,Indonesia,2026,42,Table 1\n";
        Storage::disk('local')->put($document->storage_path, $csv);
        $document->update(['sha256' => hash('sha256', $csv), 'size_bytes' => strlen($csv)]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.source-documents.extract', $document))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ai_extraction_jobs', [
            'source_document_id' => $document->id,
            'extractor' => 'local_csv',
            'status' => 'review',
        ]);
        $this->assertDatabaseHas('ai_extraction_rows', [
            'dataset_id' => $dataset->id,
            'variable_code' => 'VALUE',
            'period' => '2026',
            'status' => 'proposed',
        ]);
    }

    public function test_admin_can_recover_a_stale_processing_extraction(): void
    {
        config(['bacadulu.ai.stale_after_seconds' => 120]);
        [$dataset] = $this->createPublishedDataset('open');
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $document = $this->createSourceDocument($dataset, $admin, 'pdf');
        $job = AiExtractionJob::create([
            'source_document_id' => $document->id,
            'dataset_id' => $dataset->id,
            'requested_by' => $admin->id,
            'status' => 'processing',
            'extractor' => 'openai',
            'model' => 'test-model',
            'prompt_version' => 'dataset-extract-v2',
            'started_at' => now()->subMinutes(10),
        ]);
        $document->update(['status' => 'processing']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.ai-extractions.recover', $job))
            ->assertRedirect(route('admin.source-documents.show', $document));

        $this->assertDatabaseHas('ai_extraction_jobs', [
            'id' => $job->id,
            'status' => 'failed',
            'error_code' => 'stale_process_recovered',
        ]);
        $this->assertDatabaseHas('source_documents', [
            'id' => $document->id,
            'status' => 'uploaded',
        ]);
    }

    public function test_extraction_row_cannot_be_edited_through_a_different_job(): void
    {
        [$dataset, $variable] = $this->createPublishedDataset('open');
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $document = $this->createSourceDocument($dataset, $admin, 'csv');
        $firstJob = $this->createExtractionJob($document, $admin);
        $secondJob = $this->createExtractionJob($document, $admin);
        $row = $this->createExtractionRow($secondJob, $variable);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.ai-extractions.rows.update', [$firstJob, $row]), [
                'variable_code' => $row->variable_code,
                'variable_name' => $row->variable_name,
                'data_type' => 'numeric',
                'geography_code' => 'ID',
                'geography_name' => 'Indonesia',
                'period' => '2026',
                'value_numeric' => 99,
                'source_locator' => 'Baris CSV 2',
                'status' => 'accepted',
            ])
            ->assertNotFound();
    }

    public function test_approved_ai_extraction_still_enters_qc_as_unreviewed_and_resets_publication(): void
    {
        [$dataset, $variable] = $this->createPublishedDataset('open');
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $document = $this->createSourceDocument($dataset, $admin, 'csv');
        $job = $this->createExtractionJob($document, $admin);
        $row = $this->createExtractionRow($job, $variable, 'accepted');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.ai-extractions.approve', $job))
            ->assertRedirect(route('admin.datasets.observations.index', $dataset));

        $this->assertDatabaseHas('ai_extraction_jobs', ['id' => $job->id, 'status' => 'approved']);
        $this->assertDatabaseHas('ai_extraction_rows', ['id' => $row->id, 'status' => 'applied']);
        $this->assertDatabaseHas('dataset_observations', [
            'dataset_id' => $dataset->id,
            'dataset_variable_id' => $variable->id,
            'quality_status' => 'unreviewed',
        ]);
        $this->assertDatabaseHas('datasets', ['id' => $dataset->id, 'status' => 'draft']);
    }

    private function createSourceDocument(Dataset $dataset, User $admin, string $extension): SourceDocument
    {
        return SourceDocument::create([
            'dataset_id' => $dataset->id,
            'uploaded_by' => $admin->id,
            'original_name' => 'source.'.$extension,
            'storage_path' => 'source-documents/'.$dataset->id.'/source.'.$extension,
            'disk' => 'local',
            'mime_type' => $extension === 'pdf' ? 'application/pdf' : 'text/csv',
            'extension' => $extension,
            'size_bytes' => 100,
            'sha256' => hash('sha256', $dataset->id.'-'.$extension.'-'.$admin->id),
            'status' => 'ready',
        ]);
    }

    private function createExtractionJob(SourceDocument $document, User $admin): AiExtractionJob
    {
        return AiExtractionJob::create([
            'source_document_id' => $document->id,
            'dataset_id' => $document->dataset_id,
            'requested_by' => $admin->id,
            'status' => 'review',
            'extractor' => 'local_csv',
            'prompt_version' => 'dataset-extract-v1',
            'started_at' => now(),
            'completed_at' => now(),
        ]);
    }

    private function createExtractionRow(
        AiExtractionJob $job,
        DatasetVariable $variable,
        string $status = 'proposed'
    ): AiExtractionRow {
        return AiExtractionRow::create([
            'ai_extraction_job_id' => $job->id,
            'source_document_id' => $job->source_document_id,
            'dataset_id' => $job->dataset_id,
            'matched_variable_id' => $variable->id,
            'row_index' => 1,
            'variable_code' => $variable->code,
            'variable_name' => $variable->name,
            'unit' => $variable->unit,
            'data_type' => 'numeric',
            'geography_code' => 'ID',
            'geography_name' => 'Indonesia',
            'period' => '2026',
            'value_numeric' => 42,
            'source_locator' => 'Baris CSV 2',
            'confidence' => 1,
            'status' => $status,
            'validation_status' => 'valid',
        ]);
    }

    private function createReleasedObservation(
        Dataset $dataset,
        DatasetVariable $variable,
        string $entityCode = 'ID',
        string $entityName = 'Indonesia'
    ): DatasetObservation {
        return DatasetObservation::create([
            'dataset_id' => $dataset->id,
            'dataset_variable_id' => $variable->id,
            'geography_code' => $entityCode,
            'geography_name' => $entityName,
            'period' => '2026',
            'value_numeric' => 42,
            'quality_status' => 'verified',
        ]);
    }

    private function createPublishedDataset(string $accessType, string $dataScope = 'regional'): array
    {
        $provider = DataProvider::create([
            'name' => 'Provider Uji',
            'slug' => 'provider-uji-'.strtolower($accessType),
            'type' => 'institution',
            'status' => 'active',
        ]);

        $dataset = Dataset::create([
            'data_provider_id' => $provider->id,
            'title' => 'Dataset Uji '.ucfirst($accessType),
            'slug' => 'dataset-uji-'.strtolower($accessType),
            'code' => 'TEST-'.strtoupper(substr($accessType, 0, 4)),
            'summary' => 'Dataset yang digunakan untuk menguji batas akses publik.',
            'data_scope' => $dataScope,
            'category' => 'Pengujian',
            'access_type' => $accessType,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $variable = DatasetVariable::create([
            'dataset_id' => $dataset->id,
            'code' => 'VALUE',
            'name' => 'Nilai uji',
            'data_type' => 'text',
            'access_tier' => $accessType === 'open' ? 'open' : 'standard',
            'price_per_cell' => 0,
            'is_active' => true,
        ]);

        return [$dataset->load('provider'), $variable];
    }
}
