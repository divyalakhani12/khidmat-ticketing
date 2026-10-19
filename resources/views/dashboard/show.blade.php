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
        <span class="label">Assigned To:</span>

        @if ($ticket->assigned_to)
            {{ \App\Models\User::find($ticket->assigned_to)?->name ?? 'Unknown user' }}
        @else
            Not assigned
        @endif
    </div>

    <div class="row">
        <span class="label">Submitted:</span>
        {{ $ticket->created_at }}
    </div>

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

        <hr>

        <h3>Update Priority</h3>

        <form method="POST"
              action="{{ route('dashboard.tickets.priority', $ticket) }}">

            @csrf

            <select name="priority">
                <option value="Low"
                    @selected($ticket->priority === 'Low')>
                    Low
                </option>

                <option value="Medium"
                    @selected($ticket->priority === 'Medium')>
                    Medium
                </option>

                <option value="High"
                    @selected($ticket->priority === 'High')>
                    High
                </option>

                <option value="Critical"
                    @selected($ticket->priority === 'Critical')>
                    Critical
                </option>
            </select>

            <button type="submit">
                Update Priority
            </button>

        </form>

        <hr>

        <h3>Transfer Branch</h3>

        <form method="POST"
              action="{{ route('dashboard.tickets.transfer', $ticket) }}">

            @csrf

            <select name="branch_id" required>

                @foreach (\App\Models\Branch::where('is_active', true)->get() as $branch)

                    <option
                        value="{{ $branch->id }}"
                        @selected($ticket->branch_id == $branch->id)
                    >
                        {{ $branch->name }}
                    </option>

                @endforeach

            </select>

            <button type="submit">
                Transfer Ticket
            </button>

        </form>

    @endif

    <hr>

    <h3>Update Ticket Status</h3>

    <form method="POST"
          action="{{ route('dashboard.tickets.status', $ticket) }}">

        @csrf

        <select name="status">

            @if (auth()->user()->role === 'it_support')

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

            @else

                <option value="New"
                    @selected($ticket->status === 'New')>
                    New
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

            @endif

        </select>

        <button type="submit">
            Update Status
        </button>

    </form>

    <hr>

    <h3>Attachments</h3>

    @forelse ($ticket->attachments as $attachment)

        <div class="row">
            <a href="{{ route('dashboard.tickets.attachments.download', [$ticket, $attachment]) }}">
                {{ $attachment->original_name }}
            </a>

            <small>
                ({{ number_format($attachment->size / 1024, 0) }} KB)
            </small>
        </div>

    @empty

        <p>No attachments.</p>

    @endforelse

    <hr>

    <h3>Comments</h3>

    <form method="POST"
          action="{{ route('dashboard.tickets.comments', $ticket) }}">

        @csrf

        <textarea
            name="body"
            rows="3"
            style="width: 100%;"
            required
        >{{ old('body') }}</textarea>

        <label>
            <input
                type="checkbox"
                name="is_public"
                value="1"
            >
            Show to requester
        </label>

        <button type="submit">
            Add Comment
        </button>

    </form>

    @forelse ($comments as $comment)

        <div class="row">

            <strong>
                {{ $comment->user->name }}
            </strong>

            @if ($comment->is_public)
                (visible to requester)
            @else
                (internal)
            @endif

            <br>

            {{ $comment->body }}

            <br>

            <small>
                {{ $comment->created_at }}
            </small>

        </div>

    @empty

        <p>No comments yet.</p>

    @endforelse

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

                <strong>
                    {{ $activity->action }}
                </strong>

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