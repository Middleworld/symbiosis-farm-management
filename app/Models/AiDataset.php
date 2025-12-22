<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiDataset extends Model
{
    protected $fillable = [
        'dataset','current_version','last_refreshed_at','record_count'
    ];

    protected $casts = [
        'last_refreshed_at' => 'datetime'
    ];
}
