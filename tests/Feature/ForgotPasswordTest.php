<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_reset_link_email()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'تم إرسال رابط إعادة تعيين كلمة المرور']);
    }
} 