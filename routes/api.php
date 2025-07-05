<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\AgencyUserController;

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

// الأدوار تُدار بشكل دائم عبر Seeder ولا حاجة لإنشائها هنا

// Route لتسجيل الدخول عبر API
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);
    if (Auth::attempt($credentials)) {
        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json(['token' => $token]);
    }
    return response()->json(['message' => 'Unauthorized'], 401);
});

// Route افتراضي (محمي بـ sanctum)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Route لإضافة وكالة جديدة عبر API (فقط للسوبر أدمن)
Route::middleware(['auth:sanctum', 'role:super-admin'])->post('/agencies', [AgencyController::class, 'store']);

// Route لإنشاء مستخدم agency-admin جديد (فقط للسوبر أدمن)
Route::middleware(['auth:sanctum', 'role:super-admin'])->post('/agency-admin', function (Request $request) {
    $data = $request->validate([
        'name' => 'required',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:6',
        'agency_id' => 'required|exists:agencies,id',
    ]);
    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
        'agency_id' => $data['agency_id'],
    ]);
    $user->assignRole('agency-admin');
    return response()->json(['message' => 'Agency Admin created', 'user' => $user]);
});

// Route لإضافة مستخدم جديد لوكالة الأدمن (فقط لأدمن الوكالة)
Route::middleware(['auth:sanctum', 'role:agency-admin'])->post('/agency-users', [\App\Http\Controllers\AgencyUserController::class, 'store']);

// Routes لإدارة مستخدمي الوكالة (فقط لأدمن الوكالة)
Route::middleware(['auth:sanctum', 'role:agency-admin'])->get('/agency-users', [\App\Http\Controllers\AgencyUserController::class, 'index']);
Route::middleware(['auth:sanctum', 'role:agency-admin'])->put('/agency-users/{id}', [\App\Http\Controllers\AgencyUserController::class, 'update']);
Route::middleware(['auth:sanctum', 'role:agency-admin'])->delete('/agency-users/{id}', [\App\Http\Controllers\AgencyUserController::class, 'destroy']);

// Routes لإدارة الوكالات (عرض، تعديل، حذف، قائمة) للسوبر أدمن وأدمن الوكالة
Route::middleware(['auth:sanctum', 'role:super-admin|agency-admin'])->get('/agencies', [\App\Http\Controllers\AgencyController::class, 'index']);
Route::middleware(['auth:sanctum', 'role:super-admin|agency-admin'])->get('/agencies/{id}', [\App\Http\Controllers\AgencyController::class, 'show']);
Route::middleware(['auth:sanctum', 'role:super-admin|agency-admin'])->put('/agencies/{id}', [\App\Http\Controllers\AgencyController::class, 'update']);
Route::middleware(['auth:sanctum', 'role:super-admin|agency-admin'])->delete('/agencies/{id}', [\App\Http\Controllers\AgencyController::class, 'destroy']);
Route::middleware(['auth:sanctum', 'role:super-admin'])->patch('/agencies/{id}/status', [\App\Http\Controllers\AgencyController::class, 'changeStatus']);

// أو إذا أردت جميع دوال CRUD للوكالات عبر API
// Route::middleware(['auth:sanctum', 'role:super-admin'])->apiResource('agencies', AgencyController::class);

// Route لجلب جميع صلاحيات المستخدم الحالي (للاستخدام في الواجهة)
Route::middleware('auth:sanctum')->get('/my-permissions', function (\Illuminate\Http\Request $request) {
    return $request->user()->getAllPermissions()->pluck('name');
});

// إدارة الأدوار (فقط لأدمن الوكالة)
Route::middleware(['auth:sanctum', 'role:agency-admin'])->get('/roles', [RoleController::class, 'index']);
Route::middleware(['auth:sanctum', 'role:agency-admin'])->get('/roles/{id}', [RoleController::class, 'show']);
Route::middleware(['auth:sanctum', 'role:agency-admin'])->post('/roles', [RoleController::class, 'store']);
Route::middleware(['auth:sanctum', 'role:agency-admin'])->put('/roles/{id}', [RoleController::class, 'update']);
Route::middleware(['auth:sanctum', 'role:agency-admin'])->delete('/roles/{id}', [RoleController::class, 'destroy']);

// إدارة الصلاحيات (فقط لأدمن الوكالة)
Route::middleware(['auth:sanctum', 'role:agency-admin'])->get('/permissions', [PermissionController::class, 'index']);
Route::middleware(['auth:sanctum', 'role:agency-admin'])->post('/permissions', [PermissionController::class, 'store']);
Route::middleware(['auth:sanctum', 'role:agency-admin'])->put('/permissions/{id}', [PermissionController::class, 'update']);
Route::middleware(['auth:sanctum', 'role:agency-admin'])->delete('/permissions/{id}', [PermissionController::class, 'destroy']);

// تغيير كلمة المرور (يتطلب توثيق)
Route::middleware('auth:sanctum')->post('/change-password', [AgencyUserController::class, 'changePassword']);

// إرسال رابط استعادة كلمة المرور
Route::post('/forgot-password', function (Request $request) {
    $request->validate(['email' => 'required|email']);
    $status = Password::sendResetLink($request->only('email'));
    return $status === Password::RESET_LINK_SENT
        ? response()->json(['message' => 'تم إرسال رابط إعادة تعيين كلمة المرور'])
        : response()->json(['message' => 'حدث خطأ'], 400);
});

// إعادة تعيين كلمة المرور
Route::post('/reset-password', function (Request $request) {
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:6|confirmed',
    ]);
    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            $user->forceFill([
                'password' => bcrypt($password)
            ])->save();
        }
    );
    return $status === Password::PASSWORD_RESET
        ? response()->json(['message' => 'تم إعادة تعيين كلمة المرور بنجاح'])
        : response()->json(['message' => 'حدث خطأ'], 400);
});

