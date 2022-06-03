<?php

namespace App\Http\Controllers\API\V1\Controllers;

use App\Http\Controllers\API\V1\RequestStk\SaveRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\StkRequestValidation;
use App\Jobs\STKRequest;
use App\Models\Channel;
use App\Models\Customer;
use App\Models\ShortCodeConfig;
use App\Services\Utils\Constants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class StkRequestController extends Controller
{
    public function triggerStk(StkRequestValidation $request){
        $request = $request->validated();

        $user = Auth::user();
        $client = Customer::where('user_id', $user->id)->first();

        $short_code = $request['paybill'];
        $channel = Channel::where('client_id', $client->id)->where('shortcode', $short_code)->where('status','active')->first();
        $amount = $request['amount'];
        $msisdn = $request['msisdn'];
        $unique_id = $request['unique_id'];
        $ref_no = $request['account_no'];

        $shortcode_records = ShortCodeConfig::where('shortcode', $short_code)->where('client_id', $client->id)->where('status','active')->first();

        if($shortcode_records->charge_type == 'percentage'){
            $charge = bcdiv(($shortcode_records->charge / 100) * $amount, 1, 2);
        }elseif($shortcode_records->charge_type == 'value'){
            $charge = $shortcode_records->charge;
        }else{
            $charge = 0;
        }

        $request['charge'] = $charge;
        $request['client_id'] = $client->id;
        $request['channel_id'] = $channel->id;
        $request['source'] = $short_code;

        $request_storer = new SaveRequest();
        $request_storer->store($request);

        $final_callback_url = $channel->final_callback_url;
        $callback_url = $channel->callback_url;

        $config_query = ShortCodeConfig::where('client_id', $client->id)->where('shortcode',$short_code)->where('status','active')->first();

        $configs = [
            'key'=>decrypt($config_query->key),
            'secret'=>decrypt($config_query->secret),
            'passkey'=>decrypt($config_query->passkey),
            'type'=>$config_query->type,
            'parent_till'=>$config_query->parent_till
        ];

        $stkRequest = new STKRequest($amount, $msisdn, $short_code, $unique_id, $ref_no, $callback_url, $final_callback_url, $configs);
        if (dispatch($stkRequest->onQueue("RELAY_STK_REQUEST"))) {
            Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @msisdn ~> (" . $msisdn . ") :: @line ~> #" . __LINE__ . "STK Push Request queued successfully " . $amount . " - " . $msisdn . " - " . config('app.PB') . " - " . $ref_no);
            return Constants::formatResponse( "Received for Processing", Response::HTTP_OK);
        } else {
            Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @msisdn ~> (" . $msisdn . ") :: @line ~> #" . __LINE__ . "STK Request Queuing Error:: " . $amount . $msisdn . config('wallet_config.PB') . $ref_no);
            return Constants::formatResponse( "We could not process your Request at this point, kindly Try Again", Response::HTTP_BAD_REQUEST);
        }
    }
}
