<?php
namespace App\Http\Traits;

use App\Services\Utils\Constants;

Trait ResponseTrait{
    public function send_response($url, $data){
        $headers = array(
            "Content-Type: application/json"
        );
        $data = json_encode($data, 1);
        Constants::initiateCurlPostRequest($url, $headers, $data);
    }
}
