<?php

namespace App\Console\Commands;

use App\Models\DataConnector;
use App\Services\Sync\BpsConnectorService;
use App\Services\Sync\DataSyncException;
use Illuminate\Console\Command;

class SyncDueDataConnectors extends Command
{
    protected $signature = 'bacadulu:sync-connectors {--id=* : Jalankan ID konektor tertentu}';

    protected $description = 'Mengambil data dari konektor aktif yang sudah jatuh tempo';

    public function handle(BpsConnectorService $service): int
    {
        $ids = array_values(array_filter(array_map('intval', $this->option('id'))));
        $query = DataConnector::query()->where('status', 'active');
        if ($ids !== []) {
            $query->whereIn('id', $ids);
        } else {
            $query->due();
        }

        $connectors = $query->orderBy('id')->get();
        if ($connectors->isEmpty()) {
            $this->info('Tidak ada konektor yang perlu disinkronkan.');

            return self::SUCCESS;
        }

        $failures = 0;
        foreach ($connectors as $connector) {
            try {
                $run = $service->run($connector, null, 'scheduled');
                $this->info("Konektor #{$connector->id}: {$run->rows_count} baris masuk staging #{$run->id}.");
            } catch (DataSyncException $exception) {
                $failures++;
                $this->warn("Konektor #{$connector->id}: {$exception->getMessage()}");
            }
        }

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
