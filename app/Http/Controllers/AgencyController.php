<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agency;
use App\Models\User;
use Spatie\Permission\Models\Role;
use OpenApi\Annotations as OA;
use Database\Seeders\PermissionSeeder;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Travel Agency API",
 *     description="توثيق API لنظام إدارة وكالات السفر",
 *     @OA\Contact(
 *         email="support@example.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Local server"
 * )
 */

/**
 * @OA\Tag(
 *     name="Agencies",
 *     description="إدارة الوكالات (للسوبر أدمن فقط)"
 * )
 */
class AgencyController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super-admin');
    }

    /**
     * @OA\Get(
     *     path="/api/agencies",
     *     tags={"Agencies"},
     *     summary="عرض جميع الوكالات",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="قائمة الوكالات")
     * )
     */
    public function index()
    {
        $agencies = Agency::all();
        return response()->json($agencies);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * @OA\Post(
     *     path="/api/agencies",
     *     tags={"Agencies"},
     *     summary="إضافة وكالة جديدة مع أدمن وكالة",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"agency_name","admin_name","admin_email","admin_password"},
     *             @OA\Property(property="agency_name", type="string"),
     *             @OA\Property(property="admin_name", type="string"),
     *             @OA\Property(property="admin_email", type="string", format="email"),
     *             @OA\Property(property="admin_password", type="string", format="password"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="license_number", type="string"),
     *             @OA\Property(property="commercial_record", type="string"),
     *             @OA\Property(property="tax_number", type="string"),
     *             @OA\Property(property="logo", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="license_expiry_date", type="string", format="date"),
     *             @OA\Property(property="currency", type="string"),
     *         )
     *     ),
     *     @OA\Response(response=201, description="تم إنشاء الوكالة والأدمن بنجاح")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'agency_name' => 'required|string|max:255',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:6',
        ]);

        $agency = Agency::create([
            'name' => $validated['agency_name'],
            ...$request->only([
                'email',
                'phone',
                'address',
                'license_number',
                'commercial_record',
                'tax_number',
                'logo',
                'description',
                'status',
                'license_expiry_date',
                'currency',
            ])
        ]);

        // إنشاء الصلاحيات الأساسية للوكالة
        $permissionSeeder = new PermissionSeeder();
        $permissionSeeder->createPermissionsForAgency($agency->id);
        
        // إنشاء دور agency-admin خاص بالوكالة
        $agencyAdminRole = Role::firstOrCreate([
            'name' => 'agency-admin',
            'guard_name' => 'web',
            'agency_id' => $agency->id,
        ]);
        
        // ربط دور agency-admin بجميع الصلاحيات
        $agencyAdminRole->givePermissionTo([
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete',
            'reports.view', 'reports.export',
            'settings.view', 'settings.edit',
        ]);
        
        $admin = User::create([
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'password' => bcrypt($validated['admin_password']),
            'agency_id' => $agency->id,
        ]);
        $admin->assignRole($agencyAdminRole);

        return response()->json([
            'message' => 'تم إنشاء الوكالة والأدمن بنجاح',
            'agency' => $agency,
            'admin' => $admin,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/agencies/{id}",
     *     tags={"Agencies"},
     *     summary="عرض وكالة محددة",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="بيانات الوكالة")
     * )
     */
    public function show(string $id)
    {
        $agency = Agency::findOrFail($id);
        return response()->json($agency);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * @OA\Put(
     *     path="/api/agencies/{id}",
     *     tags={"Agencies"},
     *     summary="تعديل بيانات وكالة",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="تم التحديث بنجاح")
     * )
     */
    public function update(Request $request, string $id)
    {
        $agency = Agency::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $agency->update($validated);
        return response()->json(['message' => 'تم التحديث بنجاح', 'agency' => $agency]);
    }

    /**
     * @OA\Delete(
     *     path="/api/agencies/{id}",
     *     tags={"Agencies"},
     *     summary="حذف وكالة",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="تم الحذف بنجاح")
     * )
     */
    public function destroy(string $id)
    {
        $agency = Agency::findOrFail($id);
        $agency->delete();
        return response()->json(['message' => 'تم الحذف بنجاح']);
    }

    /**
     * @OA\Patch(
     *     path="/api/agencies/{id}/status",
     *     tags={"Agencies"},
     *     summary="تغيير حالة الوكالة (تفعيل/تعليق/إلغاء)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="تم تحديث حالة الوكالة بنجاح")
     * )
     */
    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,inactive,suspended',
        ]);
        $agency = Agency::findOrFail($id);
        $agency->status = $request->status;
        $agency->save();
        return response()->json([
            'message' => 'تم تحديث حالة الوكالة بنجاح',
            'agency' => $agency
        ]);
    }
}
