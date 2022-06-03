<?php

namespace App\Http\Requests;

use App\Rules\MsisdnValidator;
use App\Rules\PayBillValidator;
use Illuminate\Foundation\Http\FormRequest;

class StkRequestValidation extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'unique_id'=>'required',
            'amount'=>'required',
            'paybill'=>['required', new PayBillValidator],
            'msisdn'=>['required', new MsisdnValidator],
            'account_no'=>'required'
        ];
    }

    public function messages()
    {
        return [
            'unique_id.required' => "Kindly provide a unique if for the request!",
            'amount.required' => "Kindly provide an amount!",
            'paybill.required' => "Kindly provide a supported Paybill!",
            'msisdn.required' => "Kindly provide a valid Safaricom phone number!",
            'account_no.required' => "Kindly provide an account number for the STK Request!",
        ];
    }
}
