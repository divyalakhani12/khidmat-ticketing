<!DOCTYPE html>
<html>
<head>
    <title>Ticket Details</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6f8;
            margin: 0;
        }

        .container {
            max-width: 850px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        .row {
            margin-bottom: 16px;
        }

        .label {
            font-weight: bold;
        }

        select {
            padding: 8px;
            margin-top: 8px;
        }

        button {
            padding: 8px 14px;
            margin-left: 8px;
            cursor: pointer;
        }

        .success {
            color: green;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

<div class="container">

    <a href="{{ route('dashboard') }}">
        ← Back to Dashboard
    </a>

    <h1>{{ $ticket->ticket_number }}</h1>

    @if (in_array(auth()->user()->role, ['super_admin', 'branch_admin']))

    <hr>

    <h3>Assign Ticket</h3>

    <form method="POST"
          action="{{ route('dashboard.tickets.assign', $ticket) }}">

        @csrf

        <select name="assigned_to" required>

            <option value="">
                Select IT Support Officer
            </option>

            @foreach ($officers as $officer)

                <option
                    value="{{ $officer->id }}"
                    @selected($ticket->assigned_to == $officer->id)
                >
                    {{ $officer->name }}
                </option>

            @endforeach

        </select>

        <button type="submit">
            Assign Ticket
        </button>

    </form>

@endif

    @if (session('success'))
        <div class="success">
            {{ session('success') }}
        </div>
    @endif

    <div class="row">
        <span class="label">Requester:</span>
        {{ $ticket->requester_name }}
    </div>

    <div class="row">
        <span class="label">Email:</span>
        {{ $ticket->requester_email }}
    </div>

    <div class="row">
        <span class="label">Contact Number:</span>
        {{ $ticket->contact_number ?? 'Not provided' }}
    </div>

    <div class="row">
        <span class="label">Subject:</span>
        {{ $ticket->subject }}
    </div>

    <div class="row">
        <span class="label">Description:</span>
        <p>{{ $ticket->description }}</p>
    </div>

    <div class="row">
        <span class="label">Priority:</span>
        {{ $ticket->priority }}
    </div>

    <div class="row">
        <span class="label">Current Status:</span>
        {{ $ticket->status }}
    </div>

    <div class="row">
    <span class="label">Assigned To:</span>

    @if ($ticket->assigned_to)
        {{ \App\Models\User::find($ticket->assigned_to)->name }}
    @else
        Not assigned
    @endif
</div>

    <div class="row">
        <span class="label">Submitted:</span>
        {{ $ticket->created_at }}
    </div>

    <hr>

    <h3>Update Ticket Status</h3>

    <form method="POST"
          action="{{ route('dashboard.tickets.status', $ticket) }}">

        @csrf

        <select name="status">
            <option value="New"
                @selected($ticket->status === 'New')>
                New
            </option>

            <option value="Assigned"
                @selected($ticket->status === 'Assigned')>
                Assigned
            </option>

            <option value="In Progress"
                @selected($ticket->status === 'In Progress')>
                In Progress
            </option>

            <option value="On Hold"
                @selected($ticket->status === 'On Hold')>
                On Hold
            </option>

            <option value="Resolved"
                @selected($ticket->status === 'Resolved')>
                Resolved
            </option>

            <option value="Closed"
                @selected($ticket->status === 'Closed')>
                Closed
            </option>

            <option value="Reopened"
                @selected($ticket->status === 'Reopened')>
                Reopened
            </option>
        </select>

        <button type="submit">
            Update Status
        </button>

    </form>

    <hr>

<h3>Activity History</h3>

@if ($activities->isEmpty())

    <p>No activity recorded yet.</p>

@else

    @foreach ($activities as $activity)

        <div style="
            padding: 12px;
            margin-bottom: 10px;
            background: #f5f6f8;
            border-radius: 6px;
        ">

            <strong>{{ $activity->action }}</strong>

            <br>

            @if ($activity->old_value || $activity->new_value)
                {{ $activity->old_value ?? '-' }}
                →
                {{ $activity->new_value ?? '-' }}
            @endif

            <br>

            <small>
                By:
                {{ $activity->user?->name ?? 'System' }}
                |
                {{ $activity->created_at }}
            </small>

        </div>

    @endforeach

@endif

</div>

</body>
</html>