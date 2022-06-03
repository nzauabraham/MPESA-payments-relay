<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestTokenValidation extends FormRequest
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
            'email'=>'required',
            'code'=>'required'
        ];
    }

    public function messages()
    {
        return [
            'email.required' => "Kindly provide the email used to Onboard!",
            'code.required' => "Kindly provide the otp code sent!",
        ];
    }
}
