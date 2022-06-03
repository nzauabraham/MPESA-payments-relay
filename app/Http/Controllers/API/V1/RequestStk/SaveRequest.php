<?php
namespace App\Http\Controllers\API\V1\RequestStk;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaveRequest extends Controller{
    public function store($request){


        $insert = DB::table('stk_requests')->insert(
            [
                'msisdn' => $request['msisdn'],
                'amount' => $request['amount'],
                'charge' => $request['charge'],
                'unique_id' => $request['unique_id'],
                'client_id' => $request['client_id'],
                'channel_id' => $request['channel_id'],
                'source' => $request['source'],
                "created_at" => \Carbon\Carbon::now()
            ]
        );
        Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @line ~> #" . __LINE__ . " registerDepositRequest insert ~> " . $insert);

    }
}
