<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,NULL,id,agency_id,' . $user->agency_id,
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|exists:permissions,name',
        ]);
        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'agency_id' => $user->agency_id,
        ]);
        $role->syncPermissions($validated['permissions']);
        return response()->json([
            'message' => 'تم إنشاء الدور وربطه بالصلاحيات بنجاح',
            'role' => $role,
            'permissions' => $role->permissions->pluck('name'),
        ], 201);
    }

    // عرض جميع الأدوار مع صلاحياتها
    public function index()
    {
        $user = auth()->user();
        // فقط أدمن الوكالة يدير الأدوار
        $roles = Role::with('permissions')->where('agency_id', $user->agency_id)->get();
        return response()->json($roles);
    }

    // عرض دور واحد بالتفصيل
    public function show($id)
    {
        $user = auth()->user();
        $role = Role::with('permissions')->where('agency_id', $user->agency_id)->findOrFail($id);
        return response()->json($role);
    }

    // تعديل اسم دور وصلاحياته
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $role = Role::where('agency_id', $user->agency_id)->findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $id . ',id,agency_id,' . $user->agency_id,
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|exists:permissions,name',
        ]);
        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions']);
        return response()->json([
            'message' => 'تم تعديل الدور والصلاحيات بنجاح',
            'role' => $role,
            'permissions' => $role->permissions->pluck('name'),
        ]);
    }

    // حذف دور
    public function destroy($id)
    {
        $user = auth()->user();
        $role = Role::where('agency_id', $user->agency_id)->findOrFail($id);
        $role->delete();
        return response()->json(['message' => 'تم حذف الدور بنجاح']);
    }
} 