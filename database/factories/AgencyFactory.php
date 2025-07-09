<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AgencyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'license_number' => $this->faker->unique()->numerify('LIC#####'),
            'commercial_record' => $this->faker->unique()->numerify('CR#####'),
            'tax_number' => $this->faker->unique()->numerify('TAX#####'),
            'logo' => null,
            'description' => $this->faker->sentence,
            'status' => 'active',
            'license_expiry_date' => $this->faker->date,
            'currency' => 'SAR',
            'main_branch_name' => 'الفرع الرئيسي',
        ];
    }
} 