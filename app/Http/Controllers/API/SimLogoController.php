<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\OtaApiService;
use App\Models\SimLogoTransaction;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class SimLogoController extends Controller
{

    protected OtaApiService $otaApiService;

    public function __construct(OtaApiService $otaApiService)
    {
        $this->otaApiService = $otaApiService;
    }

    public function index(Request $request)
    {
        Log::channel('api')->info('Payload : ' . json_encode($request->all()));

        $validator = Validator::make($request->all(), [
            'msisdn' => [
                'required',
                'string',
                'digits:12',
                'regex:/^9599[4-8][0-9]{7}$/',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'errors' => $validator->errors(),
            ], 422);
        }

        $msisdn = $validator->validated()['msisdn'];

        $simLogoTransaction = SimLogoTransaction::where('msisdn', $msisdn)->first();

        if ($simLogoTransaction) {

            if ($simLogoTransaction->status === '404') {
                return response()->json([
                    'status' => 404,
                    'msisdn' => $simLogoTransaction->msisdn,
                ], 200);
            }

            if ($simLogoTransaction->status === 'pending') {
                return response()->json([
                    'status' => 'pending',
                    'msisdn' => $simLogoTransaction->msisdn,
                ], 200);
            }

            if ($simLogoTransaction->status === 'success') {
                return response()->json([
                    'status' => 'success',
                    'msisdn' => $simLogoTransaction->msisdn,
                ], 200);
            }

            if ($simLogoTransaction->status === 'fail') {
                $simLogoTransaction->update([
                    'status' => 'pending'
                ]);
            }

        } else {

            $simLogoTransaction = SimLogoTransaction::create([
                'msisdn' => $msisdn,
            ]);
        }

        $spnApiResult = $this->requestSPN($msisdn, $simLogoTransaction);
        $pnnApiResult = $this->requestPNN($msisdn, $simLogoTransaction);

        if ($spnApiResult === "error"  && $pnnApiResult === "error"){
            return response()->json([
                'status' => 'OTA API Error: No Content',
                'msisdn' => $msisdn,
            ], 200);
        }

        return response()->json([
            'status' => 'processing',
            'msisdn' => $msisdn,
        ], 200);
    }

    public function requestSPN(string $msisdn, SimLogoTransaction $transaction)
    {
        $response = $this->otaApiService->setSPN($msisdn);

        if (!blank($response['transaction_id'] ?? null)) {

            $transaction->update([
                'transaction_spn' => $response['transaction_id'],
            ]);

            return true;

        } else {
            return "error";
        }

    }

    public function requestPNN(string $msisdn, SimLogoTransaction $transaction)
    {
        $response = $this->otaApiService->setPNN($msisdn);

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
