<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        if ($request instanceof Request && $request->boolean('admin_logout')) {
            return redirect()->route('admin.login');
        }

        return redirect()->route('login');
    }
}
