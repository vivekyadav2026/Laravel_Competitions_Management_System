<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LudoSetting extends Model
{
    use HasFactory;

    protected $table = 'ludo_settings';

    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if ($setting && !is_null($setting->value) && $setting->value !== '') {
            return self::normalizeBrandText($setting->value);
        }

        // Aliases mapping for backward and cross-module compatibility
        $aliases = [
            'announcement_hi' => 'announcement_hindi',
            'announcement_hindi' => 'announcement_hi',
            'referral_commission' => 'commission_percentage',
            'commission_percentage' => 'referral_commission',
        ];

        if (isset($aliases[$key])) {
            $aliasSetting = self::where('key', $aliases[$key])->first();
            if ($aliasSetting && !is_null($aliasSetting->value) && $aliasSetting->value !== '') {
                return self::normalizeBrandText($aliasSetting->value);
            }
        }

        return is_string($default) ? self::normalizeBrandText($default) : $default;
    }

    /**
     * Ensure legacy brand names in stored settings are replaced with Fsaludoclub
     */
    protected static function normalizeBrandText($value)
    {
        if (!is_string($value)) {
            return $value;
        }
        $brand = config('ludo.brand_name', 'Fsaludoclub');
        return str_ireplace(
            ['Royal Ludo', 'Malik Ludo', 'रॉयल लूडो', 'मलिक लूडो'],
            [$brand, $brand, $brand, $brand],
            $value
        );
    }

    public static function set(string $key, $value, string $group = 'general'): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        $aliases = [
            'announcement_hi' => 'announcement_hindi',
            'announcement_hindi' => 'announcement_hi',
            'referral_commission' => 'commission_percentage',
            'commission_percentage' => 'referral_commission',
        ];

        if (isset($aliases[$key])) {
            self::updateOrCreate(
                ['key' => $aliases[$key]],
                ['value' => $value, 'group' => $group]
            );
        }

        return $setting;
    }
}
