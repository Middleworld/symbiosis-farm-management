<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WordPressUserMeta extends Model
{
    protected $connection = 'wordpress';
    protected $table = 'usermeta'; // Will be prefixed with D6sPMX_ automatically
    protected $primaryKey = 'umeta_id';
    public $timestamps = false;
    
    protected $fillable = [
        'user_id',
        'meta_key',
        'meta_value'
    ];

    /**
     * Get the user that owns this meta
     */
    public function user()
    {
        return $this->belongsTo(WordPressUser::class, 'user_id', 'ID');
    }
}
