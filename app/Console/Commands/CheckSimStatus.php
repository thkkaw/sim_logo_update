<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Services\OtaApiService;
use Illuminate\Console\Command;
use App\Models\SimLogoTransaction;
use Illuminate\Support\Facades\Log;

class CheckSimStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simlogo:check-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks status for transactions pending for over an hour';

    /**
     * Execute the console command.
     */
    public function handle(OtaApiService $otaApiService)
    {
        $startWindow = Carbon::now()->subHours(2);
        $endWindow = Carbon::now()->subHour();

        $transactions = SimLogoTransaction::where('status', 'pending')
            ->whereBetween('updated_at', [$startWindow, $endWindow])
            ->get();

        // $transactions = SimLogoTransaction::where('status', 'pending')
        //     ->where('updated_at', '>=', Carbon::now()->subHour())
        //     ->get();

        foreach ($transactions as $txn) {

            $transactionStatus = $otaApiService->getTransactionStatus($txn->transaction_id);

            if (isset($transactionStatus) && isset($transactionStatus["params"]["spn"])) {

                Log::channel('api')->info("SPN exists for transaction: {$txn->transaction_spn}");
                if ($transactionStatus["params"]["spn"] === 'U9') {
                    $txn->update([
                        'status' => 'success'
                    ]);
                }
            }
            // Logic: "To get the transaction status"
            $this->info("Checking status for: {$txn->transaction_spn}");

            // Example: Update DB based on result
            // $txn->update(['status' => 'success']);
        }
    }
}
