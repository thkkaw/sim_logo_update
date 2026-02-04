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

        Log::channel('api')->info("SPN for transaction: {$startWindow} {$endWindow}");

        $transactions = SimLogoTransaction::where('status', 'pending')
            ->whereBetween('updated_at', [$startWindow, $endWindow])
            ->get();

        foreach ($transactions as $txn) {

            Log::channel('api')->info("SPN for transaction: {$txn->transaction_spn}");

            if ($txn->transaction_spn != null) {
                $transactionSpnStatus = $otaApiService->getTransactionStatus($txn->transaction_spn);

                if (isset($transactionSpnStatus) && isset($transactionSpnStatus["params"])) {

                    Log::channel('api')->info("SPN for transaction: {$txn->transaction_spn}");

                    $params = json_decode($transactionSpnStatus["params"], true);

                    if ($params['spn'] === 'U9') {
                        $txn->update([
                            'status' => 'success',
                            'spn_pnn' => 'SPN'
                        ]);
                    }
                }
            }

            if ($txn->transaction_pnn != null) {
                $transactionPnnStatus = $otaApiService->getTransactionStatus($txn->transaction_pnn);

                if (isset($transactionPnnStatus) && isset($transactionPnnStatus["params"])) {

                    

                    $params = json_decode($response['params'], true);

                    $name = $params['pnn']['name'];

                    Log::channel('api')->info("PNN for transaction: {$params['pnn']['name']}");

                    if ($name === 'U9') {
                        $txn->update([
                            'status' => 'success',
                            'spn_pnn' => 'PNN'
                        ]);
                    }
                }
            }

        }
    }
}
