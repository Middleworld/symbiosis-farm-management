<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSignature extends Model
{
    protected $fillable = [
        'name',
        'content',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the default signature
     */
    public static function getDefault()
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }

    /**
     * Get all active signatures
     */
    public static function getActive()
    {
        return static::where('is_active', true)->orderBy('name')->get();
    }
}
