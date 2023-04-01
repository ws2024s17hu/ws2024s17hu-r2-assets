<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    const TEAM_SIZE_LIMIT = 10;
    protected $hidden = ["created_at", "updated_at"];

    protected $guarded = [];

    public function runners(){
        return $this->hasMany(Runner::class, 'teamId');
    }
}
