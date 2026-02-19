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

        Log::channel('api')->info("Checking status for transactions between {$startWindow} to {$endWindow}");

        $transactions = SimLogoTransaction::where('status', 'pending')
        ->whereBetween('updated_at', [$startWindow, $endWindow])
        ->get();

        foreach ($transactions as $txn) {
            $spn = false;
            $pnn = false;
            $remark = $txn->remark ?? [];

            // ---------- SPN ----------
            if (!blank($txn?->transaction_spn)) {

                Log::channel('api')->info("Start SPN for transaction : {$txn->transaction_spn}");

                $transactionSpnStatus = $otaApiService->getTransactionStatus($txn->transaction_spn);

                if (!empty($transactionSpnStatus)) {

                    if ($transactionSpnStatus['result'] == '200' && array_key_exists('params', $transactionSpnStatus)) {

                        $params = json_decode($transactionSpnStatus['params'] ?? '', true);

                        Log::channel('api')->debug('OTA Params', [
                            'msisdn' => $txn->msisdn ?? null,
                            'params' => $params,
                        ]);

                        if (!empty($params)) {
                            $name = $params['spn'];

                            if ($name === 'U9') {
                                $txn->update([
                                    'spn_flag' => $name
                                ]);

                                $spn = true;

                                Log::channel('api')->info('SPN flag set!', [
                                    'msisdn' => $txn->msisdn,
                                    'spn' => $txn->transaction_spn,
                                ]);
                            }
                        }
                    }

                    if ($transactionSpnStatus['result'] == '404') {
                        $remark[] = [
                            Carbon::now()->toDateTimeString(),
                            'SPN SMS delivery failed (404).',
                        ];
                    }

                }
            }

            // ---------- PNN ----------
            if (!blank($txn?->transaction_pnn)) {

                Log::channel('api')->info("Start PNN for transaction: {$txn->transaction_pnn}");

                $transactionPnnStatus = $otaApiService->getTransactionStatus($txn->transaction_pnn);

                if (!empty($transactionPnnStatus)) {

                    if ($transactionPnnStatus['result'] == '200' && array_key_exists('params', $transactionPnnStatus)) {

                        $params = json_decode($transactionPnnStatus['params'], true);

                        Log::channel('api')->debug('OTA Params', [
                            'msisdn' => $txn->msisdn ?? null,
                            'params' => $params,
                        ]);

                        if (!empty($params)) {
                            $name = $params['pnn']['name'];

                            if ($name === 'U9') {

                                $txn->update([
                                    'pnn_flag' => $name
                                ]);

                                $pnn = true;

                                Log::channel('api')->info('PNN flag set!', [
                                    'msisdn' => $txn->msisdn,
                                    'pnn' => $txn->transaction_pnn,
                                ]);
                            }
                        }
                    }

                    if ($transactionPnnStatus['result'] == '404') {

                        $remark[] = [
                            Carbon::now()->toDateTimeString(),
                            'PNN SMS delivery failed (404).',
                        ];
                    }
                }
            }

            if (!empty($remark)) {
                $txn->update(['remark' => $remark]);
            }

            if ($spn && $pnn) {
                $txn->update(['status' => 'success']);

                Log::channel('api')->info('Both flags set!', [
                    'msisdn' => $txn->msisdn,
                    'spn' => $txn->transaction_spn ?? '',
                    'pnn' => $txn->transaction_pnn ?? '',
                ]);
            }
        }

        Log::channel('api')->info("Completed checking status for transactions between {$startWindow} to {$endWindow}");
    }
}
