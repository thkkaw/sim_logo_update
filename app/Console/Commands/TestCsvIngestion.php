<?php

namespace App\Console\Commands;

use Illuminate\Http\Request;
use Illuminate\Console\Command;
use App\Http\Controllers\SimLogoController;

class TestCsvIngestion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:ingest-csv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reads CSV and pushes data through the SimLogoController API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = storage_path('app/Sample_Rebranding_list_100 records.csv');

        if (!file_exists($path)) {
            $this->error("CSV file not found at $path");
            return;
        }

        $file = fopen($path, 'r');
        fgetcsv($file); // Skip Header

        $controller = new SimLogoController();
        $stats = ['success' => 0, 'ignored' => 0, 'fail' => 0];

        $this->info("🚀 Starting ingestion test...");

        while (($row = fgetcsv($file)) !== false) {
            // Map CSV columns to API Payload
            // CSV: [0]msisdn, [2]t.iccid, [3]t.spn_pnn, [4]t.u9

            $payload = [
                'msisdn' => $row[0],
                'transaction_id' => $row[2],
                'type' => $row[3],     // 'SPN' or 'PNN'
                'logo' => $row[4],     // 'U9' or 'Ooredoo'
            ];

            // Manually create a Laravel Request to pass to the Controller
            $request = Request::create('/api/simlogo', 'POST', $payload);

            // Execute the Controller method directly
            // (This simulates the API call without needing a running server)
            $response = $controller->store($request);
            $data = json_decode($response->getContent(), true);

            // Output feedback based on result
            if ($response->getStatusCode() === 201) {
                $this->info("✅ Accepted: {$payload['transaction_id']}");
                $stats['success']++;
            } elseif (isset($data['status']) && $data['status'] === 'ignored') {
                $this->line("⚠️  Ignored (Logo not U9): {$payload['transaction_id']}");
                $stats['ignored']++;
            } else {
                $this->error("❌ Failed: {$payload['transaction_id']} - " . json_encode($data['errors'] ?? $data));
                $stats['fail']++;
            }
        }

        fclose($file);

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Successful Imports', $stats['success']],
            ['Ignored (Validation)', $stats['ignored']],
            ['Failed (Duplicate/Error)', $stats['fail']],
        ]);
    }
}
