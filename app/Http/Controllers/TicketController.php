<?php

namespace App\Http\Controllers;

use App\Models\SlaSetting;
use App\Mail\NewTicketMail;
use App\Mail\TicketSubmittedMail;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\TicketActivity;
use App\Mail\TicketReopenedMail;

class TicketController extends Controller
{
    public function create()
    {
        if (session('ticket_submitted')) {
            return redirect()->route('tickets.submitted');
        }

        $branches = Branch::where('is_active', true)->get();
        $categories = Category::where('is_active', true)->get();
        $departments = Department::where('is_active', true)->get();

        return view('tickets.create', compact(
            'branches',
            'categories',
            'departments'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'requester_name' => [
                'required',
                'string',
                'max:255',
                "regex:/^[A-Za-z\s'-]+$/",
            ],
            'requester_email' => [
                'required',
                'email',
                'max:255',
                'regex:/^[A-Za-z0-9._%+-]+@zindagitrust\.org$/i',
            ],
            'contact_number' => [
                'nullable',
                'regex:/^[0-9+\-\s()]{7,20}$/',
            ],
            'department_id' => [
                'nullable',
                'exists:departments,id',
            ],
            'branch_id' => [
                'required',
                'exists:branches,id',
            ],
            'category_id' => [
                'required',
                'exists:categories,id',
            ],
            'priority' => [
                'required',
                'in:Low,Medium,High,Critical',
            ],
            'subject' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],
            'attachments' => [
                'nullable',
                'array',
                'max:5',
            ],
            'attachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,pdf,docx,xlsx,txt',
                'max:5120',
            ],
        ], [
            'requester_email.regex' =>
                'Please enter a valid Zindagi Trust email address ending in @zindagitrust.org.',
        ]);

        if (!empty($validated['department_id'])) {
            $dept = \App\Models\Department::find($validated['department_id']);
            $branch = \App\Models\Branch::find($validated['branch_id']);

            if ($dept && $branch && $dept->branch_type !== $branch->type) {
                return back()
                    ->withErrors(['department_id' =>
                        'The selected department is not valid for the chosen branch.'])
                    ->withInput();
            }
        }

        unset($validated['attachments']);
        $sla = SlaSetting::where('priority', $validated['priority'])->first();

$dueAt = null;

if ($sla && $sla->resolution_hours) {
    $dueAt = now()->addHours($sla->resolution_hours);
}
        $ticket = Ticket::create([
    ...$validated,

    'ticket_number' =>
        'ZT-' .
        now()->format('Ymd') .
        '-' .
        strtoupper(Str::random(5)),

    'tracking_token' => Str::random(64),

    'status' => 'New',

    'due_at' => $dueAt,
]);

        /*
        |--------------------------------------------------------------------------
        | Save Attachments
        |--------------------------------------------------------------------------
        */

        foreach ($request->file('attachments', []) as $file) {
            $size = $file->getSize();

            $path = $file->store(
                'attachments/' . $ticket->id
            );

            $ticket->attachments()->create([
                'path' => $path,
                'original_name' =>
                    $file->getClientOriginalName(),
                'mime_type' =>
                    $file->getClientMimeType(),
                'size' => $size,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Email Requester
        |--------------------------------------------------------------------------
        */

        Mail::to($ticket->requester_email)
            ->send(
                new TicketSubmittedMail($ticket)
            );

        /*
        |--------------------------------------------------------------------------
        | Email Relevant Branch Admin
        |--------------------------------------------------------------------------
        */

        $branchAdmins = User::where(
                'role',
                'branch_admin'
            )
            ->where(
                'branch_id',
                $ticket->branch_id
            )
            ->get();

        foreach ($branchAdmins as $admin) {
            Mail::to($admin->email)
                ->send(
                    new NewTicketMail($ticket)
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent Immediate Duplicate Submission
        |--------------------------------------------------------------------------
        */

        session([
            'ticket_submitted' => true
        ]);

        /*
        |--------------------------------------------------------------------------
        | Redirect to Success Page
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'tickets.success',
                $ticket
            );
    }

    public function submitted()
    {
        return view(
            'tickets.submitted'
        );
    }

    public function resetForm()
    {
        session()->forget(
            'ticket_submitted'
        );

        return redirect()
            ->route(
                'tickets.create'
            );
    }

    public function success(Ticket $ticket)
    {
        return view(
            'tickets.success',
            compact('ticket')
        );
    }

        public function track(Ticket $ticket)
    {
        $updates = $ticket
            ->activities()
            ->where('action', 'Status Changed')
            ->latest()
            ->get();

        $comments = $ticket
            ->comments()
            ->where('is_public', true)
            ->latest()
            ->get();

        // The most recent "closed" event, if any, for the "who closed it" line.
        $closure = $ticket
            ->activities()
            ->where('new_value', 'Closed')
            ->latest()
            ->first();

        return view('tickets.track', compact(
            'ticket',
            'updates',
            'comments',
            'closure'
        ));
    }

    public function downloadAttachment(
        Ticket $ticket,
        TicketAttachment $attachment
    ) {
        abort_unless(
            $attachment->ticket_id
                === $ticket->id,
            404
        );

        abort_unless(
            Storage::exists(
                $attachment->path
            ),
            404
        );

        return Storage::download(
            $attachment->path,
            $attachment->original_name
        );
    }

        public function cancel(Ticket $ticket)
    {
        // Can't cancel something already finished.
        if (in_array($ticket->status, ['Closed', 'Cancelled'])) {
            return redirect()->route('tickets.track', $ticket);
        }

        $oldStatus = $ticket->status;

        $ticket->status = 'Cancelled';
        $ticket->save();

        TicketActivity::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => null,
            'actor_label' => 'Requester',
            'action'      => 'Status Changed',
            'old_value'   => $oldStatus,
            'new_value'   => 'Cancelled',
        ]);

        return redirect()->route('tickets.track', $ticket);
    }

        public function approveResolution(Ticket $ticket)
    {
        // Only a resolved ticket can be approved.
        if ($ticket->status !== 'Resolved') {
            return redirect()->route('tickets.track', $ticket);
        }

        $oldStatus = $ticket->status;

        $ticket->status = 'Closed';
        $ticket->closed_at = now();
        $ticket->save();

        TicketActivity::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => null,
            'actor_label' => 'Requester',
            'action'      => 'Status Changed',
            'old_value'   => $oldStatus,
            'new_value'   => 'Closed',
        ]);

        return redirect()->route('tickets.track', $ticket);
    }

    public function rejectResolution(Ticket $ticket)
    {
        if ($ticket->status !== 'Resolved') {
            return redirect()->route('tickets.track', $ticket);
        }

        $oldStatus = $ticket->status;

        $ticket->status = 'Reopened';
        $ticket->resolved_at = null;   // no longer resolved
        $ticket->escalated_at = null;  // can escalate again if it goes overdue
        $ticket->save();

        TicketActivity::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => null,
            'actor_label' => 'Requester',
            'action'      => 'Status Changed',
            'old_value'   => $oldStatus,
            'new_value'   => 'Reopened',
        ]);

        // Notify the assigned officer and the branch's admins.
        $recipients = [];

        if ($ticket->assigned_to) {
            $officer = User::find($ticket->assigned_to);
            if ($officer) {
                $recipients[] = $officer->email;
            }
        }

        $branchAdmins = User::where('role', 'branch_admin')
            ->where('branch_id', $ticket->branch_id)
            ->pluck('email')
            ->all();

        $recipients = array_merge($recipients, $branchAdmins);

        foreach (array_unique($recipients) as $email) {
            Mail::to($email)->send(new TicketReopenedMail($ticket));
        }

        return redirect()->route('tickets.track', $ticket);
    }
}