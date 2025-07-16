<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(RoleSeeder::class);
        $this->call(CustomerSeeder::class);
        $this->call(ServiceTypeSeeder::class);
        $this->call(ProviderSeeder::class);
        $this->call(IntermediarySeeder::class);
        $this->call(AccountSeeder::class);
        $this->call(PositionSeeder::class);
        $this->call(DepartmentSeeder::class);
        $this->call(CollectionsTableSeeder::class);
        // $this->call(PermissionSeeder::class); // تم تعطيله لأن الصلاحيات تُنشأ تلقائيًا لكل وكالة
        $this->call(CreatePermissionsForExistingAgenciesSeeder::class);
        $this->call(SystemDynamicListsSeeder::class); // Seeder القوائم
        // $this->call(AgencySeeder::class);
        $this->call(ServicesSeeder::class);
       
    }
}
