<?php

namespace App\Http\Controllers\Admin;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth:api')->except(['login']);
    }

    public function login(Request $request)
    {
        try {
            $token = app(Client::class)->post(url('/oauth/token'), [
                'form_params' => [
                    'grant_type' => 'password',
                    'client_id' => config('passport.admins.client_id'),
                    'client_secret' => config('passport.admins.client_secret'),
                    'username' => $request->get('username'),
                    'password' => $request->get('password'),
                    "provider" => 'admins',
                    'scope' => 'admin'
                ],
            ]);

            $data = json_decode($token->getBody(), true);

            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error("用户名或密码错误", 400);
        }
    }

    public function user(Request $request)
    {
        return $this->success($request->user());
    }

    public function me(Request $request)
    {
        $role_name = $request->user()->getRoleNames()[0];

        $data = [];

        $role = Role::with(['permissions' => function($query) {
            $query->select('name', 'title', 'id', 'pid')->orderBy('pid', 'asc');
        }])->select('name', 'title', 'id')->where('name', $role_name)->first();

        if ($role) {
            $data['id'] = $role->name;
            $data['name'] = $role->title;
            $permissions = [];
            if (!empty($role->permissions)) {
                foreach ($role->permissions as $permission) {
                    if ($permission->pid ==0) {
                        $permissions[$permission->id]['roleId'] = $role->name;
                        $permissions[$permission->id]['permissionId'] = $permission->name;
                        $permissions[$permission->id]['permissionName'] = $permission->title;
                    } else {
                        if (isset($permissions[$permission->pid])) {
                            $tmp['action'] = $permission->name;
                            $tmp['describe'] = $permission->title;
                            $tmp['defaultCheck'] = true;
                            $permissions[$permission->pid]['actionEntitySet'][] = $tmp;
                        }
                    }
                }
                $data['permissions'] = array_values($permissions);
            }
        }

        $user = [
            'id' => $request->user()->phone ?? '',
            'name' => $request->user()->phone ?? '',
            'phone' => $request->user()->phone ?? '',
            'money' => $request->user()->money ?? '',
            'role' => $data,
        ];
        return $this->success($user);
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
