<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            'Head Office',
            'School 1',
            'School 2',
        ];

        foreach ($branches as $branch) {
            Branch::firstOrCreate(
                ['name' => $branch],
                ['is_active' => true]
            );
        }
    }
}