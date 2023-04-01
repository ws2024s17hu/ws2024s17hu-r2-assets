<?php

namespace App\Http\Controllers;

use App\Http\Middleware\TokenAuthentication;
use App\Models\Runner;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function __invoke(Request $request){
        $this->validate($request, ["token" => "required|string"]);
        $user = Runner::where('token', $request->token)->first();
        if($user){
            return ["status" => "success", "user" => $user];
        }
        return response(["status" => "error", "message" => "Login failed"], 401);
    }
}
