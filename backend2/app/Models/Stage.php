<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    use HasFactory;
    public $timestamps = false;

    public function runners(){
        return $this->belongsToMany(Runner::class);
    }
}
