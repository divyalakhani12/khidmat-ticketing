<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

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
            Category::create([
                'name' => $category,
                'is_active' => true,
            ]);
        }
    }
}