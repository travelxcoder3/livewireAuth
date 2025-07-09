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
            'theme_color' => 'required|in:' . implode(',', ThemeService::getAvailableThemes())
        ]);
        
        $user = Auth::user();
        
        if (!$user->agency) {
            return response()->json(['error' => 'User has no agency'], 400);
        }
        
        $user->agency->update(['theme_color' => $validated['theme_color']]);
        
        return response()->json(['success' => true]);
    }
}