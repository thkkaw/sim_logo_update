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
        $transactions = SimLogoTransaction::where('status', 'pending')
            ->where('count', '<', config('services.ota.try_count'))
            ->whereBetween('created_at', [
                Carbon::today()->startOfDay(),
                Carbon::today()->endOfDay(),
            ])
            ->get();

        foreach ($transactions as $txn) {

            $simLogoTransaction = SimLogoTransaction::findOrFail($txn->id);

            $spnApiResult = $this->requestSPN($txn->msisdn, $txn, $otaApiService);
            $pnnApiResult = $this->requestPNN($txn->msisdn, $txn, $otaApiService);

            if (!$spnApiResult && !$pnnApiResult) {

                $simLogoTransaction->update([
                    'status' => '404',
                ]);
            }

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

    public function requestSPN(string $msisdn, SimLogoTransaction $transaction, $otaApiService)
    {
        $response = $otaApiService->setSPN($msisdn);

        if (isset($response['transaction_id'])) {
            $transaction->update([
                'transaction_spn' => $response['transaction_id'],
            ]);

            return true;

        } elseif (isset($response['result']) && $response['result'] === '404') {
            $remark = [];

            if (!empty($transaction->remark)) {
                $remark = json_decode($transaction->remark, true);
            }

            $remark[] = [
                'SPN',
                Carbon::now()->toDateTimeString(),
                'No card image',
            ];

            $transaction->update([
                'remark' => json_encode(array_values($remark)),
            ]);
            return false;

        } else {
            return "error";
        }
    }

    public function requestPNN(string $msisdn, SimLogoTransaction $transaction, $otaApiService)
    {
        $response = $otaApiService->setPNN($msisdn);

        if (isset($response['transaction_id'])) {
            $transaction->update([
                'transaction_pnn' => $response['transaction_id'],
            ]);

            return true;

        } elseif (isset($response['result']) && $response['result'] === '404') {
            $remark = [];

            if (!empty($transaction->remark)) {
                $remark = json_decode($transaction->remark, true);
            }

            $remark[] = [
                'PNN',
                Carbon::now()->toDateTimeString(),
                'No card image',
            ];

            $transaction->update([
                'remark' => json_encode(array_values($remark)),
            ]);
            return false;

        } else {
            return "error";
        }
    }
}
