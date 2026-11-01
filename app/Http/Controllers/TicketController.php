<?php

namespace App\Http\Controllers;

use App\Mail\TicketMessageMail;
use App\Mail\NewTicketMail;
use App\Mail\TicketClosedMail;
use App\Mail\TicketReopenedMail;
use App\Mail\TicketSubmittedMail;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Department;
use App\Models\SlaSetting;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Ticket Submission Form
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        if (session('ticket_submitted')) {
            return redirect()
                ->route('tickets.submitted');
        }

        $branches = Branch::where('is_active', true)
            ->get();

        $categories = Category::where('is_active', true)
            ->get();

        $departments = Department::where('is_active', true)
            ->get();

        return view(
            'tickets.create',
            compact(
                'branches',
                'categories',
                'departments'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Submit Ticket
    |--------------------------------------------------------------------------
    */

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
                Rule::exists('departments', 'id')->where('is_active', true),
            ],

            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where('is_active', true),
            ],

            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('is_active', true),
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

        /*
        |--------------------------------------------------------------------------
        | Validate Branch-specific Department
        |--------------------------------------------------------------------------
        */

        if (!empty($validated['department_id'])) {

            $department = Department::find(
                $validated['department_id']
            );

            $branch = Branch::find(
                $validated['branch_id']
            );

            if (
                $department
                &&
                $branch
                &&
                $department->branch_type !== $branch->type
            ) {
                return back()
                    ->withErrors([
                        'department_id' =>
                            'The selected department is not valid for the chosen branch.'
                    ])
                    ->withInput();
            }
        }

        $branch = Branch::findOrFail($validated['branch_id']);
        $category = Category::findOrFail($validated['category_id']);

        if (!$branch->is_active || !$category->is_active) {
            return back()->withErrors([
                'branch_id' => 'The selected branch or category is no longer active.',
            ])->withInput();
        }

        unset($validated['attachments']);

        /*
        |--------------------------------------------------------------------------
        | SLA Due Date
        |--------------------------------------------------------------------------
        */

        $sla = SlaSetting::where(
                'priority',
                $validated['priority']
            )
            ->first();

        $dueAt = null;

        if (
            $sla
            &&
            $sla->resolution_hours
        ) {
            $dueAt = now()
                ->addHours(
                    $sla->resolution_hours
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Create Ticket
        |--------------------------------------------------------------------------
        */

        $ticket = DB::transaction(function () use ($validated, $dueAt, $request) {
        $ticket = Ticket::create([
            ...$validated,

            'ticket_number' =>
                'ZT-'
                . now()->format('Ymd')
                . '-'
                . strtoupper(Str::random(5)),

            'tracking_token' =>
                Str::random(64),

            'status' => 'New',

            'due_at' => $dueAt,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Save Attachments
        |--------------------------------------------------------------------------
        */

        foreach (
            $request->file('attachments', [])
            as $file
        ) {
            $size = $file->getSize();

            $path = $file->store(
                'attachments/' . $ticket->id
            );

            $ticket
                ->attachments()
                ->create([
                    'path' => $path,

                    'original_name' =>
                        $file->getClientOriginalName(),

                    'mime_type' =>
                        $file->getClientMimeType(),

                    'size' => $size,
                ]);
        }

        return $ticket;
        });

        /*
        |--------------------------------------------------------------------------
        | Email Requester
        |--------------------------------------------------------------------------
        */

        Mail::to(
            $ticket->requester_email
        )->send(
            new TicketSubmittedMail(
                $ticket
            )
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
            ->where('is_active', true)
            ->where(
                'branch_id',
                $ticket->branch_id
            )
            ->get();

        foreach (
            $branchAdmins as $admin
        ) {
            Mail::to(
                $admin->email
            )->send(
                new NewTicketMail(
                    $ticket
                )
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

        return redirect()
            ->route(
                'tickets.success',
                $ticket
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Submitted Page
    |--------------------------------------------------------------------------
    */

    public function submitted()
    {
        return view(
            'tickets.submitted'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Reset Public Form
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Success Page
    |--------------------------------------------------------------------------
    */

    public function success(
        Ticket $ticket
    ) {
        return view(
            'tickets.success',
            compact('ticket')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Public Tracking Page
    |--------------------------------------------------------------------------
    */

    public function track(
        Ticket $ticket
    ) {
        /*
         * Public status history only.
         */
        $updates = $ticket
            ->activities()
            ->where(
                'action',
                'Status Changed'
            )
            ->latest()
            ->get();

        /*
         * IMPORTANT:
         * Requester can only see public messages.
         * Internal IT messages remain hidden.
         */
        $comments = $ticket
            ->comments()
            ->where(
                'is_public',
                true
            )
            ->latest()
            ->get();

        /*
         * Find latest close event so tracking
         * page can show who closed the ticket.
         */
        $closure = $ticket
            ->activities()
            ->where(
                'new_value',
                'Closed'
            )
            ->latest()
            ->first();

        return view(
            'tickets.track',
            compact(
                'ticket',
                'updates',
                'comments',
                'closure'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Public Attachment Download
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Requester Cancels Ticket
    |--------------------------------------------------------------------------
    */

    public function cancel(
        Ticket $ticket
    ) {
        if (
            in_array(
                $ticket->status,
                ['Closed', 'Cancelled']
            )
        ) {
            return redirect()
                ->route(
                    'tickets.track',
                    $ticket
                );
        }

        $oldStatus =
            $ticket->status;

        $ticket->status =
            'Cancelled';

        $ticket->save();

        TicketActivity::create([
            'ticket_id' =>
                $ticket->id,

            'user_id' =>
                null,

            'actor_label' =>
                'Requester',

            'action' => 'Requester Cancelled Ticket',

            'old_value' =>
                $oldStatus,

            'new_value' =>
                'Cancelled',
        ]);

        return redirect()
            ->route(
                'tickets.track',
                $ticket
            )
            ->with(
                'success',
                'Ticket cancelled.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Requester Approves Resolution
    |--------------------------------------------------------------------------
    */

    public function approveResolution(
        Ticket $ticket
    ) {
        /*
         * Only resolved tickets can be
         * approved and closed.
         */
        if (
            $ticket->status !== 'Resolved'
        ) {
            return redirect()
                ->route(
                    'tickets.track',
                    $ticket
                );
        }

        $oldStatus =
            $ticket->status;

        $ticket->status =
            'Closed';

        $ticket->closed_at =
            now();

        $ticket->save();

        TicketActivity::create([
            'ticket_id' =>
                $ticket->id,

            'user_id' =>
                null,

            'actor_label' =>
                'Requester',

            'action' => 'Requester Approved Resolution',

            'old_value' =>
                $oldStatus,

            'new_value' =>
                'Closed',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Closed Email
        |--------------------------------------------------------------------------
        */

        Mail::to(
            $ticket->requester_email
        )->send(
            new TicketClosedMail(
                $ticket
            )
        );

        return redirect()
            ->route(
                'tickets.track',
                $ticket
            )
            ->with(
                'success',
                'Ticket closed successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Requester Rejects Resolution
    |--------------------------------------------------------------------------
    */

    public function rejectResolution(
        Ticket $ticket
    ) {
        if (
            $ticket->status !== 'Resolved'
        ) {
            return redirect()
                ->route(
                    'tickets.track',
                    $ticket
                );
        }

        $oldStatus =
            $ticket->status;

        $ticket->status =
            'Reopened';

        /*
         * Ticket is no longer considered resolved.
         */
        $ticket->resolved_at =
            null;

        /*
         * Allow it to escalate again later
         * if it becomes overdue.
         */
        $ticket->escalated_at =
            null;

        $ticket->save();

        TicketActivity::create([
            'ticket_id' =>
                $ticket->id,

            'user_id' =>
                null,

            'actor_label' =>
                'Requester',

            'action' => 'Requester Rejected Resolution',

            'old_value' =>
                $oldStatus,

            'new_value' =>
                'Reopened',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Notify ALL Assigned Technicians
        |--------------------------------------------------------------------------
        */

        $recipients = [];

        $technicianEmails = $ticket
            ->technicians()
            ->pluck(
                'users.email'
            )
            ->all();

        $recipients = array_merge(
            $recipients,
            $technicianEmails
        );

        /*
        |--------------------------------------------------------------------------
        | Notify Relevant Branch Admins
        |--------------------------------------------------------------------------
        */

        $branchAdminEmails =
            User::where(
                'role',
                'branch_admin'
            )
            ->where('is_active', true)
            ->where(
                'branch_id',
                $ticket->branch_id
            )
            ->pluck('email')
            ->all();

        $recipients = array_merge(
            $recipients,
            $branchAdminEmails
        );

        foreach (
            array_unique($recipients)
            as $email
        ) {
            Mail::to(
                $email
            )->send(
                new TicketReopenedMail(
                    $ticket
                )
            );
        }

        return redirect()
            ->route(
                'tickets.track',
                $ticket
            )
            ->with(
                'success',
                'Ticket reopened. IT Support has been notified.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Requester Sends Chat Message
    |--------------------------------------------------------------------------
    */

    public function sendMessage(Request $request, Ticket $ticket)
{
    if (in_array($ticket->status, ['Closed', 'Cancelled'])) {
        return redirect()
            ->route('tickets.track', $ticket)
            ->with(
                'error',
                'Messaging is disabled for closed or cancelled tickets.'
            );
    }

    $validated = $request->validate([
        'body' => 'required|string|max:2000',
    ]);

    TicketComment::create([
        'ticket_id' => $ticket->id,
        'user_id' => null,
        'sender_type' => 'requester',
        'body' => $validated['body'],
        'is_public' => true,
    ]);

    /*
     * Email all technicians currently assigned
     * to this ticket.
     */
    $technicianEmails = $ticket->technicians()
        ->whereNotNull('email')
        ->pluck('email')
        ->unique()
        ->values();

    foreach ($technicianEmails as $email) {
        Mail::to($email)
            ->send(
                new TicketMessageMail(
                    $ticket,
                    $validated['body'],
                    $ticket->requester_name,
                    'staff'
                )
            );
    }

    return redirect()
        ->route('tickets.track', $ticket)
        ->with('success', 'Message sent to IT Support.');
}
    public function getMessages(Ticket $ticket)
{
    $comments = $ticket->comments()
        ->where('is_public', true)
        ->with('user')
        ->orderBy('created_at')
        ->get();

    return response()->json(
        $comments->map(function ($comment) {
            return [
                'id' => $comment->id,
                'sender_type' => $comment->sender_type,
                'sender_name' => $comment->sender_type === 'requester'
                    ? 'Requester'
                    : ($comment->user?->name ?? 'IT Support'),
                'body' => $comment->body,
                'created_at' => $comment->created_at
                    ->format('d M Y, h:i A'),
            ];
        })
    );
}
}
