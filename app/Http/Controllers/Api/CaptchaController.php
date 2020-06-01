<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\CaptchaRequest;
use Gregwar\Captcha\CaptchaBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CaptchaController extends Controller
{
    public function show(CaptchaBuilder $captchaBuilder)
    {
        $key = 'captcha-'.Str::random(15);

        $captcha = $captchaBuilder->build();
        $expiredAt = now()->addMinutes(5);
        Cache::put($key, ['code' => $captcha->getPhrase()], $expiredAt);

        $result = [
            'captcha_key' => $key,
            'expired_at' => $expiredAt->toDateTimeString(),
            'captcha_image_content' => $captcha->inline()
        ];

        return $this->success($result);
    }
}
