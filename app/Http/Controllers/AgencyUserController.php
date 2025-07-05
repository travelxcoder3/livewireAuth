<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AgencyUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:agency-admin');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', User::class);
        $user = auth()->user();
        $users = User::where('agency_id', $user->agency_id)
            ->where('id', '!=', $user->id) // لا تعرض الأدمن نفسه
            ->get();
        return response()->json($users);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // $this->authorize('create', User::class);
        $user = auth()->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|exists:roles,name',
        ]);
        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'agency_id' => $user->agency_id,
        ]);
        $newUser->assignRole($validated['role']);
        return response()->json([
            'message' => 'تم إضافة المستخدم بنجاح',
            'user' => $newUser,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $targetUser = User::findOrFail($id);
        $this->authorize('update', $targetUser);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:6',
        ]);
        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }
        $targetUser->update($validated);
        return response()->json(['message' => 'تم التعديل بنجاح', 'user' => $targetUser]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $targetUser = User::findOrFail($id);
        $this->authorize('delete', $targetUser);
        $targetUser->delete();
        return response()->json(['message' => 'تم الحذف بنجاح']);
    }

    /**
     * تغيير كلمة المرور للمستخدم الحالي
     * @OA\Post(
     *     path="/api/change-password",
     *     tags={"Users"},
     *     summary="تغيير كلمة المرور للمستخدم الحالي",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password","new_password","new_password_confirmation"},
     *             @OA\Property(property="current_password", type="string"),
     *             @OA\Property(property="new_password", type="string"),
     *             @OA\Property(property="new_password_confirmation", type="string"),
     *         )
     *     ),
     *     @OA\Response(response=200, description="تم تغيير كلمة المرور بنجاح")
     * )
     */
    public function changePassword(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);
        if (!\Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'كلمة المرور الحالية غير صحيحة'], 400);
        }
        $user->password = bcrypt($request->new_password);
        $user->save();
        return response()->json(['message' => 'تم تغيير كلمة المرور بنجاح']);
    }
}
