<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ThemeService;

class ThemeController extends Controller
{
    public function updateTheme(Request $request)
    {
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
        
        $user = Auth::user();
        
        // التحقق من صلاحية أدمن الوكالة
        if (!$user->hasRole('agency-admin') && !$user->hasRole('super-admin')) {
            return response()->json(['error' => 'غير مصرح لك بتغيير الثيم'], 403);
        }
        
        if (!$user->agency) {
            return response()->json(['error' => 'User has no agency'], 400);
        }
        
        $user->agency->update(['theme_color' => $validated['theme_color']]);
        
        return response()->json(['success' => true]);
    }
}