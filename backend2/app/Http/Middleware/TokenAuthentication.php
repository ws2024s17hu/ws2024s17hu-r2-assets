<?php

namespace App\Http\Middleware;

use App\Models\Runner;
use Closure;
use http\Client\Curl\User;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class TokenAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = str_replace('Bearer ', '', $request->header('authorization'));
        $user = Runner::where('token', $token)->first();
        if(!$token){
            return response(["message" => "Token required"], 401);
        }
        if(!$user){
            return response(["message" => "Token not found"], 401);
        }
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        return $next($request);
    }
}
