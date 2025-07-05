<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    // عرض جميع الصلاحيات
    public function index()
    {
        $user = auth()->user();
        // فقط أدمن الوكالة يدير الصلاحيات
        $permissions = Permission::where('agency_id', $user->agency_id)->get();
        return response()->json($permissions);
    }

    // إضافة صلاحية جديدة
    public function store(Request $request)
    {
        $user = auth()->user();
        
        // التحقق من وجود الصلاحية لنفس الوكالة
        $exists = Permission::where('name', $request->name)
            ->where('guard_name', 'web')
            ->where('agency_id', $user->agency_id)
            ->exists();
            
        if ($exists) {
            return response()->json(['message' => 'هذه الصلاحية موجودة بالفعل لهذه الوكالة'], 422);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        
        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'agency_id' => $user->agency_id,
        ]);
        return response()->json(['message' => 'تمت إضافة الصلاحية بنجاح', 'permission' => $permission], 201);
    }

    // تعديل اسم صلاحية
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $permission = Permission::where('agency_id', $user->agency_id)->findOrFail($id);
        
        // التحقق من وجود الصلاحية لنفس الوكالة (باستثناء الصلاحية الحالية)
        $exists = Permission::where('name', $request->name)
            ->where('guard_name', 'web')
            ->where('agency_id', $user->agency_id)
            ->where('id', '!=', $id)
            ->exists();
            
        if ($exists) {
            return response()->json(['message' => 'هذه الصلاحية موجودة بالفعل لهذه الوكالة'], 422);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        
        $permission->update(['name' => $validated['name']]);
        return response()->json(['message' => 'تم تعديل اسم الصلاحية بنجاح', 'permission' => $permission]);
    }

    // حذف صلاحية
    public function destroy($id)
    {
        $user = auth()->user();
        $permission = Permission::where('agency_id', $user->agency_id)->findOrFail($id);
        $permission->delete();
        return response()->json(['message' => 'تم حذف الصلاحية بنجاح']);
    }
}
