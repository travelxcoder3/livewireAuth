<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChangeAgencyStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_change_agency_status()
    {
        Role::create(['name' => 'super-admin']);
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $agency = Agency::factory()->create(['status' => 'active']);

        $response = $this->actingAs($superAdmin, 'sanctum')->patchJson("/api/agencies/{$agency->id}/status", [
            'status' => 'inactive',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'تم تحديث حالة الوكالة بنجاح']);

        $this->assertEquals('inactive', $agency->fresh()->status);
    }

    public function test_non_super_admin_cannot_change_agency_status()
    {
        $user = User::factory()->create();
        $agency = Agency::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/agencies/{$agency->id}/status", [
            'status' => 'inactive',
        ]);

        $response->assertStatus(403);
    }
} 