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

            // ---------- SPN ----------
            if (!blank($txn?->transaction_spn)) {

                Log::channel('api')->info("Start SPN for transaction : {$txn->transaction_spn}");

                $transactionSpnStatus = $otaApiService->getTransactionStatus($txn->transaction_spn);

                if (
                    !empty($transactionSpnStatus)
                    && $transactionSpnStatus['result'] == '200'
                    && array_key_exists('params', $transactionSpnStatus)
                ) {

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

                            Log::channel('api')->debug('SPN flag set!', [
                                'msisdn' => $txn->msisdn,
                                'spn' => $txn->transaction_spn,
                            ]);
                        }
                    }
                }
            }

            // ---------- PNN ----------
            if (!blank($txn?->transaction_pnn)) {

                Log::channel('api')->info("Start PNN for transaction: {$txn->transaction_pnn}");

                $transactionPnnStatus = $otaApiService->getTransactionStatus($txn->transaction_pnn);

                if (
                    !empty($transactionPnnStatus)
                    && $transactionPnnStatus['result'] == '200'
                    && array_key_exists('params', $transactionPnnStatus)
                ) {

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

                            Log::channel('api')->debug('PNN flag set!', [
                                'msisdn' => $txn->msisdn,
                                'pnn' => $txn->transaction_pnn,
                            ]);
                        }
                    }

                }
            }

            // if ($txn->spn_flag === 'U9' || $txn->pnn_flag === 'U9') {
            //     $txn->update(['status' => 'success']);

            //     Log::channel('api')->debug('Success!', [
            //         'msisdn' => $txn->msisdn,
            //         'spn' => $txn->transaction_spn ?? '',
            //         'pnn' => $txn->transaction_pnn ?? '',
            //     ]);
            // }

            if ($spn && $pnn) {
                $txn->update([
                    'status' => 'success'
                ]);

                Log::channel('api')->debug('Both flags set!', [
                    'msisdn' => $txn->msisdn,
                    'spn' => $txn->transaction_spn ?? '',
                    'pnn' => $txn->transaction_pnn ?? '',
                ]);
            }
        }


        // if ($transactions->isNotEmpty()) {

        //     foreach ($transactions as $txn) {

        //         if (!blank($txn?->transaction_spn)) {

        //             Log::channel('api')->info("Start SPN for transaction : {$txn->transaction_spn}");

        //             $transactionSpnStatus = $otaApiService->getTransactionStatus($txn->transaction_spn);

        //             if (!empty($transactionSpnStatus)) {

        //                 if ($transactionSpnStatus['result'] == 200) {

        //                     if (array_key_exists('params', $transactionSpnStatus)) {

        //                         Log::channel('api')->info("SPN for transaction: {$txn->msisdn}");

        //                         $params = json_decode($transactionSpnStatus['params'] ?? '', true);

        //                         if (!empty($params)) {

        //                             if ($params['spn'] === 'U9') {
        //                                 $txn->update([
        //                                     'spn_flag' => 'SPN'
        //                                 ]);

        //                                 $spn = true;
        //                             }
        //                         }

        //                     }
        //                 }

        //             }
        //         }

        //         if (!blank($txn?->transaction_pnn)) {

        //             Log::channel('api')->info("Start PNN for transaction: {$txn->transaction_pnn}");

        //             $transactionPnnStatus = $otaApiService->getTransactionStatus($txn->transaction_pnn);

        //             if (!empty($transactionPnnStatus)) {

        //                 if ($transactionPnnStatus['result'] == 200) {

        //                     if (array_key_exists('params', $transactionPnnStatus)) {

        //                         $params = json_decode($transactionPnnStatus['params'], true);

        //                         $name = $params['pnn']['name'];

        //                         Log::channel('api')->info("PNN for transaction: {$txn->msisdn}");

        //                         if ($name === 'U9') {

        //                             $txn->update([
        //                                 'pnn_flag' => 'PNN'
        //                             ]);

        //                             $pnn = true;

        //                         }
        //                     }
        //                 }
        //             }
        //         }

        //         if($spn && $pnn){
        //             $txn->update([
        //                 'status' => 'success'
        //             ]);
        //         }
        //     }
        // }
    }
}
