<?php

namespace App\Services;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class OtaApiService
{
    public function setSPN($msisdn) {
        try {
            // return [
            //         'transaction_id' => "123456781"
            // ];

            return [
                    "result"=> '404',
                    "params"=> "{}",
                    "por"=> "No card image"
            ];

            $otaUserName = config('services.ota.username');
            $otaPassword = config('services.ota.password');

            $parameters = '{
                "command": "SET_SPN",
                "msisdn": ' . $msisdn . ',
                "params":{"home":"1","roaming":"1","spn":"U9"},
                "por":"1",
                "refresh":"1"
            }';
            $baseUrl = config('services.ota.url');
            $endPoint = "{$baseUrl}/send_simota_command";

            $response = Http::withBasicAuth($otaUserName, $otaPassword)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->withBody($parameters)
                ->post($endPoint);

            if ($response->successful()) {

                Log::channel('api')->info('API Response For SPN change : ' . json_encode($response->json()) . ' of ' . $msisdn);
                return $response->json();
            }

            Log::channel('api')->info('API Response For SPN change failed with status : ' . $response->status() . $msisdn);

            return $response->json();

        } catch (\Throwable $e) {

            Log::channel('api')->info('API Response For SPN change request failed with Connection timed out : ' . $e->getMessage() . $msisdn);
            throw $e;
        }
    }

    public function setPNN($msisdn) {
        try {
            return [
                    'transaction_id' => "123456782"
                ];

            // return [
            //         "result" => '404',
            //         "params" => "{}",
            //         "por" => "No card image"
            // ];

            $otaUserName = config('services.ota.username');
            $otaPassword = config('services.ota.password');
            $parameters = '{
                "command": "SET_PNN",
                "msisdn": ' . $msisdn . ',
                "params":{
                    "index":"1",
                    "name":"U9"
                }
            }';
            $baseUrl = config('services.ota.url');
            $endPoint = "{$baseUrl}/send_simota_command";

            $response = Http::withBasicAuth($otaUserName, $otaPassword)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->withBody($parameters)
                ->post($endPoint);

            if ($response->successful()) {

                Log::channel('api')->info('API Response For PNN change : ' . json_encode($response->json()) . ' of ' . $msisdn);
                return $response->json();
            }

            Log::channel('api')->info('API Response For PNN change failed with status : ' . $response->status() . $msisdn);
            return $response->json();

        } catch (\Throwable $e) {

            Log::channel('api')->info('API Response For PNN change request failed with Connection timed out : ' . $e->getMessage() . $msisdn);
            throw $e;
        }
    }

    public function getTransactionStatus($transactionId) {
        try {
            return [
                    "por" => "029000005539ffffffffffffffffffffffffffff - POR OK",
                    "params" => [
                        "spn" => "U9"
                    ],
                    "result" => 200
                ];

            $otaUserName = config('services.ota.username');
            $otaPassword = config('services.ota.password');
            $baseUrl = config('services.ota.url');
            $endPoint = "{$baseUrl}/transaction_status/{$transactionId}";

            $response = Http::withBasicAuth($otaUserName, $otaPassword)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->post($endPoint);

            if ($response->successful()) {

                Log::channel('api')->info('API Response For Transaction Status : ' . json_encode($response->json()) . ' of ' . $transactionId);
                return $response->json();
            }

            Log::channel('api')->info('API Response For Transaction Status failed with status : ' . $response->status() . $transactionId);
            return $response->json();

        } catch (\Throwable $e) {

            Log::channel('api')->info('API Response For Transaction Status request failed with Connection timed out : ' . $e->getMessage() . $transactionId);
            throw $e;
        }
    }
}
