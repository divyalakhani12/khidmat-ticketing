<?php

namespace App\Http\Controllers;
use App\Models\Ticket;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $tickets = Ticket::latest()->get();

        return view('dashboard.index', compact('tickets'));
    }
    public function updateStatus(Request $request, Ticket $ticket)
{
    $validated = $request->validate([
        'status' => 'required|in:New,Assigned,In Progress,On Hold,Resolved,Closed,Reopened',
    ]);

    $ticket->status = $validated['status'];
    $ticket->save();

    return redirect()
        ->route('dashboard.tickets.show', $ticket)
        ->with('success', 'Ticket status updated successfully.');
}
public function show(Ticket $ticket)
{
    return view('dashboard.show', compact('ticket'));
}
}