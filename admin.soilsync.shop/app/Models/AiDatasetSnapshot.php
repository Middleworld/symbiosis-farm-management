<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiDatasetSnapshot extends Model
{
    protected $fillable = [
        'dataset','version','row_count','source_hash','storage_path','meta'
    ];

    protected $casts = [
        'meta' => 'array'
    ];
}
