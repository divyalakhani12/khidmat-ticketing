<?php

namespace App\Http\Controllers;

use App\Mail\TicketAssignedMail;
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
    public function index(Request $request)
    {
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Ticket Query
        |--------------------------------------------------------------------------
        */

        $query = Ticket::query();

        // Role-based visibility
        if ($user->role === 'branch_admin') {
            $query->where('branch_id', $user->branch_id);
        } elseif ($user->role === 'it_support') {
            $query->where('assigned_to', $user->id);
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%")
                    ->orWhere('requester_email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $tickets = $query
            ->latest()
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Dashboard Summary Cards
        |--------------------------------------------------------------------------
        */

        $summaryQuery = Ticket::query();

        if ($user->role === 'branch_admin') {
            $summaryQuery->where('branch_id', $user->branch_id);
        } elseif ($user->role === 'it_support') {
            $summaryQuery->where('assigned_to', $user->id);
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
            ->where('due_at', '<', now())
            ->whereNotIn('status', ['Resolved', 'Closed'])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Dropdown Data
        |--------------------------------------------------------------------------
        */

        $branches = Branch::where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        $officers = User::where('role', 'it_support')
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Dashboard View
        |--------------------------------------------------------------------------
        */

        return view('dashboard.index', compact(
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
        ));
    }

    public function show(Ticket $ticket)
    {
        $this->abortUnlessAllowed($ticket);

        $officers = User::where('role', 'it_support')
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

        return view('dashboard.show', compact(
            'ticket',
            'officers',
            'activities',
            'comments'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Comments
    |--------------------------------------------------------------------------
    */

    public function addComment(Request $request, Ticket $ticket)
    {
        $this->abortUnlessAllowed($ticket);

        $validated = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'body' => $validated['body'],
            'is_public' => $request->boolean('is_public'),
        ]);

        return redirect()
            ->route('dashboard.tickets.show', $ticket)
            ->with('success', 'Comment added.');
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization Helper
    |--------------------------------------------------------------------------
    */

    private function abortUnlessAllowed(Ticket $ticket): void
    {
        $user = Auth::user();

        if (
            $user->role === 'branch_admin'
            && $ticket->branch_id !== $user->branch_id
        ) {
            abort(403);
        }

        if (
            $user->role === 'it_support'
            && $ticket->assigned_to !== $user->id
        ) {
            abort(403);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update Ticket Status
    |--------------------------------------------------------------------------
    */

    public function updateStatus(Request $request, Ticket $ticket)
    {
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
                Mail::to($ticket->requester_email)
                    ->send(
                        new TicketResolvedMail($ticket)
                    );
            }
        }

        return redirect()
            ->route('dashboard.tickets.show', $ticket)
            ->with('success', 'Ticket status updated successfully.');
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
            $attachment->ticket_id === $ticket->id,
            404
        );

        abort_unless(
            Storage::exists($attachment->path),
            404
        );

        return Storage::download(
            $attachment->path,
            $attachment->original_name
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Assign Ticket
    |--------------------------------------------------------------------------
    */

    public function assign(Request $request, Ticket $ticket)
    {
        $this->abortUnlessAllowed($ticket);

        $user = Auth::user();

        if (!in_array(
            $user->role,
            ['super_admin', 'branch_admin']
        )) {
            abort(403);
        }

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        /*
         * Officers are intentionally NOT restricted by branch.
         * Zindagi Trust requirements allow IT officers to handle
         * tickets from multiple branches.
         */
        $officer = User::where(
                'id',
                $validated['assigned_to']
            )
            ->where(
                'role',
                'it_support'
            )
            ->firstOrFail();

        $oldOfficer = $ticket->assigned_to
            ? User::find($ticket->assigned_to)
            : null;

        $oldStatus = $ticket->status;

        $ticket->assigned_to = $officer->id;
        $ticket->status = 'Assigned';
        $ticket->save();

        /*
        |--------------------------------------------------------------------------
        | Assignment Activity
        |--------------------------------------------------------------------------
        */

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'Ticket Assigned',
            'old_value' => $oldOfficer
                ? $oldOfficer->name
                : 'Unassigned',
            'new_value' => $officer->name,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Status Activity
        |--------------------------------------------------------------------------
        */

        if ($oldStatus !== 'Assigned') {
            TicketActivity::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'action' => 'Status Changed',
                'old_value' => $oldStatus,
                'new_value' => 'Assigned',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Assignment Email
        |--------------------------------------------------------------------------
        */

        Mail::to($officer->email)
            ->send(
                new TicketAssignedMail(
                    $ticket,
                    $officer
                )
            );

        return redirect()
            ->route(
                'dashboard.tickets.show',
                $ticket
            )
            ->with(
                'success',
                'Ticket assigned successfully.'
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

        if (!in_array(
            $user->role,
            ['super_admin', 'branch_admin']
        )) {
            abort(403);
        }

        $validated = $request->validate([
            'priority' =>
                'required|in:Low,Medium,High,Critical',
        ]);

        $oldPriority = $ticket->priority;
        $newPriority = $validated['priority'];

        if ($oldPriority !== $newPriority) {
            $ticket->priority = $newPriority;
            $ticket->save();

            TicketActivity::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'action' => 'Priority Changed',
                'old_value' => $oldPriority,
                'new_value' => $newPriority,
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

        if (!in_array(
            $user->role,
            ['super_admin', 'branch_admin']
        )) {
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

        if ($ticket->branch_id != $newBranch->id) {
            $oldStatus = $ticket->status;

            $oldOfficer = $ticket->assigned_to
                ? User::find($ticket->assigned_to)
                : null;

            $ticket->branch_id = $newBranch->id;

            /*
             * When transferred, the new branch should
             * review and assign the ticket again.
             */
            $ticket->assigned_to = null;
            $ticket->status = 'New';

            $ticket->save();

            /*
            |--------------------------------------------------------------------------
            | Branch Transfer Activity
            |--------------------------------------------------------------------------
            */

            TicketActivity::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'action' => 'Branch Transferred',
                'old_value' =>
                    $oldBranch?->name ?? 'Unknown',
                'new_value' =>
                    $newBranch->name,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Assignment Cleared Activity
            |--------------------------------------------------------------------------
            */

            if ($oldOfficer) {
                TicketActivity::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => Auth::id(),
                    'action' => 'Assignment Cleared',
                    'old_value' => $oldOfficer->name,
                    'new_value' => 'Unassigned',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Status Reset Activity
            |--------------------------------------------------------------------------
            */

            if ($oldStatus !== 'New') {
                TicketActivity::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => Auth::id(),
                    'action' => 'Status Changed',
                    'old_value' => $oldStatus,
                    'new_value' => 'New',
                ]);
            }
        }

        /*
         * Important:
         * A Branch Admin loses access after transferring
         * the ticket to another branch.
         *
         * Therefore redirect to dashboard instead of
         * redirecting back to the ticket.
         */
        return redirect()
            ->route('dashboard')
            ->with(
                'success',
                'Ticket transferred successfully.'
            );
    }
}