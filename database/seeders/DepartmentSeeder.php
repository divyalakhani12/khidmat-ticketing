<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'Administration',
            'Finance',
            'Human Resources',
            'Academics',
            'IT',
            'Operations',
        ];

        foreach ($departments as $name) {
            Department::firstOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }
    }
}