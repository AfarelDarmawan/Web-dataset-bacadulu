<?php

namespace Tests\Feature;

use App\Models\DataConnector;
use App\Models\DataProvider;
use App\Models\Dataset;
use App\Models\DatasetVariable;
use App\Models\User;
use App\Services\Sync\BpsConnectorService;
use App\Services\Sync\BpsPayloadTransformer;
use App\Services\Sync\DataSyncException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BpsDataSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'bacadulu.bps.enabled' => false,
            'bacadulu.bps.api_key' => null,
            'bacadulu.bps.mock' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_mock_sync_stages_data_without_publishing_or_creating_observations(): void
    {
        [$admin, $dataset, $variable, $connector] = $this->fixtures();

        $run = app(BpsConnectorService::class)->run($connector, $admin);

        $this->assertSame('review', $run->status);
        $this->assertSame(6, $run->rows_count);
        $this->assertSame(6, $run->rows()->count());
        $this->assertSame(0, $dataset->observations()->count());
        $this->assertSame('published', $dataset->fresh()->status);
        $this->assertTrue((bool) data_get($run->metadata, 'mock'));
        $this->assertDatabaseMissing('data_sync_rows', ['data_sync_run_id' => $run->id, 'status' => 'applied']);
    }

    public function test_admin_review_applies_staging_as_unreviewed_and_resets_publication(): void
    {
        [$admin, $dataset, $variable, $connector] = $this->fixtures();
        $run = app(BpsConnectorService::class)->run($connector, $admin);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.automation.runs.bulk', [$connector, $run]), [
                'action' => 'accept_all_valid',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.automation.runs.apply', [$connector, $run]))
            ->assertRedirect(route('admin.datasets.observations.index', $dataset));

        $this->assertSame(6, $dataset->observations()->count());
        $this->assertSame(6, $dataset->observations()->where('quality_status', 'unreviewed')->count());
        $this->assertSame('draft', $dataset->fresh()->status);
        $this->assertSame('applied', $run->fresh()->status);
        $this->assertDatabaseHas('dataset_observations', [
            'dataset_id' => $dataset->id,
            'dataset_variable_id' => $variable->id,
            'geography_name' => 'DKI Jakarta',
            'quality_status' => 'unreviewed',
        ]);
    }

    public function test_sync_run_cannot_be_opened_through_another_connector(): void
    {
        [$admin, $dataset, $variable, $connector] = $this->fixtures();
        $secondVariable = DatasetVariable::create([
            'dataset_id' => $dataset->id,
            'code' => 'BPS-OTHER',
            'name' => 'Variabel BPS lain',
            'unit' => 'Orang',
            'data_type' => 'numeric',
            'access_tier' => 'open',
            'price_per_cell' => 0,
            'is_active' => true,
        ]);
        $secondConnector = $this->createConnector($dataset, $secondVariable, $admin, 'Konektor kedua');
        $run = app(BpsConnectorService::class)->run($connector, $admin);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.automation.runs.show', [$secondConnector, $run]))
            ->assertNotFound();
    }

    public function test_changing_schedule_recalculates_next_sync_with_the_new_frequency(): void
    {
        Carbon::setTestNow('2026-09-21 10:00:00');
        [$admin, $dataset, $variable, $connector] = $this->fixtures();
        $connector->update(['schedule' => 'daily', 'next_sync_at' => now()->addDay()]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.automation.connectors.update', $connector), [
                'name' => $connector->name,
                'dataset_variable_id' => $variable->id,
                'domain' => '0000',
                'variable_id' => 145,
                'period_ids' => '115;116',
                'derived_variable_id' => 0,
                'vertical_variable_id' => null,
                'derived_period_id' => 0,
                'language' => 'ind',
                'schedule' => 'monthly',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('monthly', $connector->fresh()->schedule);
        $this->assertSame('2026-10-21 10:00:00', $connector->fresh()->next_sync_at?->format('Y-m-d H:i:s'));
    }

    public function test_admin_can_open_connector_settings_with_its_inactive_variable(): void
    {
        [$admin, $dataset, $variable, $connector] = $this->fixtures();
        $variable->update(['is_active' => false]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.automation.connectors.edit', $connector))
            ->assertOk()
            ->assertSee($connector->name)
            ->assertSee($variable->name);
    }

    public function test_connector_with_history_cannot_be_moved_to_another_variable(): void
    {
        [$admin, $dataset, $variable, $connector] = $this->fixtures();
        $otherVariable = DatasetVariable::create([
            'dataset_id' => $dataset->id,
            'code' => 'PENDUDUK_LAIN',
            'name' => 'Penduduk kategori lain',
            'unit' => 'Orang',
            'data_type' => 'numeric',
            'access_tier' => 'open',
            'price_per_cell' => 0,
            'is_active' => true,
        ]);
        app(BpsConnectorService::class)->run($connector, $admin);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.automation.connectors.edit', $connector))
            ->put(route('admin.automation.connectors.update', $connector), [
                'name' => $connector->name,
                'dataset_variable_id' => $otherVariable->id,
                'domain' => '0000',
                'variable_id' => 145,
                'period_ids' => '115;116',
                'derived_variable_id' => 0,
                'vertical_variable_id' => null,
                'derived_period_id' => 0,
                'language' => 'ind',
                'schedule' => 'manual',
            ])
            ->assertSessionHasErrors('dataset_variable_id');

        $this->assertSame($variable->id, $connector->fresh()->dataset_variable_id);
    }

    public function test_ambiguous_derived_variables_are_rejected_before_staging(): void
    {
        [$admin, $dataset, $variable, $connector] = $this->fixtures();
        $connector->update(['config' => array_merge($connector->config, ['derived_variable_id' => null])]);
        $payload = [
            'var' => [['val' => 145, 'label' => 'Jumlah penduduk', 'unit' => 'Orang']],
            'turvar' => [
                ['val' => 1, 'label' => 'Laki-laki'],
                ['val' => 2, 'label' => 'Perempuan'],
            ],
            'labelvervar' => 'Provinsi',
            'vervar' => [['val' => 3100, 'label' => 'DKI Jakarta']],
            'tahun' => [['val' => 115, 'label' => '2025']],
            'turtahun' => [['val' => 0, 'label' => 'Tahun']],
            'datacontent' => [
                '310014511150' => 500,
                '310014521150' => 510,
            ],
        ];

        try {
            app(BpsPayloadTransformer::class)->transform($payload, $connector->fresh());
            $this->fail('Pemetaan ambigu seharusnya ditolak.');
        } catch (DataSyncException $exception) {
            $this->assertSame('bps_derived_variable_ambiguous', $exception->errorCode);
        }
    }

    public function test_researcher_cannot_access_data_automation(): void
    {
        $researcher = User::factory()->create(['role' => 'researcher', 'status' => 'active']);

        $this->actingAs($researcher, 'admin')
            ->get(route('admin.automation.index'))
            ->assertForbidden();
    }

    private function fixtures(): array
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $provider = DataProvider::create([
            'name' => 'Badan Pusat Statistik',
            'slug' => 'badan-pusat-statistik',
            'type' => 'government',
            'status' => 'active',
        ]);
        $dataset = Dataset::create([
            'data_provider_id' => $provider->id,
            'owner_id' => $admin->id,
            'title' => 'Statistik Penduduk Indonesia',
            'slug' => 'statistik-penduduk-indonesia',
            'code' => 'BPS-PENDUDUK',
            'summary' => 'Data uji sinkronisasi WebAPI BPS.',
            'data_scope' => 'regional',
            'category' => 'Kependudukan',
            'access_type' => 'open',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $variable = DatasetVariable::create([
            'dataset_id' => $dataset->id,
            'code' => 'PENDUDUK_TOTAL',
            'name' => 'Jumlah penduduk',
            'unit' => 'Orang',
            'data_type' => 'numeric',
            'access_tier' => 'open',
            'price_per_cell' => 0,
            'is_active' => true,
        ]);
        $connector = $this->createConnector($dataset, $variable, $admin, 'Penduduk BPS');

        return [$admin, $dataset, $variable, $connector];
    }

    private function createConnector(
        Dataset $dataset,
        DatasetVariable $variable,
        User $admin,
        string $name
    ): DataConnector {
        return DataConnector::create([
            'dataset_id' => $dataset->id,
            'dataset_variable_id' => $variable->id,
            'created_by' => $admin->id,
            'name' => $name,
            'type' => 'bps',
            'status' => 'active',
            'schedule' => 'manual',
            'config' => [
                'domain' => '0000',
                'variable_id' => 145,
                'period_ids' => '115;116',
                'derived_variable_id' => 0,
                'vertical_variable_id' => null,
                'derived_period_id' => 0,
                'language' => 'ind',
            ],
        ]);
    }
}
