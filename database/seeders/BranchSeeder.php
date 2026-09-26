<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::create([
            'name' => 'Head Office',
            'is_active' => true,
        ]);

        Branch::create([
            'name' => 'School 1',
            'is_active' => true,
        ]);

        Branch::create([
            'name' => 'School 2',
            'is_active' => true,
        ]);
    }
}