<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Department;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
        "regex:/^[A-Za-z\s'-]+$/"
    ],

    'requester_email' => [
    'required',
    'email',
    'max:255',
    'regex:/^[A-Za-z0-9._%+-]+@zindagitrust\.org$/i'
    ],

    [
    'requester_email.regex' => 'Please enter a valid Zindagi Trust email address ending in @zindagitrust.org.',
    ],

    'contact_number' => [
        'nullable',
        'regex:/^[0-9+\-\s()]{7,20}$/'
    ],

    'department_id' => [
        'nullable',
        'exists:departments,id'
    ],

    'branch_id' => [
        'required',
        'exists:branches,id'
    ],

    'category_id' => [
        'required',
        'exists:categories,id'
    ],

    'priority' => [
        'required',
        'in:Low,Medium,High,Critical'
    ],

    'subject' => [
        'required',
        'string',
        'max:255'
    ],

    'description' => [
        'required',
        'string',
        'min:10',
        'max:5000'
    ],
]);
        $ticket = Ticket::create([
            ...$validated,
            'ticket_number' => 'ZT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
            'tracking_token' => Str::random(64),
            'status' => 'New',
        ]);
        session(['ticket_submitted' => true]);

        return redirect()->route('tickets.success', $ticket);
    }

    public function submitted()
{
    return view('tickets.submitted');
}

public function resetForm()
{
    session()->forget('ticket_submitted');

    return redirect()->route('tickets.create');
}

    public function success(Ticket $ticket)
    {
        return view('tickets.success', compact('ticket'));
    }
}