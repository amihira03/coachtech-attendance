<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        // 管理者レイアウトのログアウトなら管理者ログインへ
        if ($request instanceof Request && $request->boolean('admin_logout')) {
            return redirect()->route('admin.login');
        }

        // それ以外（一般ユーザー等）は通常ログインへ
        return redirect()->route('login');
    }
}
