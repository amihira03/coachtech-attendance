<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request instanceof Request && $request->boolean('admin_login')) {
            return redirect()->route('admin.attendance.list');
        }

        return redirect('/attendance');
    }
}
