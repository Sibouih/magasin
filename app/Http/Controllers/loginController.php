<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;


class loginController extends Controller
{
    public function users(request $request) {
        $users = User::where('deleted_at', null)->get(['firstname', 'lastname', 'email']);
        return response()->json([
            'users' => $users,
        ]);
    }
}
