<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['name' => 'Head Office', 'type' => 'head_office'],
            ['name' => 'School 1', 'type' => 'school'],
            ['name' => 'School 2', 'type' => 'school'],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(
                ['name' => $branch['name']],
                ['is_active' => true, 'type' => $branch['type']]
            );
        }
    }
}