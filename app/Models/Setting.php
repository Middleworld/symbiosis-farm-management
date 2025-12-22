<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $table = 'admin_settings';
    
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        // Use cache to avoid database queries for frequently accessed settings
        $cacheKey = "admin_setting_{$key}";
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }
            
            return self::castValue($setting->value, $setting->type);
        });
    }
    
    /**
     * Set a setting value by key
     */
    public static function set(string $key, $value, string $type = 'string', string $description = null): bool
    {
        $stringValue = self::valueToStringPrivate($value, $type);
        
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'type' => $type,
                'description' => $description,
            ]
        );
        
        // Clear cache
        Cache::forget("admin_setting_{$key}");
        
        return (bool) $setting;
    }
    
    /**
     * Get all settings as an associative array
     */
    public static function getAll(): array
    {
        $settings = self::all();
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting->key] = self::castValue($setting->value, $setting->type);
        }
        
        return $result;
    }
    
    /**
     * Set multiple settings at once
     */
    public static function setMultiple(array $settings): bool
    {
        foreach ($settings as $key => $config) {
            if (is_array($config)) {
                $value = $config['value'];
                $type = $config['type'] ?? 'string';
                $description = $config['description'] ?? null;
            } else {
                $value = $config;
                $type = self::detectType($value);
                $description = null;
            }
            
            self::set($key, $value, $type, $description);
        }
        
        return true;
    }
    
    /**
     * Cast value from string to appropriate type
     */
    private static function castValue(string $value, string $type)
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            'array' => json_decode($value, true),
            default => $value,
        };
    }
    
    /**
     * Convert value to string for storage (public helper)
     */
    public static function valueToString($value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'json', 'array' => json_encode($value),
            default => (string) $value,
        };
    }
    
    /**
     * Convert value to string for storage (private implementation)
     */
    private static function valueToStringPrivate($value, string $type): string
    {
        return self::valueToString($value, $type);
    }
    
    /**
     * Auto-detect type from value
     */
    private static function detectType($value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value) => 'array',
            default => 'string',
        };
    }
}
