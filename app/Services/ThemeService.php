<?php

namespace App\Services;
use App\Models\SystemSetting;

class ThemeService
{
    public static function getThemeColors()
    {
   return [
        'emerald' => [
            'primary-100' => '209, 250, 229',
            'primary-500' => '16, 185, 129',
            'primary-600' => '5, 150, 105',
        ],
        'blue' => [
            'primary-100' => '219, 234, 254',
            'primary-500' => '59, 130, 246',
            'primary-600' => '37, 99, 235',
        ],

        'brown' => [
            'primary-100' => '145, 79, 30',
            'primary-500' => '145, 79, 30',
            'primary-600' => '145, 79, 30',
        ],
        'seagreen' => [
            'primary-100' => '212, 237, 225', 
            'primary-500' => '46, 139, 87',  
            'primary-600' => '38, 119, 74', 
        ],

        'mediumblue' => [
            'primary-100' => '128, 179, 255',
            'primary-500' => '128, 179, 255',
            'primary-600' => '128, 179, 255',
        ],
        'goldenorange' => [
            'primary-100' => '255, 187, 92',
            'primary-500' => '255, 187, 92',
            'primary-600' => '255, 187, 92',
        ],
        'softgreen' => [
            'primary-100' => '147, 177, 166',
            'primary-500' => '147, 177, 166',
            'primary-600' => '147, 177, 166',
        ],
        'turquoise' => [
            'primary-100' => '34, 166, 153',
            'primary-500' => '34, 166, 153',
            'primary-600' => '34, 166, 153',
        ],
        'steelblue' => [
            'primary-100' => '70, 130, 169',
            'primary-500' => '70, 130, 169',
            'primary-600' => '70, 130, 169',
        ],

        'charcoal' => [
            'primary-100' => '220, 220, 220',
            'primary-500' => '80, 80, 80',
            'primary-600' => '50, 50, 50',
        ],

        'peachorange' => [
            'primary-100' => '243, 162, 109',
            'primary-500' => '243, 162, 109',
            'primary-600' => '243, 162, 109',
        ],


        'sandstone' => [
            'primary-100' => '233, 229, 219',  
            'primary-500' => '160, 147, 125',  
            'primary-600' => '130, 118, 100', 
        ],
        'steelgray' => [
            'primary-100' => '222, 224, 228',
            'primary-500' => '104, 109, 118',
            'primary-600' => '83, 87, 94',
        ],
        'navydeep' => [
            'primary-100' => '217, 223, 238',
            'primary-500' => '1, 32, 78',   
            'primary-600' => '0, 24, 60',
        ],
        'olivegreen' => [
            'primary-100' => '237, 241, 223',
            'primary-500' => '181, 193, 142',  
            'primary-600' => '151, 161, 119',
        ],

        'forestgreen' => [
            'primary-100' => '218, 232, 223',   
            'primary-500' => '26, 77, 46',     
            'primary-600' => '20, 61, 37',      
        ],
        'darkbrown' => [
            'primary-100' => '230, 219, 215',
            'primary-500' => '72, 30, 20',     
            'primary-600' => '56, 24, 16',
        ],

    ];
    }

    public static function getAvailableThemes()
    {
        return array_keys(self::getThemeColors());
    }

    public static function getCurrentThemeColors($themeName)
    {
        $colors = self::getThemeColors();
        return $colors[$themeName] ?? $colors['emerald'];
    }


public static function getSystemTheme()
{
    try {
        $setting = SystemSetting::first();
        if ($setting && in_array($setting->theme_color, self::getAvailableThemes())) {
            return $setting->theme_color;
        }
        return 'emerald'; // القيمة الافتراضية
    } catch (\Exception $e) {
        Log::error('Error getting system theme: ' . $e->getMessage());
        return 'emerald';
    }
}
}