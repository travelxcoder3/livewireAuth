<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    // عرض جميع الصلاحيات
    public function index()
    {
        // جلب الصلاحيات العامة لجميع الوكالات
        $permissions = Permission::whereNull('agency_id')->get();
        return response()->json($permissions);
    }

    // إضافة صلاحية جديدة
    public function store(Request $request)
    {
        $user = auth()->user();
        
        // التحقق من وجود الصلاحية
        $exists = Permission::where('name', $request->name)
            ->where('guard_name', 'web')
            ->whereNull('agency_id')
            ->exists();
            
        if ($exists) {
            return response()->json(['message' => 'هذه الصلاحية موجودة بالفعل'], 422);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        
        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);
        
        // ربط الصلاحية الجديدة بدور أدمن الوكالة تلقائيًا
        $agencyAdminRole = \Spatie\Permission\Models\Role::where('name', 'agency-admin')
            ->where('agency_id', $user->agency_id)
            ->first();
        if ($agencyAdminRole) {
            $agencyAdminRole->givePermissionTo($permission->name);
        }
        return response()->json(['message' => 'تمت إضافة الصلاحية بنجاح', 'permission' => $permission], 201);
    }

    // تعديل اسم صلاحية
    public function update(Request $request, $id)
    {
        return response()->json(['message' => 'تعديل الصلاحيات غير مسموح'], 403);
    }

    // حذف صلاحية
    public function destroy($id)
    {
        return response()->json(['message' => 'حذف الصلاحيات غير مسموح'], 403);
    }
}
