<?php

namespace App\Helpers;

class CURLHelpers
{

    /**
     * Curl Execute Helper
     *
     * @param string $url
     * @param array|null $data
     * @param string|null $method
     * @param string $headers
     * @return void
     */
    public static function curl(string $url, ?array $data = null, ?string $method = 'GET', string $headers)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $headers,
            ),
        ));

        $response = curl_exec($curl);
        $error_msg = null;

        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            $error_msg = json_decode($error_msg, true);
        }

        curl_close($curl);

        $response = json_decode($response, true);

        return [
            'response' => $response,
            'error_msg' => $error_msg,
        ];
    }
}
