<?php

namespace Database\Seeders;

use App\Models\SlaSetting;
use Illuminate\Database\Seeder;

class SlaSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'priority' => 'Low',
                'response_hours' => 24,
                'resolution_hours' => 72,
            ],
            [
                'priority' => 'Medium',
                'response_hours' => 12,
                'resolution_hours' => 48,
            ],
            [
                'priority' => 'High',
                'response_hours' => 4,
                'resolution_hours' => 24,
            ],
            [
                'priority' => 'Critical',
                'response_hours' => 1,
                'resolution_hours' => 4,
            ],
        ];

        foreach ($settings as $setting) {
            SlaSetting::updateOrCreate(
                ['priority' => $setting['priority']],
                $setting
            );
        }
    }
}