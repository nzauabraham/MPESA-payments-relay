<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class MsisdnValidator implements Rule
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
        preg_match('/^(\+)?(254|255|0)?((7|1)\d{8})\s*/', $value, $matches);
        if (!empty($matches)) {
            $msisdn = "254" . $matches[3];
            $network = 'other';
            $safaricom_regex = array(
                '(^25471)[0-9]{7}','(^25470)[0-9]{7}','(^25472)[0-9]{7}','(^25479)[0-9]{7}','(^25474)[0-9]{7}','(^25475)[0-9]{7}','(^25476)[0-9]{7}','(^25411)[0-9]{7}'
            );

            foreach($safaricom_regex as $regexp){
                if(preg_match("/$regexp/i", $msisdn)){
                    $network = "safaricom";
                }
            }

            if($network === 'safaricom'){
                return true;
            }
        }
        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The provided phone number is invalid.';
    }
}
