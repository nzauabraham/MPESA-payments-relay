<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use App\Services\Utils\Constants;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsExists {
    public function handle(Request $request, Closure $next){
        if(isset($request->email)){
            $client = Customer::where('email', $request->email)->first();
            if($client){
                return $next($request);
            }
        }

        return Constants::formatResponse("Invalid request!", Response::HTTP_FORBIDDEN);
    }
}
