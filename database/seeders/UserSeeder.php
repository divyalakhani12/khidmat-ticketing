<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $headOffice = Branch::where('name', 'Head Office')->value('id');
        $school1 = Branch::where('name', 'School 1')->value('id');
        $school2 = Branch::where('name', 'School 2')->value('id');

        $users = [
            // name, email, role, branch
            ['Super Admin', 'super@zindagitrust.org', 'super_admin', null],

            ['Head Office Admin', 'ho.admin@zindagitrust.org', 'branch_admin', $headOffice],
            ['School 1 Admin', 's1.admin@zindagitrust.org', 'branch_admin', $school1],
            ['School 2 Admin', 's2.admin@zindagitrust.org', 'branch_admin', $school2],

            ['Head Office Tech', 'ho.tech@zindagitrust.org', 'it_support', $headOffice],
            ['School 1 Tech', 's1.tech@zindagitrust.org', 'it_support', $school1],
            ['School 2 Tech', 's2.tech@zindagitrust.org', 'it_support', $school2],
        ];

        foreach ($users as [$name, $email, $role, $branchId]) {
            User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => 'password',
                    'role' => $role,
                    'branch_id' => $branchId,
                ]
            );
        }
    }
}