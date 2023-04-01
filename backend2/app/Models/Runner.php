<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Runner extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $hidden = ["created_at", "updated_at"];

    protected $guarded = [];

    protected $casts = [
        "isAdmin" => "boolean"
    ];

    public function team(){
        return $this->belongsTo(Team::class, 'teamId');
    }

    public function getSpeedAttribute(){
        return Str::substr($this->attributes['speed'], 0, 5);
    }

    public function stages(){
        return $this->belongsToMany(Stage::class);
    }
}
