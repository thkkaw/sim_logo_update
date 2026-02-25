<?php

namespace App\Services;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class OtaApiService
{
    public function setSPN($msisdn) {
        try {

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

                Log::channel('api')->info('API Response For SPN Change', [
                    'msisdn' => $msisdn,
                    'response' => $response->json(),
                ]);

                return $response->json();
            }

            if ($response->serverError()) {

                Log::channel('api')->error('API Response For SPN change failed', [
                    'msisdn' => $msisdn,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return $response->json();

        } catch (\Throwable $e) {

            Log::channel('api')->error('API Response For SPN change request failed', [
                'msisdn' => $msisdn,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function setPNN($msisdn) {
        try {

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

                Log::channel('api')->info('API Response For PNN Change', [
                    'msisdn' => $msisdn,
                    'response' => $response->json(),
                ]);

                return $response->json();
            }

            if ($response->serverError()) {

                Log::channel('api')->error('API Response For PNN change failed', [
                    'msisdn' => $msisdn,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return $response->json();

        } catch (\Throwable $e) {
            Log::channel('api')->error('API Response For PNN change request failed', [
                'msisdn' => $msisdn,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getTransactionStatus($transactionId) {
        try {

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

                Log::channel('api')->info('API Response For Transaction Status', [
                    'transaction_id' => $transactionId,
                    'response' => $response->json(),
                ]);

                return $response->json();
            }

            if ($response->serverError()) {
                Log::channel('api')->error('API Call Failed', [
                    'transaction_id' => $transactionId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return $response->json();

        } catch (\Throwable $e) {

            Log::channel('api')->error('API request for transaction status failed', [
                'transaction_id' => $transactionId,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
