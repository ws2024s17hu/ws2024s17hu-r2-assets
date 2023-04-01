<?php

namespace App\Http\Controllers;

use App\Http\Middleware\TokenAuthentication;
use App\Models\Runner;
use App\Models\Stage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CurrentRunnerController extends Controller
{
    public function __construct()
    {
        $this->middleware(TokenAuthentication::class);
    }

    public function __invoke(Request $request)
    {
        $team = $request->user()->team;
        $completedStages = $this->getCompletedStages($team);
        $lastStage = $completedStages[0] ?? null;
        if (!$lastStage) {
            $nextStage = $this->getStage($team, 1);
            $runner = Runner::find($nextStage->runner_id);
            $stage = Stage::find($nextStage->stage_id);
            return ["runner" => $runner, "stage" => $stage, "scheduleDifference" => 0];
        }
        if ($lastStage->stage_id == Stage::count()) {
            return ["finished" => true];
        }
        $nextStage = $this->getStage($team, $lastStage->stage_id + 1);
        $runner = Runner::find($nextStage->runner_id);
        $stage = Stage::find($nextStage->stage_id);
        $scheduleDifference = $this->calculateScheduleDifference($team);
        return ["runner" => $runner, "stage" => $stage, "scheduleDifference" => $scheduleDifference];
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
            ->orderBy('runner_stage.id')
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

    private function getStage($team, $stageId)
    {
        $runners = $this->getRunners($team);
        return DB::table("runner_stage")->whereIn('runner_id', $runners)->where('stage_id', $stageId)->first();
    }
}
