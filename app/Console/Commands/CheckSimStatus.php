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
        $spn = false;
        $pnn = false;

        Log::channel('api')->info("SPN for transaction: {$startWindow} {$endWindow}");

        $transactions = SimLogoTransaction::where('status', 'pending')
            ->whereBetween('updated_at', [$startWindow, $endWindow])
            ->get();
        if ($transactions->isNotEmpty()) {
            foreach ($transactions as $txn) {

                if (isset($txn->transaction_spn)) {

                    $transactionSpnStatus = $otaApiService->getTransactionStatus($txn->transaction_spn);

                    if (!empty($transactionSpnStatus)) {

                        if ($transactionSpnStatus['result'] == '200') {

                            if (array_key_exists('params', $transactionSpnStatus)) {
                            
                                Log::channel('api')->info("SPN for transaction: {$txn->msisdn}");

                                $params = json_decode($transactionSpnStatus['params'] ?? '', true);

                                if (!empty($params)) {

                                if ($params['spn'] === 'U9') {
                                        $txn->update([                                        
                                            'spn' => 'SPN'
                                        ]);

                                        $spn = true;
                                    } 
                                }
                                
                            }
                        }
                        
                    }
                }

                if (isset($txn->transaction_pnn)) {

                    $transactionPnnStatus = $otaApiService->getTransactionStatus($txn->transaction_pnn);

                    if (!empty($transactionPnnStatus)) {

                        if ($transactionPnnStatus['result'] == '200') {

                            if (array_key_exists('params', $transactionPnnStatus)) {                 

                                $params = json_decode($transactionPnnStatus['params'], true);

                                $name = $params['pnn']['name'];

                                Log::channel('api')->info("PNN for transaction: {$txn->msisdn}");

                                if ($name === 'U9') {

                                    $txn->update([                                   
                                        'pnn' => 'PNN'
                                    ]);

                                    $pnn = true;

                                }
                            }
                        }
                    }
                }

                if($spn && $pnn){
                    $txn->update([
                        'status' => 'success'                   
                    ]);
                }

            }
        }
    }
}
