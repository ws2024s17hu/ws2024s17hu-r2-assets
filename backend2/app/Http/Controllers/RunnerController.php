<?php

namespace App\Http\Controllers;

use App\Http\Middleware\TokenAuthentication;
use App\Models\Runner;
use App\Models\Stage;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class RunnerController extends Controller
{
    public function __construct()
    {
        $this->middleware(TokenAuthentication::class)->only('destroy', 'store', 'update');
    }

    public function index(Team $team)
    {
        return $team->runners()->get();
    }

    public function show(Team $team, Runner $runner)
    {
        $this->validateTeamMembership($runner, $team);

        return $runner->load('stages');
    }

    public function update(Team $team, Runner $runner, Request $request){
        $this->validateTeamAdmin($request, $team);
        $valid = $this->validate($request, ['firstName' => 'required|string', 'lastName' => 'required|string', 'speed' => ['required', 'string', 'regex:/\d\d:\d\d/']]);
        $this->validateTeamMembership($runner, $team);
        $runner->update($valid);
        $this->reassignStages($team);
        return $runner->refresh()->load('stages');
    }

    public function store(Team $team, Request $request)
    {
        $this->validateTeamAdmin($request, $team);
        $valid = $this->validate($request, ['firstName' => 'required|string', 'lastName' => 'required|string', 'speed' => ['required', 'string', 'regex:/\d\d:\d\d/']]);
        if($team->runners()->count() >= Team::TEAM_SIZE_LIMIT){
            throw new BadRequestException("The team is full");
        }
        $runner = $team->runners()->create(array_merge($valid, ['teamId' => $team->id, 'token' => rand(100000000, 999999999)]));
        $this->reassignStages($team);
        return $runner->load('stages');
    }

    public function destroy(Team $team, Runner $runner, Request $request){
        $this->validateTeamAdmin($request, $team);
        $this->validateTeamMembership($runner, $team);
        $runner->delete();
        $this->reassignStages($team);
        return ["success" => true];
    }

    /**
     * @param Runner $runner
     * @param Team $team
     * @return void
     */
    public function validateTeamMembership(Runner $runner, Team $team): void
    {
        if ($runner->teamId !== $team->id) {
            throw new BadRequestException("The runner with id {$runner->id} is not a member of this team");
        }
    }

    public function reassignStages(Team $team)
    {
        $stages = Stage::all()->pluck('id');
        $runners = $team->runners;
        if($runners->count() === 0) return;
        $stageForRunner = $stages->count() / $runners->count();
        foreach ($runners as $runner){
            $randomStages = $stages->random($stageForRunner);
            $stages = $stages->filter(function ($stage) use ($randomStages){
                return !$randomStages->contains($stage);
            });
            $runner->stages()->sync($randomStages);
        }
        $runners[0]->stages()->attach($stages);
    }

    private function validateTeamAdmin(Request $request, Team $team)
    {
        if(!$request->user()->isAdmin && $request->user()->teamId === $team->id){
            throw new UnauthorizedException("You are not an admin of this team");
        }
    }
}
