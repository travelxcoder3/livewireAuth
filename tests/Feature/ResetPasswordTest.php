<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password()
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'test@example.com']);

        // أرسل رابط الاستعادة
        $this->postJson('/api/forgot-password', [
            'email' => 'test@example.com',
        ]);

        // احصل على التوكن من الإشعار
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;
            return true;
        });

        // نفذ إعادة التعيين
        $response = $this->postJson('/api/reset-password', [
            'email' => 'test@example.com',
            'token' => $token,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'تم إعادة تعيين كلمة المرور بنجاح']);
    }
} 