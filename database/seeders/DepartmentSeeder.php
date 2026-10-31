<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
                $headOfficeDepts = [
            'Administration',
            'Finance',
            'Human Resources',
            'Academics',
            'IT',
            'Operations',
        ];

        $schoolDepts = [
            'Teaching',
            'Non-teaching',
        ];

        foreach ($headOfficeDepts as $name) {
            Department::updateOrCreate(
                ['name' => $name],
                ['is_active' => true, 'branch_type' => 'head_office']
            );
        }

        foreach ($schoolDepts as $name) {
            Department::updateOrCreate(
                ['name' => $name],
                ['is_active' => true, 'branch_type' => 'school']
            );
        }
    }
}