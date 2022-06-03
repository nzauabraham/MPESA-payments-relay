<?php

namespace App\Http\Controllers\API\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequestTokenValidation;
use App\Models\Customer;
use App\Models\OtpRequest;
use App\Models\User;
use App\Services\Utils\Constants;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RequestTokenController extends Controller
{
    public function fetchToken(RequestTokenValidation $request){
        $request = $request->validated();
        $email_address = $request['email'];
        $otp = $request['code'];

        $client = Customer::where('email', $email_address)->first();
        $is_valid_otp = DB::select("select id from otp_requests where otp = '$otp' and client_id =".$client->id
                    ." and status = 'unused' and TIMESTAMPDIFF(MINUTE, created_at, now()) < 5 limit 1");

        if(!$is_valid_otp){
            return Constants::formatResponse("Invalid Otp!", Response::HTTP_FORBIDDEN);
        }

        OtpRequest::where('id', $is_valid_otp[0]->id)
            ->update([
                'status'=>'used'
            ]);

        //Return token
        $user = User::find($client->user_id);
        $res = $user->createToken('access_token');

        $response['token'] = $res->plainTextToken;
        $response['message']= "Account verified successfully";

        return Constants::formatResponse($response, Response::HTTP_OK);

    }
}
