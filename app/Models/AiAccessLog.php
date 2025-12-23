<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiAccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','service','method','tier','duration_ms','result_count','params','client_ip'
    ];

    protected $casts = [
        'params' => 'array'
    ];
}
