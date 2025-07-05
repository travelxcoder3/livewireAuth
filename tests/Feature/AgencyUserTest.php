<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AgencyUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_agency_admin_can_add_user_to_his_agency()
    {
        Role::create(['name' => 'agency-admin']);
        Role::create(['name' => 'accountant']);

        $agency = Agency::factory()->create();
        $admin = User::factory()->create(['agency_id' => $agency->id]);
        $admin->assignRole('agency-admin');

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/agency-users', [
            'name' => 'موظف جديد',
            'email' => 'newuser@example.com',
            'password' => '123456',
            'role' => 'accountant'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'agency_id' => $agency->id,
        ]);
    }
} 