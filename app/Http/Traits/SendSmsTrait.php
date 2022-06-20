<?php
namespace App\Http\Traits;

use App\Models\OutgoingMessage;
use App\Models\User;
use App\Services\Emalify\src\API\Messaging;
use App\Services\Utils\Constants;
use Illuminate\Support\Facades\Log;

trait SendSmsTrait{
    /**
     * @throws \Exception
     */
    public function send_sms($msisdn, $text) {
        if($msisdn){
            $outbox_data = [
                'msisdn'=> $msisdn,
                'message'=>$text,
                'status'=> 'SENT',
            ];
            Log::debug("[x] outgoing message data as..".var_export($outbox_data, 1));

            $outbox = OutgoingMessage::create($outbox_data);

            $messaging =  new Messaging( Constants::$EMALI_CLIENT_ID,  Constants::$EMALI_CLIENT_SECRET, Constants::$PROJECT_ID);
            $params = [
                'from' => '<YOUR EMALIFY SENDER OR SHORTCODE>',
                'api' => 'v2',
                'messageId' => $outbox->id,
                'linkId' => null,
                'reference' => rand(0, 10) . rand(0, 10) . rand(0, 10),
                'callback' => config('app.url').'/api/sms/dlr'
            ];

            Log::debug(__FUNCTION__ . "::Class ~> " . __CLASS__ . " :: @line ~> #" . __LINE__ . "[x]Msisdn:$msisdn Going to send message......" . $text);
            $messaging->sendMessage($text, [$msisdn], $params);
        }else{
            Log::debug("[x] Response Trait msisdn not found");
        }

        return true;
    }

}
