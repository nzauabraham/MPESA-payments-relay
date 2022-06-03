<?php

namespace App\Http\Controllers\API\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\STKCallback;
use App\Services\Utils\Constants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class StkCallbackController extends Controller
{
    public function process_callback(Request $request){
        Log::debug("Stk Push CallBack endpoint ..." . $request->getContent());
        $request = $request->getContent();
        $response = json_encode($request);
        $stkCallback = new STKCallback($response);
        $body = json_decode($request, true);

        Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . "  :: @line ~> #"
            . __LINE__ . "Body:::" . $body['Body']["stkCallback"]["CheckoutRequestID"]);

        if (isset($body['Body']["stkCallback"]["CheckoutRequestID"])) {
            Log::debug("Subscriber MPESA CALLBACK:: " . $request);
            if (dispatch($stkCallback->onQueue("RELAY_STK_CALLBACK"))) {
                Log::debug("STK Callback Queuing Successful " . $request);
                return Constants::formatResponse("", Response::HTTP_OK);
            } else {
                Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @line ~> #" . __LINE__ . " STK Callback Queuing Error:: " . $request);
            }
        } else {
            Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @line ~> #" . __LINE__ . " STK Callback Queuing Error top:: " . $request);
            return Constants::formatResponse("", Response::HTTP_BAD_REQUEST);
        }
    }
}
