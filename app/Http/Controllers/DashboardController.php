<?php

namespace App\Http\Controllers;

use App\Mail\TicketMessageMail;
use App\Mail\TicketAssignedMail;
use App\Mail\TicketClosedMail;
use App\Mail\TicketResolvedMail;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Ticket::query();

        $query->whereNotIn('status', [
            'Cancelled',
            'Merged'
        ]);

        /*
        |--------------------------------------------------------------------------
        | Role-based visibility
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'branch_admin') {

            $query->where('branch_id', $user->branch_id);

        } elseif ($user->role === 'it_support') {

            $query->whereHas('technicians', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where(
                    'ticket_number',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'requester_name',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'requester_email',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'subject',
                    'like',
                    "%{$search}%"
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        if ($request->filled('branch_id')) {

            $query->where(
                'branch_id',
                $request->branch_id
            );
        }

        if ($request->filled('category_id')) {

            $query->where(
                'category_id',
                $request->category_id
            );
        }

        if ($request->filled('priority')) {

            $query->where(
                'priority',
                $request->priority
            );
        }

        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );
        }

        /*
         * Filter using the new many-to-many
         * technician relationship.
         */
        if ($request->filled('assigned_to')) {

            $officerId = $request->assigned_to;

            $query->whereHas(
                'technicians',
                function ($q) use ($officerId) {
                    $q->where(
                        'users.id',
                        $officerId
                    );
                }
            );
        }

        $tickets = $query
            ->latest()
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Dashboard Summary Cards
        |--------------------------------------------------------------------------
        */

        $summaryQuery = Ticket::query()
            ->whereNotIn('status', [
                'Cancelled',
                'Merged'
            ]);

        if ($user->role === 'branch_admin') {

            $summaryQuery->where(
                'branch_id',
                $user->branch_id
            );

        } elseif ($user->role === 'it_support') {

            $summaryQuery->whereHas(
                'technicians',
                function ($q) use ($user) {
                    $q->where(
                        'users.id',
                        $user->id
                    );
                }
            );
        }

        $totalCount = (clone $summaryQuery)
            ->count();

        $newCount = (clone $summaryQuery)
            ->where('status', 'New')
            ->count();

        $inProgressCount = (clone $summaryQuery)
            ->where('status', 'In Progress')
            ->count();

        $resolvedCount = (clone $summaryQuery)
            ->where('status', 'Resolved')
            ->count();

        $closedCount = (clone $summaryQuery)
            ->where('status', 'Closed')
            ->count();

        $overdueCount = (clone $summaryQuery)
            ->whereNotNull('due_at')
            ->where(
                'due_at',
                '<',
                now()
            )
            ->whereNotIn(
                'status',
                ['Resolved', 'Closed', 'Cancelled']
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Dropdown Data
        |--------------------------------------------------------------------------
        */

        $branches = Branch::where(
                'is_active',
                true
            )
            ->orderBy('name')
            ->get();

        $categories = Category::where(
                'is_active',
                true
            )
            ->orderBy('name')
            ->get();

        $officers = User::where(
                'role',
                'it_support'
            )
            ->orderBy('name')
            ->get();

        return view(
            'dashboard.index',
            compact(
                'tickets',
                'branches',
                'categories',
                'officers',
                'totalCount',
                'newCount',
                'inProgressCount',
                'resolvedCount',
                'closedCount',
                'overdueCount'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Show Ticket
    |--------------------------------------------------------------------------
    */

    public function show(Ticket $ticket)
    {
        if (in_array($ticket->status, ['Cancelled', 'Merged'])) {
            return redirect()
                ->route('dashboard')
                ->with(
                    'error',
                    'Cancelled or merged tickets cannot be opened.'
                );
        }

        $this->abortUnlessAllowed($ticket);

        $officers = User::where(
                'role',
                'it_support'
            )
            ->orderBy('name')
            ->get();

        $activities = $ticket
            ->activities()
            ->with('user')
            ->latest()
            ->get();

        $comments = $ticket
            ->comments()
            ->with('user')
            ->latest()
            ->get();

        $assignedTechnicianIds = $ticket
            ->technicians()
            ->pluck('users.id')
            ->toArray();

        return view(
            'dashboard.show',
            compact(
                'ticket',
                'officers',
                'activities',
                'comments',
                'assignedTechnicianIds'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Comments
    |--------------------------------------------------------------------------
    */

    public function addComment(Request $request, Ticket $ticket)
{
    $this->abortUnlessAllowed($ticket);

    if (in_array($ticket->status, ['Closed', 'Cancelled'])) {
        return redirect()
            ->route('dashboard.tickets.show', $ticket)
            ->with(
                'error',
                'Messages and comments are disabled for closed or cancelled tickets.'
            );
    }

    $validated = $request->validate([
        'body' => 'required|string|max:2000',
    ]);

    $isPublic = $request->boolean('is_public');

    TicketComment::create([
        'ticket_id' => $ticket->id,
        'user_id' => Auth::id(),
        'sender_type' => 'staff',
        'body' => $validated['body'],
        'is_public' => $isPublic,
    ]);

    /*
     * Only send requester an email if this message
     * is visible to the requester.
     */
    if ($isPublic) {
        Mail::to($ticket->requester_email)
            ->send(
                new TicketMessageMail(
                $ticket,
                $validated['body'],
                Auth::user()->name,
                'requester'
            )
            );
    }

    return redirect()
        ->route('dashboard.tickets.show', $ticket)
        ->with('success', 'Message added.');
}

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    private function abortUnlessAllowed(
        Ticket $ticket
    ): void {

        $user = Auth::user();

        /*
         * Branch Admin can only access tickets
         * belonging to their own branch.
         */
        if (
            $user->role === 'branch_admin'
            &&
            $ticket->branch_id !== $user->branch_id
        ) {
            abort(403);
        }

        /*
         * IT Support can access a ticket only if
         * they are one of the technicians assigned
         * through the pivot table.
         */
        if (
            $user->role === 'it_support'
            &&
            !$ticket
                ->technicians()
                ->where(
                    'users.id',
                    $user->id
                )
                ->exists()
        ) {
            abort(403);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update Status
    |--------------------------------------------------------------------------
    */

    public function updateStatus(
        Request $request,
        Ticket $ticket
    ) {
        $this->abortUnlessAllowed($ticket);

        $user = Auth::user();

        if ($user->role === 'it_support') {

            $allowedStatuses = [
                'In Progress',
                'On Hold',
                'Resolved',
            ];

        } else {

            $allowedStatuses = [
                'New',
                'In Progress',
                'On Hold',
                'Resolved',
                'Closed',
                'Reopened',
            ];
        }

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in($allowedStatuses),
            ],
        ]);

        $oldStatus = $ticket->status;
        $newStatus = $validated['status'];

        if ($oldStatus !== $newStatus) {

            $ticket->status = $newStatus;

            if ($newStatus === 'Resolved') {

                $ticket->resolved_at = now();
            }

            if ($newStatus === 'Closed') {

                $ticket->closed_at = now();
            }

            if ($newStatus === 'Reopened') {

                $ticket->closed_at = null;
            }

            $ticket->save();

            TicketActivity::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'action' => 'Status Changed',
                'old_value' => $oldStatus,
                'new_value' => $newStatus,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Resolution Email
            |--------------------------------------------------------------------------
            */

            if ($newStatus === 'Resolved') {

                Mail::to(
                    $ticket->requester_email
                )->send(
                    new TicketResolvedMail(
                        $ticket
                    )
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Closed Email
            |--------------------------------------------------------------------------
            */

            if ($newStatus === 'Closed') {

                Mail::to(
                    $ticket->requester_email
                )->send(
                    new TicketClosedMail(
                        $ticket
                    )
                );
            }
        }

        return redirect()
            ->route(
                'dashboard.tickets.show',
                $ticket
            )
            ->with(
                'success',
                'Ticket status updated successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Download Attachment
    |--------------------------------------------------------------------------
    */

    public function downloadAttachment(
        Ticket $ticket,
        TicketAttachment $attachment
    ) {
        $this->abortUnlessAllowed($ticket);

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
    | Assign Multiple Technicians
    |--------------------------------------------------------------------------
    */

    public function assign(
        Request $request,
        Ticket $ticket
    ) {
        $this->abortUnlessAllowed($ticket);

        $user = Auth::user();

        if (
            !in_array(
                $user->role,
                [
                    'super_admin',
                    'branch_admin'
                ]
            )
        ) {
            abort(403);
        }

        $validated = $request->validate([
            'technicians' => [
                'required',
                'array',
                'min:1'
            ],

            'technicians.*' => [
                'exists:users,id'
            ],
        ]);

        /*
         * Only users who are actually IT Support
         * may be assigned.
         */
        $technicians = User::whereIn(
                'id',
                $validated['technicians']
            )
            ->where(
                'role',
                'it_support'
            )
            ->get();

        if (
            $technicians->count()
            !==
            count($validated['technicians'])
        ) {
            abort(
                422,
                'One or more selected users are not IT technicians.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Existing assignments
        |--------------------------------------------------------------------------
        */

        $oldTechnicians = $ticket
            ->technicians()
            ->pluck('users.name')
            ->toArray();

        $oldTechnicianIds = $ticket
            ->technicians()
            ->pluck('users.id')
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | New assignments
        |--------------------------------------------------------------------------
        */

        $newTechnicianIds = $technicians
            ->pluck('id')
            ->toArray();

        /*
         * Sync automatically:
         *
         * - adds newly selected technicians
         * - keeps existing selected technicians
         * - removes technicians no longer selected
         */
        $ticket
            ->technicians()
            ->sync(
                $newTechnicianIds
            );

        /*
         * Keep assigned_to temporarily so old parts of
         * the project remain compatible while we migrate
         * completely to multiple technicians.
         */
        $ticket->assigned_to =
            $newTechnicianIds[0] ?? null;

        /*
         * Only automatically change New → Assigned.
         *
         * If the ticket is already In Progress,
         * On Hold, etc., don't reset its status.
         */
        if ($ticket->status === 'New') {

            $ticket->status = 'Assigned';
        }

        $ticket->save();

        /*
        |--------------------------------------------------------------------------
        | Activity History
        |--------------------------------------------------------------------------
        */

        $newTechnicians = $technicians
            ->pluck('name')
            ->toArray();

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'Technicians Updated',

            'old_value' =>
                empty($oldTechnicians)
                    ? 'Unassigned'
                    : implode(
                        ', ',
                        $oldTechnicians
                    ),

            'new_value' =>
                implode(
                    ', ',
                    $newTechnicians
                ),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Email only newly assigned technicians
        |--------------------------------------------------------------------------
        */

        $newlyAssignedIds = array_diff(
            $newTechnicianIds,
            $oldTechnicianIds
        );

        foreach ($technicians as $technician) {

            if (
                in_array(
                    $technician->id,
                    $newlyAssignedIds
                )
            ) {

                Mail::to(
                    $technician->email
                )->send(
                    new TicketAssignedMail(
                        $ticket,
                        $technician
                    )
                );
            }
        }

        return redirect()
            ->route(
                'dashboard.tickets.show',
                $ticket
            )
            ->with(
                'success',
                'Technician assignments updated successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Priority
    |--------------------------------------------------------------------------
    */

    public function updatePriority(
        Request $request,
        Ticket $ticket
    ) {
        $this->abortUnlessAllowed($ticket);

        $user = Auth::user();

        if (
            !in_array(
                $user->role,
                [
                    'super_admin',
                    'branch_admin'
                ]
            )
        ) {
            abort(403);
        }

        $validated = $request->validate([
            'priority' =>
                'required|in:Low,Medium,High,Critical',
        ]);

        $oldPriority = $ticket->priority;
        $newPriority =
            $validated['priority'];

        if (
            $oldPriority !== $newPriority
        ) {

            /*
             * Recalculate due date based on
             * the new priority SLA.
             */
            $sla = \App\Models\SlaSetting::where(
                    'priority',
                    $newPriority
                )
                ->first();

            if (
                $sla
                &&
                $sla->resolution_hours
            ) {

                $ticket->due_at =
                    $ticket
                        ->created_at
                        ->copy()
                        ->addHours(
                            $sla->resolution_hours
                        );
            }

            $ticket->priority =
                $newPriority;

            $ticket->save();

            TicketActivity::create([
                'ticket_id' =>
                    $ticket->id,

                'user_id' =>
                    Auth::id(),

                'action' =>
                    'Priority Changed',

                'old_value' =>
                    $oldPriority,

                'new_value' =>
                    $newPriority,
            ]);
        }

        return redirect()
            ->route(
                'dashboard.tickets.show',
                $ticket
            )
            ->with(
                'success',
                'Ticket priority updated successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Transfer Ticket Between Branches
    |--------------------------------------------------------------------------
    */

    public function transferBranch(
        Request $request,
        Ticket $ticket
    ) {
        $this->abortUnlessAllowed($ticket);

        $user = Auth::user();

        if (
            !in_array(
                $user->role,
                [
                    'super_admin',
                    'branch_admin'
                ]
            )
        ) {
            abort(403);
        }

        $validated = $request->validate([
            'branch_id' =>
                'required|exists:branches,id',
        ]);

        $oldBranch = Branch::find(
            $ticket->branch_id
        );

        $newBranch = Branch::findOrFail(
            $validated['branch_id']
        );

        if (
            $ticket->branch_id
            !=
            $newBranch->id
        ) {

            $oldStatus = $ticket->status;

            /*
             * Save ALL currently assigned technicians
             * before clearing them.
             */
            $oldTechnicians = $ticket
                ->technicians()
                ->pluck('users.name')
                ->toArray();

            /*
            |--------------------------------------------------------------------------
            | Transfer
            |--------------------------------------------------------------------------
            */

            $ticket->branch_id =
                $newBranch->id;

            /*
             * Clear backward-compatible assignment.
             */
            $ticket->assigned_to = null;

            /*
             * IMPORTANT:
             * Clear ALL multi-technician assignments.
             */
            $ticket
                ->technicians()
                ->detach();

            /*
             * New branch must review and assign
             * technicians again.
             */
            $ticket->status = 'New';

            $ticket->save();

            /*
            |--------------------------------------------------------------------------
            | Branch Transfer Activity
            |--------------------------------------------------------------------------
            */

            TicketActivity::create([
                'ticket_id' =>
                    $ticket->id,

                'user_id' =>
                    Auth::id(),

                'action' =>
                    'Branch Transferred',

                'old_value' =>
                    $oldBranch?->name
                    ?? 'Unknown',

                'new_value' =>
                    $newBranch->name,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Technician Assignment Cleared
            |--------------------------------------------------------------------------
            */

            if (
                !empty($oldTechnicians)
            ) {

                TicketActivity::create([
                    'ticket_id' =>
                        $ticket->id,

                    'user_id' =>
                        Auth::id(),

                    'action' =>
                        'Assignment Cleared',

                    'old_value' =>
                        implode(
                            ', ',
                            $oldTechnicians
                        ),

                    'new_value' =>
                        'Unassigned',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Status Reset Activity
            |--------------------------------------------------------------------------
            */

            if (
                $oldStatus !== 'New'
            ) {

                TicketActivity::create([
                    'ticket_id' =>
                        $ticket->id,

                    'user_id' =>
                        Auth::id(),

                    'action' =>
                        'Status Changed',

                    'old_value' =>
                        $oldStatus,

                    'new_value' =>
                        'New',
                ]);
            }
        }

        /*
         * Redirect to dashboard because a Branch Admin
         * transferring to another branch no longer has
         * permission to view that ticket.
         */
        return redirect()
            ->route('dashboard')
            ->with(
                'success',
                'Ticket transferred successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Ticket
    |--------------------------------------------------------------------------
    */

    public function cancel(
        Ticket $ticket
    ) {
        $this->abortUnlessAllowed($ticket);

        $user = Auth::user();

        if (
            !in_array(
                $user->role,
                [
                    'super_admin',
                    'branch_admin'
                ]
            )
        ) {
            abort(403);
        }

        if (
            in_array(
                $ticket->status,
                [
                    'Closed',
                    'Cancelled'
                ]
            )
        ) {

            return redirect()
                ->route(
                    'dashboard.tickets.show',
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
                Auth::id(),

            'action' =>
                'Status Changed',

            'old_value' =>
                $oldStatus,

            'new_value' =>
                'Cancelled',
        ]);

        return redirect()
            ->route(
                'dashboard.tickets.show',
                $ticket
            )
            ->with(
                'success',
                'Ticket cancelled.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Merge Duplicate Ticket
    |--------------------------------------------------------------------------
    */

    public function merge(Request $request, Ticket $ticket)
{
    $this->abortUnlessAllowed($ticket);

    $user = Auth::user();

    if (!in_array($user->role, ['super_admin', 'branch_admin'])) {
        abort(403);
    }

    /*
     * The NEW recurring ticket is the one being merged.
     * It must still be an active ticket.
     */
    if (in_array($ticket->status, ['Closed', 'Cancelled', 'Merged'])) {
        return redirect()
            ->route('dashboard.tickets.show', $ticket)
            ->with(
                'error',
                'This ticket cannot be merged.'
            );
    }

    $validated = $request->validate([
        'main_ticket_id' => [
            'required',
            'exists:tickets,id',
        ],
    ]);

    /*
     * This is the OLD/original ticket.
     */
    $mainTicket = Ticket::findOrFail(
        $validated['main_ticket_id']
    );

    /*
     * Cannot merge a ticket into itself.
     */
    if ($mainTicket->id === $ticket->id) {
        return back()->with(
            'error',
            'A ticket cannot be merged into itself.'
        );
    }

    /*
     * Branch Admin can only merge tickets
     * from their own branch.
     */
    if (
        $user->role === 'branch_admin'
        &&
        $mainTicket->branch_id !== $user->branch_id
    ) {
        abort(403);
    }

    /*
     * The old ticket should normally be
     * Resolved or Closed.
     */
    if (!in_array($mainTicket->status, ['Resolved', 'Closed'])) {
        return back()->with(
            'error',
            'Please select a previously resolved or closed ticket.'
        );
    }

    /*
     * Cannot merge into a cancelled or
     * already merged ticket.
     */
    if (in_array($mainTicket->status, ['Cancelled', 'Merged'])) {
        return back()->with(
            'error',
            'The selected previous ticket cannot be reopened.'
        );
    }

    $newTicketOldStatus = $ticket->status;
    $mainTicketOldStatus = $mainTicket->status;

    /*
     * ---------------------------------------------------------
     * Reopen the OLD/original ticket
     * ---------------------------------------------------------
     */

    $mainTicket->status = 'Reopened';
    $mainTicket->resolved_at = null;
    $mainTicket->closed_at = null;
    $mainTicket->escalated_at = null;

    $mainTicket->save();

    /*
     * Record that the issue occurred again.
     *
     * We preserve the new ticket's subject and description
     * in the original ticket history.
     */
    TicketActivity::create([
        'ticket_id' => $mainTicket->id,
        'user_id' => Auth::id(),
        'action' => 'Recurring Issue Reported',
        'old_value' => $mainTicketOldStatus,
        'new_value' =>
            $ticket->ticket_number .
            ' - ' .
            $ticket->subject,
    ]);

    TicketActivity::create([
        'ticket_id' => $mainTicket->id,
        'user_id' => Auth::id(),
        'action' => 'Status Changed',
        'old_value' => $mainTicketOldStatus,
        'new_value' => 'Reopened',
    ]);

    /*
     * Add the new ticket's description as an
     * internal note on the reopened original ticket.
     */
    TicketComment::create([
        'ticket_id' => $mainTicket->id,
        'user_id' => Auth::id(),
        'sender_type' => 'staff',
        'body' =>
            'Recurring issue reported through ticket ' .
            $ticket->ticket_number .
            ":\n\n" .
            $ticket->description,
        'is_public' => false,
    ]);

    /*
     * ---------------------------------------------------------
     * Move attachments from the NEW ticket to the OLD ticket
     * ---------------------------------------------------------
     */

    TicketAttachment::where(
        'ticket_id',
        $ticket->id
    )->update([
        'ticket_id' => $mainTicket->id,
    ]);

    /*
     * ---------------------------------------------------------
     * Move requester/staff chat from NEW ticket to OLD ticket
     * ---------------------------------------------------------
     */

    TicketComment::where(
        'ticket_id',
        $ticket->id
    )->update([
        'ticket_id' => $mainTicket->id,
    ]);

    /*
     * ---------------------------------------------------------
     * Mark the NEW ticket as merged
     * ---------------------------------------------------------
     */

    $ticket->status = 'Merged';
    $ticket->merged_into_ticket_id = $mainTicket->id;
    $ticket->merged_at = now();

    /*
     * New duplicate/recurring ticket is no longer
     * independently assigned.
     */
    $ticket->assigned_to = null;

    $ticket->save();

    $ticket->technicians()->detach();

    /*
     * Activity on the new merged ticket.
     */
    TicketActivity::create([
        'ticket_id' => $ticket->id,
        'user_id' => Auth::id(),
        'action' => 'Ticket Merged',
        'old_value' => $newTicketOldStatus,
        'new_value' => $mainTicket->ticket_number,
    ]);

    /*
     * Activity on original/reopened ticket.
     */
    TicketActivity::create([
        'ticket_id' => $mainTicket->id,
        'user_id' => Auth::id(),
        'action' => 'Recurring Ticket Merged',
        'old_value' => $ticket->ticket_number,
        'new_value' => $mainTicket->ticket_number,
    ]);

    return redirect()
        ->route(
            'dashboard.tickets.show',
            $mainTicket
        )
        ->with(
            'success',
            $ticket->ticket_number .
            ' was merged into ' .
            $mainTicket->ticket_number .
            '. The original ticket has been reopened.'
        );
}
}
