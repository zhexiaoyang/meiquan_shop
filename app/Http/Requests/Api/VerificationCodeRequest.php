<?php

namespace App\Http\Requests\Api;

class VerificationCodeRequest extends FormRequest
{

    public function rules()
    {
        return [
            'phone' => [
                'required',
                'regex:/^1[3456789]\d{9}$/',
            ],
            'captcha_key' => 'required|string',
            'captcha' => 'required|string',
        ];
        
    }

    public function attributes()
    {
        return [
            'captcha_key' => '图片验证码 key',
            'captcha' => '图片验证码',
        ];
    }
}
