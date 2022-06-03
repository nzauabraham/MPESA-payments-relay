<?php

namespace App\Http\Controllers\API\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequestOtpValidation;
use App\Http\Traits\SendSmsTrait;
use App\Models\Customer;
use App\Models\OtpRequest;
use App\Services\Utils\Constants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestOtpController extends Controller
{
    use SendSmsTrait;

    /**
     * @throws \Exception
     */
    public function sendOtp(RequestOtpValidation $request): \Illuminate\Http\JsonResponse
    {
        Log::debug("[x] Received request for otp...");
        $request = $request->validated();
        $email_address = $request['email'];

        $client = Customer::where('email', $email_address)->first();
        $previous_requests = OtpRequest::where('client_id', $client->id)->where('status', 'unused')->get();

        if($previous_requests){
            foreach($previous_requests as $req){
                $created_at = $req->created_at;
                $time_diff = DB::select("SELECT TIMESTAMPDIFF(MINUTE,'$created_at',now()) diff");
                if($time_diff[0]->diff > 5){
                    $status = 'expired';
                }else{
                    $status = 'revoked';
                }
                OtpRequest::where('id', $req->id)
                    ->update([
                        'status'=>$status
                    ]);
            }
        }

        //Generate new Otp and send to registered No.
        $new_otp = Constants::uniqId();
        $client->otp = $new_otp;
        $client->save();

        //Record OTP request
        $data=[
            'client_id'=>$client->id,
            'otp'=>$new_otp,
        ];
        OtpRequest::create($data);

        $fname = $client->name ? strtok($client->name, " ") : "Customer";

        $sms = "Hello $fname\n"
        ."Your new OTP is: $new_otp\n"
        ."Kindly note it's validity is 5 mins";

        $this->send_sms($client->msisdn, $sms);
        return Constants::formatResponse("OTP Sent!", Response::HTTP_OK);
    }
}
