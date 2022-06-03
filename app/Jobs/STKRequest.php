<?php

namespace App\Jobs;

use App\Http\Traits\ResponseTrait;
use App\Services\Utils\Constants;
use Illuminate\Bus\Queueable;
use App\Models\StkRequest as StkRequestModel;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class STKRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ResponseTrait;

    private string $final_callback_url;
    private string $callback_url;
    private string $reference_number;
    private int $unique_id;
    private int $paybill;
    private int $msisdn;
    private int $amount;
    private array $paybill_configs;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($amount, $msisdn, $short_code, $unique_id, $ref_no,
                                $callback_url, $final_callback_url, $configs)
    {
        $this->amount = $amount;
        $this->msisdn = $msisdn;
        $this->paybill = $short_code;
        $this->unique_id = $unique_id;
        $this->reference_number = $ref_no;
        $this->callback_url = $callback_url;
        $this->final_callback_url = $final_callback_url;
        $this->paybill_configs = $configs;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @msisdn ~> (" . $this->msisdn . ") :: @line ~> #" . __LINE__ . "stk producer queue");
        Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @msisdn ~> (" . $this->msisdn . ") :: @line ~> #" . __LINE__ . "reference number::".$this->reference_number);
        $DARAJA_CONSUMER_SECRET = $this->paybill_configs['secret'];
        $DARAJA_CONSUMER_KEY = $this->paybill_configs['key'];
        $DARAJA_PASSWORD = $this->paybill_configs['passkey'];

        $daraja_token = $this->getDarajaToken($DARAJA_CONSUMER_SECRET, $DARAJA_CONSUMER_KEY);

        $timestamp = time();
        $transaction_id = "";
        $timestamp = gmdate("YmdHis", $timestamp);

        $password = base64_encode($this->paybill . $DARAJA_PASSWORD . $timestamp);

        Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @msisdn ~> (" . $this->msisdn . ") :: @line ~> #"
            . __LINE__ . 'shortcode= ' . $this->paybill . ' | password = ' . $DARAJA_PASSWORD .
            ' | timestamp= ' . $timestamp . '   |||   passkey= ' . $password
        );

        if($this->paybill_configs['type'] == 'C2BPAYBILL'){
            //Request Body
            $request_body = "{\r\n
            \"BusinessShortCode\":\"" . $this->paybill . "\",\r\n
            \"Password\": \"" . $password . "\",\r\n
            \"Timestamp\":\"" . $timestamp . "\",\r\n
            \"TransactionType\": \"CustomerPayBillOnline\",\r\n
            \"Amount\":\"" . $this->amount . "\",\r\n
            \"PartyA\":\"" . $this->msisdn . "\",\r\n
            \"PartyB\":\"" . $this->paybill . "\",\r\n
            \"PhoneNumber\":\"" . $this->msisdn . "\",\r\n
            \"CallBackURL\":\"" .config('app.url') . '/api/v1/stk_callback'. "\",\r\n
            \"AccountReference\":\"" . $this->reference_number . "\",\r\n
            \"TransactionDesc\":\"Subscription\"\r\n}";
        }else{
            //Request Body
            $request_body = "{\r\n
            \"BusinessShortCode\":\"" . $this->paybill . "\",\r\n
            \"Password\": \"" . $password . "\",\r\n
            \"Timestamp\":\"" . $timestamp . "\",\r\n
            \"TransactionType\": \"CustomerBuyGoodsOnline\",\r\n
            \"Amount\":\"" . $this->amount . "\",\r\n
            \"PartyA\":\"" . $this->msisdn . "\",\r\n
            \"PartyB\":\"" . $this->paybill_configs['parent_till'] . "\",\r\n
            \"PhoneNumber\":\"" . $this->msisdn . "\",\r\n
            \"CallBackURL\":\"" .config('app.url') . '/api/v1/stk_callback' . "\",\r\n
            \"AccountReference\":\"" .  $this->reference_number . "\",\r\n
            \"TransactionDesc\":\"Subscription\"\r\n}";
        }

//"https://encxj84jhi35ar.m.pipedream.net"
        //config('app.url') . '/api/v1/stk_callback'

        // Request Header
        $header = array(
            "Authorization: Bearer " . $daraja_token,
            "Content-Type: application/json",
            "cache-control: no-cache"
        );

        Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @msisdn ~> (" . $this->msisdn . ") :: @line ~> #" . __LINE__ . "STK Push request payload " . $request_body);
        Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @msisdn ~> (" . $this->msisdn . ") :: @line ~> #" . __LINE__ . "STK Callback URL  " . $this->callback_url);
        Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @msisdn ~> (" . $this->msisdn . ") :: @line ~> #" . __LINE__ . "STK Push URL  " . Constants::$DARAJA_STK_URL);

        // Perform the CURL request
        $response_a = Constants::initiateCurlPostRequest(Constants::$DARAJA_STK_URL, $header, $request_body);

        // Retrieve the response
        if (json_decode($response_a, true)) {
            Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @msisdn ~> (" . $this->msisdn . ") :: @line ~> #" . __LINE__ . "STK popup initiated \n" . $response_a);
            $response = json_decode($response_a, true);
            Log::debug(gettype($response_a) . " " . gettype($response));

            if (isset($response['CheckoutRequestID'])) {
                $transaction_id = json_decode($response_a, true)["CheckoutRequestID"];

                if ($this->saveDepositRequest($this->amount, $this->msisdn, $response, $transaction_id, $this->callback_url, 1)) {
                    return Constants::formatResponse("Subscriber Request" . $response_a, Response::HTTP_OK);
                }
            } else {
                //An error occurred triggering STK push
                if (isset($response['errorMessage'])) {
                    $errorMessage = json_decode($response_a, true)["errorMessage"];
                    if (isset($response['CheckoutRequestID']))
                        $transaction_id = json_decode($response_a, true)["CheckoutRequestID"];

                    Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @msisdn ~> (" . $this->msisdn . ") :: @line ~> #" . __LINE__ . "Running Status update on FAILED Daraja request for " .
                        $this->msisdn . " for " . $this->amount . " Status is " . var_export($errorMessage, true));

                    if ($this->saveDepositRequest($this->amount, $this->msisdn, $errorMessage, $transaction_id,  $this->callback_url, 0)) {
                        return Constants::formatResponse("C2B  Request" . $response_a, Response::HTTP_OK);
                    }

                    return Constants::formatResponse($errorMessage . $response_a, Response::HTTP_OK);
                } else
                    return Constants::formatResponse("An error occurred f" . $response_a, Response::HTTP_OK);
            }
        } else {
            Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @msisdn ~> (" . $this->msisdn . ") :: @line ~> #" . __LINE__ . "An error occurred, cURL Error #");
            return Constants::formatResponse('An error occurred, cURL Error #: ' . $response_a, Response::HTTP_OK);
        }
    }

    public function getDarajaToken($DARAJA_CONSUMER_SECRET, $DARAJA_CONSUMER_KEY)
    {
        Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @msisdn ~> (" . $this->msisdn . ") :: @line ~> #" . __LINE__ . " Consumer_key " . $DARAJA_CONSUMER_KEY .
            ':' . $DARAJA_CONSUMER_SECRET);

        $credentials = base64_encode($DARAJA_CONSUMER_KEY .
            ':' . $DARAJA_CONSUMER_SECRET);

        // Request Header
        $header = array(
            "Authorization: Basic " . $credentials,
            "cache-control: no-cache"
        );


        Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @msisdn ~> (" . $this->msisdn . ") :: @line ~> #" . __LINE__ . "Fetching Daraja Token , Credentials " . $credentials);
        // Perform the CURL request
        $response = Constants::initiateCurlGetRequest(Constants::$DARAJA_TOKEN_URL, $header);

        // Retrieve the Oauth Token from Daraja
        if (json_decode($response, true)) {
            Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @msisdn ~> (" . $this->msisdn . ") :: @line ~> #" . __LINE__ . "Daraja API Token Retrieved" . $response);
            return json_decode($response, true)["access_token"];
        } else {
            Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @msisdn ~> (" . $this->msisdn . ") :: @line ~> #" . __LINE__ . "An error occurred when retrieving, Daraja API Token, cURL Error #");
            return Constants::formatResponse('An error occurred, cURL Error #: ' . $response, Response::HTTP_FORBIDDEN);
        }
    }

    public function saveDepositRequest($amount, $msisdn, $response, $transaction_id, $callback_url, $success)
    {
        try {
            Log::debug("Core::Class ~> " . __CLASS__ .
                " :: @method ~> (" . __FUNCTION__ . ") :: @line ~> #" . __LINE__ . " registerPaymentRequest Amount:" .
                $amount . "MSISDN::".$msisdn . $transaction_id);

            $update = DB::table('stk_requests')
                ->where('msisdn', $msisdn)
                ->whereNull('mpesa_response')
                ->whereNull('mpesa_callback')
                ->whereNull('mpesa_reference')
                ->whereNull('transaction_id')
                ->latest()
                ->take(1)
                ->limit(1)
                ->update(array(
                    'msisdn' => $msisdn,
                    'mpesa_response' => json_encode($response),
                    'transaction_id' => $transaction_id,
                    'callback_url' => $callback_url,
                    "updated_at" => \Carbon\Carbon::now(),
                ));

            Log::debug("Core::Class ~> " . __CLASS__ . " :: @method ~> (" . __FUNCTION__ . ") :: @line ~> #" . __LINE__ . " mpesaRequest  update ~> " . $update);
            //Update subscription Status
            $stk_request = StkRequestModel::where('transaction_id', $transaction_id)->first();
            $payload = [
                'mpesa_response'=> $response,
                'success'=>$success,
                'unique_id'=>$stk_request->unique_id,
                'status_code'=>400
            ];

            $this->send_response($this->callback_url, $payload);

            return $update;
        } catch (QueryException $e) {
            Log::debug("Core::Class ~> " . __CLASS__ . " :: @method ~> (" . __FUNCTION__ . ") :: @line ~> #" . __LINE__ . " " . $e->getMessage());
            throw new Exception($e);
        }
    }
}
