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

    @if (session('error'))
        <div style="color: red; margin-bottom: 15px;">
            {{ session('error') }}
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
    <span class="label">Assigned Technicians:</span>

    @if ($ticket->technicians->isNotEmpty())

        @foreach ($ticket->technicians as $technician)
            <span style="
                display: inline-block;
                background: #eef2f7;
                padding: 6px 10px;
                margin: 4px 4px 4px 0;
                border-radius: 6px;
            ">
                {{ $technician->name }}
            </span>
        @endforeach

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

        <h3>Assign Technicians</h3>

<form method="POST"
      action="{{ route('dashboard.tickets.assign', $ticket) }}">

    @csrf

    <label>
        Select one or more IT Support Officers:
    </label>

    <br>

    <select
        name="technicians[]"
        multiple
        size="6"
        required
        style="
            min-width: 320px;
            margin-top: 10px;
        "
    >

        @foreach ($officers as $officer)

            <option
                value="{{ $officer->id }}"
                @selected(
                    in_array(
                        $officer->id,
                        $assignedTechnicianIds
                    )
                )
            >
                {{ $officer->name }}
            </option>

        @endforeach

    </select>

    <br><br>

    <small>
        Hold Ctrl while clicking to select multiple technicians.
    </small>

    <br><br>

    <button type="submit">
        Update Technicians
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

        <hr>

        @if (!in_array($ticket->status, ['Closed', 'Cancelled', 'Merged']))

            <h3>Merge with Previous Ticket</h3>

            <p>
                If this issue has occurred before, select the previously
                resolved or closed ticket. The previous ticket will be reopened
                and this new ticket will be merged into it.
            </p>

            <form method="POST"
                  action="{{ route('dashboard.tickets.merge', $ticket) }}"
                  onsubmit="return confirm('Merge this ticket into the selected main ticket? This action cannot be undone easily.');">

                @csrf

                <label>
                    Select the main ticket:
                </label>

                <br><br>

                <select name="main_ticket_id" required style="min-width: 420px;">
                    <option value="">-- Select Main Ticket --</option>

                    @foreach (
                        \App\Models\Ticket::where('id', '!=', $ticket->id)
                        ->where('requester_email', $ticket->requester_email)
                        ->whereIn('status', ['Resolved', 'Closed'])
                        ->when(
                            auth()->user()->role === 'branch_admin',
                            fn ($query) => $query->where(
                                'branch_id',
                                auth()->user()->branch_id
                            )
                        )
                        ->latest()
                        ->get()
                        as $mainTicket
                    )

                        <option value="{{ $mainTicket->id }}">
                            {{ $mainTicket->ticket_number }}
                            -
                            {{ $mainTicket->subject }}
                            ({{ $mainTicket->status }})
                        </option>

                    @endforeach

                </select>

                <br><br>

                <button type="submit">
                    Merge Into Selected Ticket
                </button>

            </form>

        @endif

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

    @if (
    in_array(auth()->user()->role, ['super_admin', 'branch_admin'])
    && !in_array($ticket->status, ['Closed', 'Cancelled'])
)
    <form method="POST" action="{{ route('dashboard.tickets.cancel', $ticket) }}"
          onsubmit="return confirm('Cancel this ticket?');">
        @csrf
        <button type="submit">Cancel Ticket</button>
    </form>
@endif
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

<h3>Chat with Requester</h3>

@if (!in_array($ticket->status, ['Closed', 'Cancelled']))

    <form method="POST"
          action="{{ route('dashboard.tickets.comments', $ticket) }}">

        @csrf

        <textarea
            name="body"
            rows="3"
            style="width: 100%;"
            required
            placeholder="Type a message to the requester..."
        ></textarea>

        <input
            type="hidden"
            name="is_public"
            value="1"
        >

        <br><br>

        <button type="submit">
            Send Message
        </button>

    </form>

@else

    <p>
        <em>
            Chat is disabled because this ticket is {{ strtolower($ticket->status) }}.
        </em>
    </p>

@endif


<br>

<h4>Conversation</h4>
<div id="requester-chat-messages">

@forelse (
    $comments
        ->where('is_public', true)
        ->sortBy('created_at')
    as $comment
)

    <div style="
        padding: 12px;
        margin-bottom: 10px;
        background: #eef4ff;
        border-radius: 8px;
    ">

        <strong>
            @if ($comment->sender_type === 'requester')
                Requester
            @else
                {{ $comment->user?->name ?? 'IT Support' }}
            @endif
        </strong>

        <br>

        {{ $comment->body }}

        <br>

        <small>
            {{ $comment->created_at->format('d M Y, h:i A') }}
        </small>

    </div>

@empty

    <p>No chat messages yet.</p>

@endforelse
</div>

<hr>

<h3>Internal Comments / Notes</h3>

@if (!in_array($ticket->status, ['Closed', 'Cancelled']))

    <form method="POST"
          action="{{ route('dashboard.tickets.comments', $ticket) }}">

        @csrf

        <textarea
            name="body"
            rows="3"
            style="width: 100%;"
            required
            placeholder="Add an internal note for IT staff..."
        ></textarea>

        <br>

        <label>
            <input
                type="checkbox"
                name="is_public"
                value="1"
            >
            Show to requester
        </label>

        <br><br>

        <button type="submit">
            Add Comment
        </button>

    </form>

@else

    <p>
        <em>
            Comments are disabled because this ticket is {{ strtolower($ticket->status) }}.
        </em>
    </p>

@endif


<br>

<h4>Internal Notes</h4>

@forelse (
    $comments
        ->where('is_public', false)
        ->sortBy('created_at')
    as $comment
)

    <div style="
        padding: 12px;
        margin-bottom: 10px;
        background: #fff8e1;
        border-radius: 8px;
    ">

        <strong>
            {{ $comment->user?->name ?? 'IT Support' }}
        </strong>

        <br>

        {{ $comment->body }}

        <br>

        <small>
            {{ $comment->created_at->format('d M Y, h:i A') }}
        </small>

    </div>

@empty

    <p>No internal comments yet.</p>

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
                    {{ $activity->user?->name ?? ($activity->actor_label ?? 'System') }}
                    |
                    {{ $activity->created_at }}
                </small>

            </div>

        @endforeach

    @endif

</div>

<script>
    async function refreshRequesterChat() {
        try {
            const response = await fetch(
                "{{ route('tickets.track.messages.get', $ticket) }}"
            );

            if (!response.ok) {
                return;
            }

            const messages = await response.json();

            const container =
                document.getElementById('requester-chat-messages');

            if (!container) {
                return;
            }

            if (messages.length === 0) {
                container.innerHTML =
                    '<p>No chat messages yet.</p>';
                return;
            }

            container.innerHTML = messages.map(message => {

                const sender = escapeHtml(message.sender_name);
                const body = escapeHtml(message.body);
                const time = escapeHtml(message.created_at);

                return `
                    <div style="
                        padding: 12px;
                        margin-bottom: 10px;
                        background: #eef4ff;
                        border-radius: 8px;
                    ">
                        <strong>${sender}</strong>
                        <br>
                        ${body}
                        <br>
                        <small>${time}</small>
                    </div>
                `;
            }).join('');

        } catch (error) {
            console.error('Chat refresh failed:', error);
        }
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

    setInterval(refreshRequesterChat, 3000);
</script>


</body>
</html>