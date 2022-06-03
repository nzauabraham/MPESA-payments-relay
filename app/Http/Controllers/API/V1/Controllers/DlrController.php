<?php

namespace App\Http\Controllers\API\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OutgoingMessage;
use App\Services\Utils\Constants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DlrController extends Controller
{
    public function update_dlr(Request $request){
        Log::debug("[x] Incoming sms DLR as ....".$request->getContent());
        $request = $request->getContent();
        $requestData = json_decode($request, true);
        $msisdn = $requestData['recipient'];
        $status = $requestData['status'];
        $messageId = $requestData['messageId'];
        //update DLR status on DB
        $this->deliveryStatus($msisdn, $messageId, $status);
        return Constants::formatResponse("", Response::HTTP_OK);
    }

    private function deliveryStatus($msisdn, $messageId, $status)
    {
        $outbox = OutgoingMessage::find($messageId);
        if(empty($outbox)){
            Log::debug("Outbox not found with OutboxID::$messageId");
            return false;
        }
        $outbox->status = $status;
        $outbox->save();
        return $outbox->outbox_id;
    }
}
