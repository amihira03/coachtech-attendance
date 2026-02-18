<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class AdminStaffController extends Controller
{
    public function showList(): View
    {
        $users = User::query()
            ->where('is_admin', false)
            ->orderBy('id')
            ->get();

        return view('admin.staff.list', compact('users'));
    }
}
