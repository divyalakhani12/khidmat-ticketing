<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_requester_can_track_only_with_the_ticket_token(): void
    {
        $ticket = Ticket::factory()->create();

        $this->get('/track/'.$ticket->tracking_token)->assertOk();
        $this->get('/track/not-the-token')->assertNotFound();
    }

    public function test_submission_rejects_a_department_from_another_branch_type(): void
    {
        Mail::fake();
        $school = Branch::create(['name' => 'School 1', 'type' => 'school', 'is_active' => true]);
        $department = Department::create(['name' => 'Finance', 'branch_type' => 'head_office', 'is_active' => true]);
        $category = Category::create(['name' => 'Hardware', 'is_active' => true]);

        $response = $this->post('/submit-ticket', [
            'requester_name' => 'Test Requester',
            'requester_email' => 'requester@zindagitrust.org',
            'branch_id' => $school->id,
            'department_id' => $department->id,
            'category_id' => $category->id,
            'priority' => 'Medium',
            'subject' => 'Printer issue',
            'description' => 'The printer is not responding to print jobs.',
        ]);

        $response->assertSessionHasErrors('department_id');
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_branch_admin_cannot_open_another_branch_ticket(): void
    {
        $first = Branch::create(['name' => 'Head Office', 'type' => 'head_office', 'is_active' => true]);
        $second = Branch::create(['name' => 'School 1', 'type' => 'school', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'branch_admin', 'branch_id' => $first->id]);
        $ticket = Ticket::factory()->create(['branch_id' => $second->id]);

        $this->actingAs($admin)
            ->get('/dashboard/tickets/'.$ticket->id)
            ->assertForbidden();
    }

    public function test_staff_public_message_is_visible_but_internal_note_is_not(): void
    {
        $ticket = Ticket::factory()->create(['status' => 'In Progress']);
        $staff = User::factory()->create(['role' => 'it_support', 'branch_id' => $ticket->branch_id]);
        $ticket->technicians()->attach($staff);

        $this->actingAs($staff)->post('/dashboard/tickets/'.$ticket->id.'/comments', [
            'body' => 'Internal investigation note',
        ])->assertRedirect();

        $this->actingAs($staff)->post('/dashboard/tickets/'.$ticket->id.'/comments', [
            'body' => 'We are working on this issue.',
            'is_public' => '1',
        ])->assertRedirect();

        $this->get('/track/'.$ticket->tracking_token)
            ->assertOk()
            ->assertDontSee('Internal investigation note')
            ->assertSee('We are working on this issue.');
    }

    public function test_unassigned_technician_cannot_open_a_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        $technician = User::factory()->create(['role' => 'it_support', 'branch_id' => $ticket->branch_id]);

        $this->actingAs($technician)
            ->get('/dashboard/tickets/'.$ticket->id)
            ->assertForbidden();
    }

    public function test_only_super_admin_can_access_administration(): void
    {
        $staff = User::factory()->create(['role' => 'it_support']);
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($staff)->get('/dashboard/admin')->assertForbidden();
        $this->actingAs($admin)->get('/dashboard/admin')->assertOk();
    }

    public function test_requester_can_cancel_ticket(): void
    {
        $ticket = Ticket::factory()->create(['status' => 'In Progress']);

        $this->post('/track/'.$ticket->tracking_token.'/cancel')
            ->assertRedirect('/track/'.$ticket->tracking_token);

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'Cancelled']);
    }

    public function test_requester_can_approve_or_reject_resolution(): void
    {
        $approved = Ticket::factory()->create(['status' => 'Resolved', 'resolved_at' => Carbon::now()]);
        $this->post('/track/'.$approved->tracking_token.'/approve')->assertRedirect();
        $this->assertDatabaseHas('tickets', ['id' => $approved->id, 'status' => 'Closed']);

        $rejected = Ticket::factory()->create(['status' => 'Resolved', 'resolved_at' => Carbon::now()]);
        $this->post('/track/'.$rejected->tracking_token.'/reject')->assertRedirect();
        $this->assertDatabaseHas('tickets', ['id' => $rejected->id, 'status' => 'Reopened', 'resolved_at' => null]);
    }

    public function test_branch_admin_can_assign_multiple_local_technicians(): void
    {
        $branch = Branch::create(['name' => 'Head Office', 'type' => 'head_office', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'branch_admin', 'branch_id' => $branch->id]);
        $first = User::factory()->create(['role' => 'it_support', 'branch_id' => $branch->id]);
        $second = User::factory()->create(['role' => 'it_support', 'branch_id' => $branch->id]);
        $ticket = Ticket::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($admin)->post('/dashboard/tickets/'.$ticket->id.'/assign', [
            'technicians' => [$first->id, $second->id],
        ])->assertRedirect();

        $this->assertCount(2, $ticket->fresh()->technicians);
    }
}
