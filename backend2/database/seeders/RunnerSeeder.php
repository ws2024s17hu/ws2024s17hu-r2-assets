<?php

namespace Database\Seeders;

use App\Http\Controllers\RunnerController;
use App\Models\Runner;
use App\Models\Team;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RunnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sql = file_get_contents(__DIR__.'/runners.sql');
        DB::unprepared($sql);
        Team::all()->each(function ($team){
            $controller = new RunnerController();
            $controller->reassignStages($team);
        });
    }
}
