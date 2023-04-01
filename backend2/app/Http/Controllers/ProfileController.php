<?php

namespace App\Http\Controllers;

use App\Http\Middleware\TokenAuthentication;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware(TokenAuthentication::class);
    }

    public function __invoke(Request $request){
        return $request->user()->load('team');
    }
}
