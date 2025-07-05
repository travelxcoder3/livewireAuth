<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_password_with_correct_current_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('oldpassword'),
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/change-password', [
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword',
            'new_password_confirmation' => 'newpassword',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'تم تغيير كلمة المرور بنجاح']);
    }

    public function test_user_cannot_change_password_with_wrong_current_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('oldpassword'),
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/change-password', [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword',
            'new_password_confirmation' => 'newpassword',
        ]);

        $response->assertStatus(400)
                 ->assertJson(['message' => 'كلمة المرور الحالية غير صحيحة']);
    }
} 