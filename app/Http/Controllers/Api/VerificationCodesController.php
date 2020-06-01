<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\VerificationCodeRequest;
use Illuminate\Support\Str;
use Overtrue\EasySms\EasySms;

class VerificationCodesController extends Controller
{
    public function store(VerificationCodeRequest $request, EasySms $easySms)
    {

        $captchaData = \Cache::get($request->captcha_key);

        if (!$captchaData) {
            return $this->error('图片验证码已失效', 422);
        }

        if (!hash_equals(strtolower($captchaData['code']), strtolower($request->captcha))) {
            // 验证错误就清除缓存
            // \Cache::forget($request->captcha_key);
            return $this->error('验证码错误', 422);
        }

        $phone = $request->phone;

        // 生成4位随机数，左侧补0
        $code = str_pad(random_int(1, 9999), 4, 0, STR_PAD_LEFT);

        try {
            $result = $easySms->send($phone, [
                'content'  =>  "您的验证码是{$code}。如非本人操作，请忽略本短信"
            ]);
        } catch (\Overtrue\EasySms\Exceptions\NoGatewayAvailableException $exception) {
            $message = $exception->getException('qcloud')->getMessage();
            return $this->error($message ?: '短信发送异常', 105);
        }

        $key = 'verificationCode_'.Str::random(15);
        $expiredAt = now()->addMinutes(10);
        // 缓存验证码 10分钟过期。
        \Cache::put($key, ['phone' => $phone, 'code' => $code], $expiredAt);

        return $this->success([
            'key' => $key,
            'expired_at' => $expiredAt->toDateTimeString(),
        ]);
    }
}
