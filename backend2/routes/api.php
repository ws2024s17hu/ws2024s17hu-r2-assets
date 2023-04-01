<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function (){
    Route::delete('/resetDb', function (){
        \Illuminate\Support\Facades\Artisan::call("migrate:fresh --seed");
        return ["message" => "OK"];
    });
    Route::delete('/resetTimes', function (){
        \App\Models\Team::where("id", ">", "0")->update(["startingTime" => null]);
        DB::table("runner_stage")->update(["handoverTime" => null]);
        return ["message" => "OK"];
    });
    Route::get('/stages', [\App\Http\Controllers\StageController::class, 'index']);
    Route::get('/currentRunner', \App\Http\Controllers\CurrentRunnerController::class);
    Route::get('/nextRun', \App\Http\Controllers\NextRunController::class);
    Route::post('/handover/start', [\App\Http\Controllers\HandoverController::class, "start"]);
    Route::post('/handover/finish', [\App\Http\Controllers\HandoverController::class, "finish"]);
    Route::get('/me', ProfileController::class);
    Route::post('/login', LoginController::class);
    Route::get('/assignments', [AssignmentController::class, 'index']);
    Route::post('/assignments', [AssignmentController::class, 'store']);
    Route::apiResource('/teams', \App\Http\Controllers\TeamController::class)->only(['index', 'show', 'update', 'destroy']);
    Route::apiResource('/teams/{team}/runners', \App\Http\Controllers\RunnerController::class);
});
