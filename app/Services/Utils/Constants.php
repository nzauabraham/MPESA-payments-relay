<?php

namespace App\Services\Utils;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class Constants{
    //EMALIFY
    public static $EMALI_CLIENT_ID = "aJr7ry5PDXkQ4chGQVyWlbsdADU0i3za";
    public static $EMALI_CLIENT_SECRET = "K7C1yhp7X1wIQceXjJrt86SG77WJXWHQYT0vsVgw";
    public static $EMALI_TOKEN_ENDPOINT = "https://api.emalify.com/v1/oauth/token";
    public static $PROJECT_ID = "oa0ebjmbyx4g7x3n";
    public static $EMALIPAY_EMALI_PURCHASE_AIRTIME_ENDPOINT="https://api.emalify.com/v1/projects/oa0ebjmbyx4g7x3n/airtime/purchase";

    //SAF
    public static $B2C_URL = "https://api.safaricom.co.ke/mpesa/b2c/v1/paymentrequest";
    public static $SAF_PROD = "https://api.safaricom.co.ke/";

    //DARAJA
    public static $DARAJA_TOKEN_URL = "https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";
    public static $DARAJA_STK_URL = "https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest";

    public static function requestEmalifyApiToken()
    {
        return Cache::remember('emalify_developer_api_token', 3540, function () { //cache for 59 minutes
            $tokenEndPint = self::$EMALI_TOKEN_ENDPOINT;
            $client = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]);

            $response = $client->post($tokenEndPint, [
                'client_id' => self::$EMALI_CLIENT_ID,
                'client_secret' => self::$EMALI_CLIENT_SECRET,
                'grant_type' => 'client_credentials'
            ]);

            $data = $response->json();

            Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @line ~> #" . __LINE__ . ' Response '
                . var_export($data, true));

            return $data['access_token'];
        });
    }

    public static function formatResponse($message = NULL, $status_code = Response::HTTP_OK,
                                          $reason = NULL, $other = NULL): JsonResponse
    {
        $response = array();
        $response['status'] = $status_code;
        $response['message'] = $message;
        if ($reason != NULL) {
            $response['reason'] = $reason;
        }
        #if ($other != NULL) {
        $response['more'] = $other;
        #}
        return response()->json($response, $status_code);
    }

    public static function initiateCurlPostRequest($CURLOPT_URL, $CURLOPT_HTTPHEADER, $CURLOPT_POSTFIELDS)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $CURLOPT_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $CURLOPT_POSTFIELDS,
            CURLOPT_HTTPHEADER => $CURLOPT_HTTPHEADER,
            CURLOPT_SSL_VERIFYPEER => 0
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            Log::debug("An error occurred, cURL Error #: " . var_export($err, true));

            return self::formatResponse('An error occurred, cURL Error #:' . $err . " on " . $CURLOPT_URL,
                Response::HTTP_FORBIDDEN);
        } else {
            Log::debug("Curl Response as #: " . var_export($response, true));
            return $response;
        }
    }


    public static function initiateCurlGetRequest($CURLOPT_URL, $HEADERS)
    {
        $curl = curl_init();

        //Log::debug("Endpoint received " . $CURLOPT_URL. "Header".print_r($HEADERS, 1));

        curl_setopt_array($curl, array(
            CURLOPT_URL => $CURLOPT_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => $HEADERS
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        Log::debug("[x]==>".print_r(curl_getinfo($curl), 1)."[x]".print_r(curl_errno($curl), 1)."[x]".print_r(curl_error($curl), 1));
        curl_close($curl);

        if ($err) {
            return self::formatResponse('An error occurred, cURL Error #:' . $err . " on " . $CURLOPT_URL,
                Response::HTTP_FORBIDDEN);
        } else {
            Log::debug("Curl Response as #: " . var_export($response, true));
            return $response;
        }
    }

    public static function uniqId($length = 6)
    {
        $bytes = openssl_random_pseudo_bytes(ceil($length / 2));

        return substr(bin2hex($bytes), 0, $length);
    }
}
