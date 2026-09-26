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

</div>

</body>
</html>