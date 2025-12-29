<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'conversation_id',
        'message',
        'type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
