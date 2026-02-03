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
    public array $remark = [];

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

        if (!$spnApiResult && !$pnnApiResult) {

            $simLogoTransaction->update([
                'status' => '404'
            ]);

            return response()->json([
                'status' => 404,
                'msisdn' => $msisdn,
            ], 200);
        }

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

    public function requestPNN(string $msisdn, SimLogoTransaction $transaction)
    {
        $response = $this->otaApiService->setPNN($msisdn);

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

    // public function index(Request $request)
    // {
    //     // --- 1. Request Handling & Data Validation ---
    //     $validator = Validator::make($request->all(), [
    //         'msisdn' => 'required|string|digits:12|regex:/^9599[4-8][0-9]{7}$/',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 'fail',
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     // --- 2. Logic: "Is logo U9?" ---
    //     $simLogoTransaction = SimLogoTransaction::where('msisdn', $request->msisdn)->first();

    //     if (isset($simLogoTransaction)) {

    //         if ($simLogoTransaction->status === 404){
    //             return response()->json([
    //                 'status' => 404,
    //                 'msisdn' => $simLogoTransaction->msisdn
    //             ], 200);
    //         }

    //         if ($simLogoTransaction->status === 'success') {
    //             return response()->json([
    //                 'status' => 'success',
    //                 'msisdn' => $simLogoTransaction->msisdn
    //             ], 200);
    //         }

    //         //call SET_SPN API
    //         $this->requestSPN($request->msisdn, $simLogoTransaction->id);

    //         $this->requestPNN($request->msisdn, $simLogoTransaction->id);

    //     } else {
    //         return response()->json(['message' => 'not yet'], 404);
    //     }

    // }

    // public function requestSPN($msisdn, $id)
    // {
    //     $simLogoTransaction = SimLogoTransaction::findOrFail($id);
    //     $spnApiResult = $this->otaApiService->setSPN($msisdn);

    //         if (isset($spnApiResult["result"]) && $spnApiResult["result"] === 404) {
    //             $simLogoTransaction->status = 404;
    //             $simLogoTransaction->save();

    //             return response()->json([
    //                 'status' => 'fail',
    //                 'message' => 'No card image'
    //             ], 200);
    //         }

    //         if (isset($spnApiResult["transaction_id"])) {
    //             $simLogoTransaction->transaction_spn = $spnApiResult["transaction_id"];
    //             $simLogoTransaction->save();

    //             return response()->json([
    //                 'status' => 'fail',
    //                 'message' => 'No card image'
    //             ], 200);
    //         }
    // }

    // public function requestPNN($msisdn, $id)
    // {
    //     $simLogoTransaction = SimLogoTransaction::findOrFail($id);
    //     $pnnApiResult = $this->otaApiService->setPNN($msisdn);

    //         if (isset($pnnApiResult["result"]) && $pnnApiResult["result"] === 404) {
    //             $simLogoTransaction->status = 404;
    //             $simLogoTransaction->save();

    //             return response()->json([
    //                 'status' => 'fail',
    //                 'message' => 'No card image'
    //             ], 200);
    //         }

    //         if (isset($pnnApiResult["transaction_id"])) {
    //             $simLogoTransaction->transaction_pnn = $pnnApiResult["transaction_id"];
    //             $simLogoTransaction->save();

    //             return response()->json([
    //                 'status' => 'success',
    //                 'transaction_id' => $pnnApiResult["transaction_id"]
    //             ], 200);
    //         }
    // }
}
