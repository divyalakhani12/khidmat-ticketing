<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Hardware',
            'Software',
            'Network/Internet',
            'Email/Google Workspace/Microsoft 365',
            'Printer/Scanning',
            'Access/Account Issues',
            'Website/System Issues',
            'Other',
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category],
                ['is_active' => true]
            );
        }
    }
}