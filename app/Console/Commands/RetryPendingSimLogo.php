<?php

namespace App\Console\Commands;

use App\Models\SimLogoTransaction;
use App\Services\OtaApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RetryPendingSimLogo extends Command
{

    public array $remark = [];

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
        $targetDate = Carbon::yesterday()->toDateString();

        Log::channel('api')->info("Starting retry process for date: {$targetDate}");

        $transactions = SimLogoTransaction::where('status', 'pending')
            ->where('count', '<', config('services.ota.try_count'))
            ->whereDate('created_at', Carbon::yesterday())
            ->get();

        if ($transactions->isNotEmpty()) {

            foreach ($transactions as $txn) {

                $simLogoTransaction = SimLogoTransaction::findOrFail($txn->id);

                $this->requestSPN($txn->msisdn, $txn, $otaApiService);
                $this->requestPNN($txn->msisdn, $txn, $otaApiService);

                if (($txn->count + 1) == config('services.ota.try_count')) {

                    // Log::info($simLogoTransaction->count . ',' . config('services.ota.try_count'));
                    $remark = [];

                    if (!empty($simLogoTransaction->remark)) {
                        $remark = json_decode($simLogoTransaction->remark, true);
                    }

                    $remark[] = [
                        Carbon::now()->toDateTimeString(),
                        'Try count has reached its limit.',
                    ];

                    $simLogoTransaction->update([
                        'count' => 0,
                        'status' => 'fail',
                        'remark' => json_encode(array_values($remark)),
                    ]);
                } else {
                    $simLogoTransaction->update([
                        'count' => $txn->count + 1,
                    ]);
                }
            }
        }

        Log::channel('api')->info("Retry process completed for date: {$targetDate}");
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
