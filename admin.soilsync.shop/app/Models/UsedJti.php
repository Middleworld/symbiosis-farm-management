<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UsedJti extends Model
{
    protected $fillable = ['jti', 'expires_at'];

    protected $dates = ['expires_at'];

    /**
     * Check if a JTI has been used
     */
    public static function isUsed(string $jti): bool
    {
        return static::where('jti', $jti)->exists();
    }

    /**
     * Mark a JTI as used
     */
    public static function markUsed(string $jti, int $expiresAt): bool
    {
        try {
            static::create([
                'jti' => $jti,
                'expires_at' => Carbon::createFromTimestamp($expiresAt)
            ]);
            return true;
        } catch (\Exception $e) {
            // JTI already exists or other error
            return false;
        }
    }

    /**
     * Clean up expired JTIs
     */
    public static function cleanupExpired(): int
    {
        return static::where('expires_at', '<', now())->delete();
    }
}
