<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        // 管理者ログイン画面からのログインなら管理画面へ
        if ($request instanceof Request && $request->boolean('admin_login')) {
            return redirect()->route('admin.attendance.list');
        }

        // 一般ログインは従来どおり
        return redirect('/attendance');
    }
}
