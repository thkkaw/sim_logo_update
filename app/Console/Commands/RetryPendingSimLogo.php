<?php

namespace App\Console\Commands;

use App\Models\SimLogoTransaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RetryPendingSimLogo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simlogo:retry-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retries pending transactions where count < 7';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactions = SimLogoTransaction::where('status', 'pending')
            ->where('count', '<', 7)
            ->whereDate('created_at', Carbon::today())
            ->get();

        foreach ($transactions as $txn) {
            $response = Http::post('https://api-url/api/simota/send_simota_command', [
                'transaction_id' => $txn->transaction_id,
                // Add other required payloads here
            ]);

            // Increment count as per logic
            $txn->increment('count');

            $this->info("Retried transaction: {$txn->transaction_id}");
        }
    }
}
