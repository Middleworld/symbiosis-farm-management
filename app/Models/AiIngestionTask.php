<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiIngestionTask extends Model
{
    protected $fillable = [
        'type','status','progress','params','error','created_by','started_at','finished_at'
    ];

    protected $casts = [
        'params' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime'
    ];
}
