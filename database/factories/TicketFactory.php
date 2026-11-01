<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Department;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        $branch = Branch::query()->first() ?? Branch::create([
            'name' => 'Test Branch',
            'type' => 'head_office',
            'is_active' => true,
        ]);
        $category = Category::query()->first() ?? Category::create([
            'name' => 'Test Category',
            'is_active' => true,
        ]);

        return [
            'ticket_number' => 'ZT-'.$this->faker->date('Ymd').'-'.strtoupper(Str::random(5)),
            'tracking_token' => Str::random(64),
            'requester_name' => $this->faker->name(),
            'requester_email' => 'requester@zindagitrust.org',
            'contact_number' => null,
            'branch_id' => $branch->id,
            'department_id' => null,
            'category_id' => $category->id,
            'priority' => 'Medium',
            'subject' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'status' => 'New',
        ];
    }
}
