<?php

namespace App\Http\Controllers;

use App\Http\Middleware\TokenAuthentication;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;

class AssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(TokenAuthentication::class);
    }

    public function index(Request $request){
        $team = $request->user()->team;
        $runners = $team->runners;
        return DB::table('runner_stage')->whereIn('runner_id', $runners->pluck('id')->toArray())->get();
    }

    public function store(Request $request)
    {
        $assignments = collect($request->get('routeAssignments'));
        if($assignments->count() > 0) DB::table('runner_stage')->delete();
        foreach ($assignments as $assignment){
            DB::table('runner_stage')->insert(["stage_id" => $assignment['routeId'], 'runner_id' => $assignment['runnerId']]);
        }
        return $assignments;
    }
}
