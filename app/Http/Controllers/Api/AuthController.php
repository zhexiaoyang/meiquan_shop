<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Traits\PassportToken;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use PassportToken;

    public function __construct()
    {
        $this->middleware('auth:api')->except(['login', 'register']);
    }

    public function login(Request $request)
    {
        // 是否手机号验证码登录
        if (isset($request->verification_code)) {
            return $this->loginByCode($request);
        }


        // 验证表单提交
        $validator = Validator::make($request->all(), [
            'phone'    => 'required|exists:users',
            'password' => 'required|between:6,20',
        ]);

        if ($validator->fails()) {

            $error_message = [];
            if (!empty($validator->errors()->toArray())) {
                foreach ($validator->errors()->toArray() as $k => $v) {
                    $error_message = $v[0];
                }
            }
            return $this->error($error_message, 422);
        }

        try {
            $token = app(Client::class)->post(url('/oauth/token'), [
                'form_params' => [
                    'grant_type' => 'password',
                    'client_id' => config('passport.users.client_id'),
                    'client_secret' => config('passport.users.client_secret'),
                    'username' => $request->get('phone'),
                    'password' => $request->get('password'),
                    "provider" => 'users',
                    'scope' => 'user'
                ],
            ]);

            $data = json_decode($token->getBody(), true);

            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error("用户名或密码错误", 400);
        }
    }

    public function loginByCode(Request $request)
    {
        // 判断表单提交
        $validator = Validator::make($request->all(), [
            'phone'    => 'required|exists:users',
            'verification_key' => 'required|string',
            'verification_code' => 'required|string',
        ], [], [
            'verification_key' => '短信验证码 key',
            'verification_code' => '短信验证码',
        ]);

        if ($validator->fails()) {

            $error_message = [];
            if (!empty($validator->errors()->toArray())) {
                foreach ($validator->errors()->toArray() as $k => $v) {
                    $error_message = $v[0];
                }
            }
            return $this->error($error_message, 422);
        }

        // 验证验证码
        $verifyData = \Cache::get($request->verification_key);

        if (!$verifyData) {
            return $this->error('验证码已失效', 422);
        }

        if (!hash_equals($verifyData['phone'], $request->phone)) {
            // 返回401
            return $this->error('登录手机与验证码手机不一致', 422);
        }

        if (!hash_equals($verifyData['code'], $request->verification_code)) {
            // 返回401
            return $this->error('验证码错误', 422);
        }

        if ($user = User::query()->where('phone', $request->phone)->first() ) {
            return $this->success($this->getBearerTokenByUser($user, 2, false));
        // } else {
        //     $user = User::query()->create(['phone' => $request->phone, 'name' => $request->phone]);
        //     return $this->success($this->getBearerTokenByUser($user, 2, false));
        }

        return $this->error("用户不存在，请注册");
    }


    public function register(Request $request, User $user)
    {
        $phone = $request->get('phone');
        $password = $request->get('password');
        $verifyCode = (string)$request->get('verifyCode');

        if (User::query()->where('phone', $phone)->first()) {
            return $this->message('手机号已存在，请登录');
        }

        $verifyData = \Cache::get($phone);

        if (!$verifyData) {
            return $this->error('验证码已失效');
        }

        if (!hash_equals($verifyData['code'], $verifyCode)) {
            return $this->error('验证码失效');
        }

        $user->name = $request->get('phone');
        $user->phone = $request->get('phone');
        $user->password = bcrypt($password);
        $user->save();

        $user->assignRole('shop');

        if ($user) {
            return $this->message("注册成功，请登录");
        } else {
            return $this->error("注册失败，稍后再试", 500);
        }
    }

    public function user(Request $request)
    {
        return $this->success($request->user());
    }

    public function me(Request $request)
    {
        return $this->success($request->user());
    }

    public function logout()
    {
        if (Auth::guard('api')->check()) {
            Auth::guard('api')->user()->token()->delete();
        }

        return response()->json(['message' => '登出成功', 'status_code' => 200, 'data' => null]);
    }

    /**
     * 修改密码
     * @param Request $request
     * @return mixed
     */
    public function resetPassword(Request $request)
    {
        $isCheck = Hash::check($request->get('old_password'), auth()->user()->password);

        if (!$isCheck) {
            return $this->error('旧密码错误');
        }

        $request->validate([
            'old_password' => 'required',
            'password' => 'required|confirmed',
        ], [], [
            'old_password' => '旧密码',
        ]);

        auth()->user()->update([
            'password' => bcrypt($request->get('password')),
        ]);

        return $this->success();
    }
}
