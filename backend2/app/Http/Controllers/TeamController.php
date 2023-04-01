<?php

namespace App\Http\Controllers;

use App\Http\Middleware\TokenAuthentication;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;

class TeamController extends Controller
{
    public function __construct()
    {
        $this->middleware(TokenAuthentication::class);
    }

    public function show(Team $team){
        return $team;
    }

    private function validateTeamAdmin(Request $request, Team $team)
    {
        if(!$request->user()->isAdmin && $request->user()->teamId === $team->id){
            throw new UnauthorizedException("You are not an admin of this team");
        }
    }

    public function update(Team $team, Request $request){
        $this->validateTeamAdmin($request, $team);
        $valid = $request->validate(["name" => "required|string", "contactEmail" => "required|string", "location" => "required|string"]);
        $team->update($valid);
        return $team;
    }

    public function destroy(Team $team, Request $request){
        $this->validateTeamAdmin($request, $team);
        $team->delete();
        return ["success" => true];
    }
}
