<?php

namespace App\Console\Commands;

use App\Models\SimLogoTransaction;
use App\Services\OtaApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
    protected $description = 'Retries pending transactions where count < try_count';

    /**
     * Execute the console command.
     */
    public function handle(OtaApiService $otaApiService)
    {
        Log::channel('api')->info("Starting retry process...");

        $transactions = SimLogoTransaction::where('status', 'pending')
            ->where('count', '<', config('services.ota.try_count'))
            ->whereDate('created_at', '<', Carbon::today())
            ->get();

        Log::channel('api')->info('Pending transactions found for retry: ' . $transactions->count());

        if ($transactions->isNotEmpty()) {

            foreach ($transactions as $txn) {

                $simLogoTransaction = SimLogoTransaction::findOrFail($txn->id);

                $this->requestSPN($txn->msisdn, $txn, $otaApiService);
                $this->requestPNN($txn->msisdn, $txn, $otaApiService);

                if (($txn->count + 1) >= config('services.ota.try_count')) {

                    // Log::info($simLogoTransaction->count . ',' . config('services.ota.try_count'));
                    $remark = $simLogoTransaction->remark ?? [];

                    $remark[] = [
                        Carbon::now()->toDateTimeString(),
                        'Try count has reached its limit.',
                    ];

                    $simLogoTransaction->update([
                        'count' => 0,
                        'status' => 'fail',
                        'remark' => $remark,
                    ]);

                } else {
                    $simLogoTransaction->update([
                        'count' => $txn->count + 1,
                    ]);
                }
            }
        }

        Log::channel('api')->info("Retry process completed.");
    }

    public function requestSPN(string $msisdn, SimLogoTransaction $transaction, $otaApiService)
    {
        $response = $otaApiService->setSPN($msisdn);

        if (!blank($response['transaction_id'] ?? null)) {
            $transaction->update([
                'transaction_spn' => $response['transaction_id'],
            ]);

            return true;

        } else {
            return "error";
        }
    }

    public function requestPNN(string $msisdn, SimLogoTransaction $transaction, $otaApiService)
    {
        $response = $otaApiService->setPNN($msisdn);

        if (!blank($response['transaction_id'] ?? null)) {
            $transaction->update([
                'transaction_pnn' => $response['transaction_id'],
            ]);

            return true;

        } else {
            return "error";
        }
    }
}
