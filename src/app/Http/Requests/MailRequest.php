<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MailRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required|email',
            'onetime_token' => 'required|digits:4',
        ];
    }

    public function messages()
    {
        return [
            'onetime_token.required' => '認証コードを入力してください',
            'onetime_token.digits' => '4桁の数字で入力してください'
        ];

    }
}

