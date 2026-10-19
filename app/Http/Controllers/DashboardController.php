<?php

namespace App\Http\Controllers;

use App\Models\TicketActivity;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TicketComment;
use App\Models\TicketAttachment;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'super_admin') {
            $tickets = Ticket::latest()->get();
        } elseif ($user->role === 'branch_admin') {
            $tickets = Ticket::where('branch_id', $user->branch_id)
                ->latest()
                ->get();
        } else {
            $tickets = Ticket::where('assigned_to', $user->id)
                ->latest()
                ->get();
        }

        return view('dashboard.index', compact('tickets'));
    }

    public function show(Ticket $ticket)
    {
        $user = Auth::user();

        if (
            $user->role === 'branch_admin' &&
            $ticket->branch_id !== $user->branch_id
        ) {
            abort(403);
        }

        if (
            $user->role === 'it_support' &&
            $ticket->assigned_to !== $user->id
        ) {
            abort(403);
        }

        $officers = User::where('role', 'it_support')
            ->orderBy('name')
            ->get();

        $activities = $ticket->activities()
            ->with('user')
            ->latest()
            ->get();
        
        $comments = $ticket->comments()
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

    private function abortUnlessAllowed(Ticket $ticket): void
    {
        $user = Auth::user();

        if ($user->role === 'branch_admin' && $ticket->branch_id !== $user->branch_id) {
            abort(403);
        }

        if ($user->role === 'it_support' && $ticket->assigned_to !== $user->id) {
            abort(403);
        }
    }

    public function updateStatus(Request $request, Ticket $ticket)
{
    $validated = $request->validate([
        'status' => 'required|in:New,Assigned,In Progress,On Hold,Resolved,Closed,Reopened',
    ]);

    $oldStatus = $ticket->status;
    $newStatus = $validated['status'];

    if ($oldStatus !== $newStatus) {

        $ticket->status = $newStatus;
        $ticket->save();

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'Status Changed',
            'old_value' => $oldStatus,
            'new_value' => $newStatus,
        ]);
    }

    return redirect()
        ->route('dashboard.tickets.show', $ticket)
        ->with('success', 'Ticket status updated successfully.');
}

public function downloadAttachment(Ticket $ticket, TicketAttachment $attachment)
    {
        $this->abortUnlessAllowed($ticket);

        abort_unless($attachment->ticket_id === $ticket->id, 404);
        abort_unless(Storage::exists($attachment->path), 404);

        return Storage::download($attachment->path, $attachment->original_name);
    }

    public function assign(Request $request, Ticket $ticket)
{
    $user = Auth::user();

    if (!in_array($user->role, ['super_admin', 'branch_admin'])) {
        abort(403);
    }

    $validated = $request->validate([
        'assigned_to' => 'required|exists:users,id',
    ]);

    $officer = User::where('id', $validated['assigned_to'])
        ->where('role', 'it_support')
        #->where('branch_id', $ticket->branch_id)
        ->firstOrFail();

    $oldOfficer = $ticket->assigned_to
        ? User::find($ticket->assigned_to)
        : null;

    $ticket->assigned_to = $officer->id;
    $ticket->status = 'Assigned';
    $ticket->save();

    TicketActivity::create([
        'ticket_id' => $ticket->id,
        'user_id' => Auth::id(),
        'action' => 'Ticket Assigned',
        'old_value' => $oldOfficer ? $oldOfficer->name : 'Unassigned',
        'new_value' => $officer->name,
    ]);

    return redirect()
        ->route('dashboard.tickets.show', $ticket)
        ->with('success', 'Ticket assigned successfully.');
}
}