<?php

namespace App\Rules;

use App\Models\Customer;
use App\Models\ShortCodeConfig;
use Illuminate\Contracts\Validation\Rule;

class PayBillValidator implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $is_supported = ShortCodeConfig::where('shortcode', $value)->where('status','active')->first();
        if(!$is_supported){
            return false;
        }

        $is_client_supported = Customer::where('id', $is_supported->client_id)->where('status','active')->first();
        if(!$is_client_supported){
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The paybill is not supported! Kindly contact Admin!';
    }
}
