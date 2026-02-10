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

            // return [
            //         "result"=> '404',
            //         "params"=> "{}",
            //         "por"=> "No card image"
            // ];

            $otaUserName = config('services.ota.username');
            $otaPassword = config('services.ota.password');

            $parameters = $parameters = [
                "command" => "SET_SPN",
                "msisdn"  => $msisdn,
                "params"  => [
                    "home"    => "1",
                    "roaming" => "1",
                    "spn"     => "U9",
                ],
                "por"     => "1",
                "refresh" => "1",
            ];

            $baseUrl = config('services.ota.url');
            $endPoint = "{$baseUrl}/send_simota_command";

            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];

            $response = Http::withBasicAuth($otaUserName, $otaPassword)
                ->withHeaders($headers)
                ->post($endPoint, $parameters);

            if ($response->successful()) {
                Log::channel('api')->info(
                    'API Response For SPN change : ' . json_encode($response->json()) . ' of ' . $msisdn
                );

                return $response->json();
            }

            if ($response->serverError()) {
                Log::channel('api')->error(
                    'API Response For SPN change failed with status : ' .
                    $response->status() .
                    ', body : ' . $response->body() .
                    ' for MSISDN : ' . $msisdn
                );
            }

            return $response->json();

        } catch (\Throwable $e) {

            Log::channel('api')->error(
                'API Response For SPN change request failed : ' .
                $e->getMessage() .
                ' for MSISDN : ' . $msisdn
            );

            return false;
        }
    }

    public function setPNN($msisdn) {
        try {
            // return [
            //         'transaction_id' => "123456782"
            //     ];

            // return [
            //         "result" => '404',
            //         "params" => "{}",
            //         "por" => "No card image"
            // ];

            $otaUserName = config('services.ota.username');
            $otaPassword = config('services.ota.password');

            $parameters = [
                "command" => "SET_PNN",
                "msisdn"  => $msisdn,
                "params"  => [
                    "index" => "1",
                    "name"  => "U9",
                ],
            ];

            $baseUrl = config('services.ota.url');
            $endPoint = "{$baseUrl}/send_simota_command";

            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];

            $response = Http::withBasicAuth($otaUserName, $otaPassword)
                ->withHeaders($headers)
                ->post($endPoint, $parameters);

            if ($response->successful()) {
                Log::channel('api')->info(
                    'API Response For PNN change ' . json_encode($response->json()) . ' of ' . $msisdn
                );

                return $response->json();
            }

            if ($response->serverError()) {
                Log::channel('api')->error(
                    'API Response For PNN change failed with status : ' .
                    $response->status() .
                    ', body : ' . $response->body() .
                    ' for MSISDN : ' . $msisdn
                );
            }

            Log::channel('api')->info('API Response For PNN change failed with status : ' . $response->status() . ' for MSISDN : ' . $msisdn);

            return $response->json();

        } catch (\Throwable $e) {

            Log::channel('api')->error(
                'API Response For PNN change request failed : ' .
                $e->getMessage() .
                ' for MSISDN : ' . $msisdn
            );

            return false;
        }
    }

    public function getTransactionStatus($transactionId) {
        try {
            // return [
            //         "por" => "029000005539ffffffffffffffffffffffffffff - POR OK",
            //         "params" => [
            //             "spn" => "U9"
            //         ],
            //         "result" => 200
            //     ];

            $otaUserName = config('services.ota.username');
            $otaPassword = config('services.ota.password');

            $baseUrl = config('services.ota.url');
            $endPoint = "{$baseUrl}/transaction_status/{$transactionId}";

            $headers = [
                'Content-Type' => 'application/json',
            ];

            $response = Http::withBasicAuth($otaUserName, $otaPassword)
                ->withHeaders($headers)
                ->get($endPoint);

            Log::channel('api')->debug('Response Status: ' . $response->status());
            Log::channel('api')->debug('Response Headers: ' . json_encode($response->headers()));
            Log::channel('api')->debug('Response Body: ' . $response->body());

           if ($response->successful()) {
                Log::channel('api')->info(
                    'API Response For Transaction Status : ' . json_encode($response->json()) . ' of ' . $transactionId
                );
                return $response->json();
            }

            if ($response->serverError()) {
                Log::channel('api')->error(
                    'API Response For Transaction Status : ' . $response->status() .
                    ', body : ' . $response->body() .
                    ' of : ' . $transactionId
                );
            }

            Log::channel('api')->info(
                'API Response For Transaction Status failed with status : ' .
                $response->status() . ' for transaction ID : ' . $transactionId
            );

            return $response->json();

        } catch (\Throwable $e) {

            Log::channel('api')->error(
                'API Response For Transaction Status request failed : ' .
                $e->getMessage() . ' for transaction ID : ' . $transactionId
            );

            return false;
        }
    }
}
