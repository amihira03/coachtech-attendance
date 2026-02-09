<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AdminStaffController extends Controller
{
    public function showList(): View
    {
        return view('admin.staff.list');
    }
}
