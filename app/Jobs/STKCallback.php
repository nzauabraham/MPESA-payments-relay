<?php

namespace App\Jobs;

use App\Http\Traits\ResponseTrait;
use App\Models\Channel;
use App\Services\Utils\Constants;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class STKCallback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ResponseTrait;

    private mixed $request;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($request)
    {
        $this->request = json_decode($request, true);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $request = $this->request;

        Log::debug(__CLASS__ . " :: @method ~> (" . __FUNCTION__ . ") :: @line ~> #" . __LINE__ . " Callback array " . json_encode($request, true));

        $a = json_encode($request, true);

        if (isset($a)) {
            $request_b = json_decode($request, true);
            $responseCode = $request_b['Body']["stkCallback"]["ResultCode"];

            Log::debug(__CLASS__ . " :: @method ~> (" . __FUNCTION__ . ") :: @line ~> #" . __LINE__ . " Callback array processing " . json_encode($request_b, true));

            //If the user did not cancel the transaction setting
            if (isset($request_b['Body']["stkCallback"]["CallbackMetadata"])) {
                $callbackMetadata = $request_b['Body']["stkCallback"]["CallbackMetadata"];
                $amount = $callbackMetadata["Item"][0]["Value"];
                $reference = $callbackMetadata["Item"][1]["Value"];
                if (isset($callbackMetadata["Item"][4]["Value"])) {
                    $msisdn = $callbackMetadata["Item"][4]["Value"];
                } else {
                    $msisdn = $callbackMetadata["Item"][3]["Value"];
                }
                $transaction_id = strval($request_b['Body']["stkCallback"]["CheckoutRequestID"]);
                $success = "1";
                $reason = $request_b['Body']["stkCallback"]["ResultDesc"];

                Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @msisdn ~> (" . $msisdn . ") :: @line ~> #"
                    . __LINE__ . " Subscriber MPESA CALLBACK VALIDATED:: " . $transaction_id . " msisdn " . $msisdn . " "
                    . $reference . " amount " . $amount);
            } else {
                //If the user cancelled or entered a wrong PIN
                $request_b = json_decode($request, true);
                Log::debug(__CLASS__ . " :: @method ~> (" . __FUNCTION__ . ") :: @line ~> #"
                    . __LINE__ . "  failed to decode ::>> " . gettype($request_b) . json_encode($request_b, true));
                $amount = "";
                $reference = "";
                $msisdn = "";
                $transaction_id = $request_b['Body']["stkCallback"]["CheckoutRequestID"];
                $success = "0";
                $reason = $request_b['Body']["stkCallback"]["ResultDesc"];

                $this->updatePayment($responseCode, $msisdn, $reference, $transaction_id, $amount,
                    $request_b, $success, $reason);

                Log::debug("Core::Class ~> " . __CLASS__ . " :: @method ~> (" . __FUNCTION__ . ") :: @line ~> #" . __LINE__ . 'An error occurred while processing the subscriber payment. Wrong callback data format ' . json_encode($request_b, true));
                return Constants::formatResponse('An error occurred while processing the subscriber payment. Wrong callback data format ' . json_encode($request_b, true), Response::HTTP_FORBIDDEN);
            }

            $request_b = json_decode($request, true);
            Log::debug(__CLASS__ . " :: @method ~> (" . __FUNCTION__ . ") :: @line ~> #"
                . __LINE__ . " Updating db record :: " . $transaction_id);

            $this->updatePayment($responseCode, $msisdn, $reference, $transaction_id, $amount,
                $request_b, $success, $reason);
        } else {
            //insert failed for some reason
            Log::debug(__CLASS__ . " :: @method ~> (" . __FUNCTION__ . ") :: @line ~> #"
                . __LINE__ . 'An error occurred while processing the subscriber payment json error');
            return Constants::formatResponse('An error occurred while processing the subscriber payment',
                Response::HTTP_FORBIDDEN);
        }
    }
    private function updatePayment($responseCode, $msisdn, $reference, $transaction_id, $amount, $request,
                                   $success, $reason = "Request Processed successfully")
    {

        Log::debug(__CLASS__ . " :: @method ~> (" . __FUNCTION__ . ") :: @line ~> #"
            . __LINE__ . " updatePayment :: " . $transaction_id);


        $update = StkRequest::where( 'transaction_id','=',$transaction_id)
            ->update(
                [
                    'mpesa_reference' =>  $reference,
                    'mpesa_callback'=> json_encode($request),
                    'success' =>  $success,
                    'reason' =>  $reason,
                    'updated_at' =>  Carbon::now()
                ]
            );
        Log::debug("[x] Update request deposit result::$update");

        $stk_request = StkRequest::where('transaction_id', $transaction_id)->get()->last();
        $channel = Channel::where('client_id', $stk_request->client_id)->where('shortcode', $stk_request->source)->where('status','active')->first();


        Log::debug(__CLASS__ . " :: @method ~> (" . __FUNCTION__ . ") :: @line ~> #"
            . __LINE__ . " updatePayment success:: " . $success);

        if ($success){
          $status_code = 200;
        }else{
            $status_code = 400;
        }

        $payload = [
            'mpesa_reference' =>  $reference,
            'mpesa_response'=> $request,
            'success' =>  $success,
            'reason' =>  $reason,
            'unique_id'=>$stk_request->unique_id,
            'call_back_time' =>  Carbon::now(),
            'status_code'=>$status_code
        ];

        $this->send_response($channel->final_callback_url, $payload);

        return true;
    }

}
