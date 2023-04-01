<?php

namespace App\Http\Controllers;

use App\Http\Middleware\TokenAuthentication;
use App\Models\Runner;
use App\Models\Stage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HandoverController extends Controller
{
    public function __construct()
    {
        $this->middleware(TokenAuthentication::class);
    }

    public function start(Request $request)
    {
        $time = Carbon::parse($request->time) ?? Carbon::now();
        $stageId = $request->stageId;
        $team = $request->user()->team;
        if (!$stageId) {
            // Already started
            return response(["success" => false, 'message' => "Stage id required", "description" => "You need to send the id of the stage you try to hand over."], 403);
        }
        $stage = DB::table("runner_stage")->where('stage_id', $stageId)->where('runner_id', $request->user()->id)->first();
        if(!$stage){
            // Not assigned to user
            return response(["success" => false, 'message' => 'This is not your stage', 'description' => 'You need to send your next stage id in the handover request']);
        }
        if ($stageId === 1 && $team->startingTime) {
            // Already started
            return response(["success" => false, 'message' => "The race already started", "description" => "You need to send the id of the stage you try to hand over, otherwise it will try to start the race."], 403);
        }
        if($stageId === 1){
            // Start race
            $team->update(["startingTime" => $time]);
            return response(["success" => true, 'message' => 'Race started']);
        }
        $lastStage = $this->getCompletedStages($team)[0];
        if($stageId === $lastStage->stage_id + 1){
            // Not the next stage
            return response(["success" => false, 'message' => 'Already active', 'description' => 'The stage already have been started']);
        }
        if($stageId !== $lastStage->stage_id + 2){
            // Not the next stage
            return response(["success" => false, 'message' => 'Not active yet', 'description' => 'You cannot start this stage, as this is not the next upcoming stage.']);
        }
        DB::table('runner_stage')->where('stage_id', $lastStage->stage_id+1)->whereIn('runner_id', $this->getRunners($team))->update(['handoverTime' => $time]);
        return ["success" => true, 'message' => 'Handover successful'];
    }

    public function finish(Request $request)
    {
        $time = Carbon::parse($request->time) ?? Carbon::now();
        $stageId = $request->stageId;
        $team = $request->user()->team;
        if (!$stageId) {
            // Already started
            return response(["success" => false, 'message' => "Stage id required", "description" => "You need to send the id of the stage you try to hand over."], 403);
        }
        $stage = DB::table("runner_stage")->where('stage_id', $stageId)->where('runner_id', $request->user()->id)->first();
        if(!$stage){
            // Not assigned to user
            return response(["success" => false, 'message' => 'This is not your stage', 'description' => 'You need to send your next stage id in the handover request']);
        }
        $completed = $this->getCompletedStages($team);
        $lastStageId = 0;
        if($completed->count() > 0){
            $lastStageId = $completed[0]->stage_id;
        }
        if($stageId !== $lastStageId + 1){
            // Not the next stage
            return response(["success" => false, 'message' => 'Not active yet', 'description' => 'You cannot finish this stage, as this is not active.']);
        }

        DB::table('runner_stage')->where('stage_id', $stageId)->where('runner_id', $request->user()->id)->update(['handoverTime' => $time]);
        return ["success" => true, 'message' => 'Handover successful'];
    }

    /**
     * @param $team
     * @return \Illuminate\Support\Collection
     */
    public function getCompletedStages($team): \Illuminate\Support\Collection
    {
        $runners = $this->getRunners($team);
        return DB::table("runner_stage")->whereIn('runner_id', $runners)->orderByDesc('stage_id')->whereNotNull('handoverTime')->get();
    }

    /**
     * @param $team
     * @return mixed
     */
    public function getRunners($team)
    {
        return $team->runners()->pluck('id')->toArray();
    }
}
