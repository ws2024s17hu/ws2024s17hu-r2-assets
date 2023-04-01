<?php

namespace App\Http\Controllers;

use App\Http\Middleware\TokenAuthentication;
use App\Models\Runner;
use App\Models\Stage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NextRunController extends Controller
{
    public function __construct()
    {
        $this->middleware(TokenAuthentication::class);
    }

    public function __invoke(Request $request)
    {
        $team = $request->user()->team;
        $nextStage = DB::table('runner_stage')
            ->where('runner_id', $request->user()->id)
            ->whereNull('handoverTime')
            ->orderBy('id')
            ->first();
        if(!$nextStage){
            return ["finished" => true];
        }
        $previousRunner = $this->getStageRunner($team, $nextStage->stage_id - 1);
        $nextRunner = $this->getStageRunner($team, $nextStage->stage_id + 1);
        $stage = Stage::find($nextStage->stage_id);
        $completed = $this->getCompletedStages($team);
        $lastStageId = 0;
        $diff = 0;
        if($completed->count() > 0){
            $lastStageId = $completed[0]->stage_id;
            $diff = $this->calculateScheduleDifference($team);
        }
        return [
            "stage" => $stage,
            'previousRunner' => $previousRunner,
            'nextRunner' => $nextRunner,
            'canStart' => $previousRunner == null || $lastStageId + 2 === $stage->id,
            'plannedStartTime' => $this->getPlannedTimeForStage($team, $stage->id - 1)->addSeconds($diff),
            'plannedFinishTime' => $this->getPlannedTimeForStage($team, $stage->id)->addSeconds($diff),
        ];
    }

    private function calculateScheduleDifference($team)
    {
        $completedStages = $this->getCompletedStages($team);
        $plannedTime = $this->getPlannedTimeForStage($team, $completedStages[0]->stage_id);
        $lastStage = $this->getCompletedStages($team)[0];
        $actualTime = Carbon::parse($lastStage->handoverTime);
        return $plannedTime->diffInSeconds($actualTime, false);
    }

    private function getPlannedTimeForStage($team, $stageId)
    {
        $startingTime = $team->startingTime ?? $team->plannedStaringTime;
        $runners = $this->getRunners($team);
        $stages = DB::table("runner_stage")
            ->whereIn('runner_id', $runners)
            ->orderBy('stage_id')
            ->where('stage_id', '<=', $stageId)
            ->join('runners', 'runner_id', '=', 'runners.id')
            ->join('stages', 'stage_id', '=', 'stages.id')
            ->get();
        $time = Carbon::parse($startingTime);
        foreach ($stages as $stage) {
            [$minutes, $seconds] = explode(':', $stage->speed);
            $seconds += $minutes * 60;
            $seconds *= $stage->distance;
            $time->addSeconds($seconds);
        }
        return $time;
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

    private function getStageRunner($team, $stageId)
    {
        $runners = $this->getRunners($team);
        $runnerStage = DB::table("runner_stage")->whereIn('runner_id', $runners)->where('stage_id', $stageId)->first();
        if (!$runnerStage) return null;
        return Runner::find($runnerStage->runner_id);
    }
}
