<?php

class ClientEvolutionApi
{
    public static function postRequest($endpoint, $data): array
    {
        $apiKey = EVOAPI_API_KEY;
        $apiUrl = EVOAPI_API_URL;

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "{$apiUrl}{$endpoint}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "apikey: {$apiKey}",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);

        if ($err) {
            error_log("{$endpoint} cURL Post Request Error: $err");
            throw new Exception("{$endpoint} HTTP request failed: $err");
        }

        error_log("{$endpoint} cURL Post Request Response: $response");

        return json_decode($response, true);
    }
}