<?php

namespace App\Http\Controllers\API\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Utils\Constants;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function auth_failed(){
        $response['message'] = 'Unauthorized!';
        return Constants::formatResponse($response, Response::HTTP_UNAUTHORIZED);
    }
}
