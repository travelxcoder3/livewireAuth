<?php
namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use App\Services\ThemeService;
use Illuminate\Support\Facades\Log;

class SystemSettingsController extends Controller
{
    public function updateTheme(Request $request)
    {
        try {
            $validated = $request->validate([
                'theme_color' => [
                    'required',
                    function($attribute, $value, $fail) {
                        $themes = \App\Services\ThemeService::getAvailableThemes();
                        if (!in_array($value, $themes) && !preg_match('/^#?([A-Fa-f0-9]{6})$/', $value)) {
                            $fail('لون الثيم غير صالح');
                        }
                    }
                ]
            ]);
            
            $settings = SystemSetting::firstOrCreate();
            $settings->update(['theme_color' => $validated['theme_color']]);
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Error updating theme: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الثيم',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}