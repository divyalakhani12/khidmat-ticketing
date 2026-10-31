<!DOCTYPE html>
<html>
<head>
    <title>Track Ticket {{ $ticket->ticket_number }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6f8;
            margin: 0;
        }

        .container {
            max-width: 700px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        .row {
            margin-bottom: 12px;
        }

        .label {
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Ticket {{ $ticket->ticket_number }}</h1>

    <div class="row">
        <span class="label">Subject:</span>
        {{ $ticket->subject }}
    </div>

    <div class="row">
        <span class="label">Status:</span>
        {{ $ticket->status }}
    </div>

    @if ($ticket->status === 'Closed' && $closure)
    <div class="row">
        <span class="label">Closed:</span>
        @if ($closure->actor_label === 'Requester')
            by you (approved)
        @elseif ($closure->actor_label === 'System')
            automatically (no response within 1 month)
        @else
            by IT Support
        @endif
        on {{ $closure->created_at }}
    </div>
@endif

    <div class="row">
        <span class="label">Priority:</span>
        {{ $ticket->priority }}
    </div>

    <div class="row">
        <span class="label">Submitted:</span>
        {{ $ticket->created_at }}
    </div>

    @if (!in_array($ticket->status, ['Closed', 'Cancelled']))
    <form method="POST" action="{{ route('tickets.track.cancel', $ticket) }}"
          onsubmit="return confirm('Cancel this ticket? This cannot be undone.');">
        @csrf
        <button type="submit">Cancel This Ticket</button>
    </form>
    @endif
    
    @if ($ticket->status === 'Resolved')
    <div class="row">
        <p>IT has marked this ticket as resolved. Is your issue fixed?</p>

        <form method="POST" action="{{ route('tickets.track.approve', $ticket) }}"
              style="display:inline;"
              onsubmit="return confirm('Confirm the issue is resolved and close this ticket?');">
            @csrf
            <button type="submit">Yes, close it</button>
        </form>

        <form method="POST" action="{{ route('tickets.track.reject', $ticket) }}"
              style="display:inline;"
              onsubmit="return confirm('Reopen this ticket? IT will be notified.');">
            @csrf
            <button type="submit">No, still not fixed</button>
        </form>
    </div>
@endif

    <h2>Attachments</h2>

    @forelse ($ticket->attachments as $attachment)
        <div class="row">
            <a href="{{ route('tickets.track.attachments.download', [$ticket, $attachment]) }}">
                {{ $attachment->original_name }}
            </a>
        </div>
    @empty
        <p>No attachments.</p>
    @endforelse

    <h2>Updates</h2>

    @forelse ($updates as $update)
        <div class="row">
            {{ $update->old_value }} &rarr; {{ $update->new_value }}
            <br>
            <small>{{ $update->created_at }}</small>
        </div>
    @empty
        <p>No updates yet. We'll notify you when there is progress.</p>
    @endforelse

        <hr>

<h2>Messages</h2>

<div id="requester-chat-messages"
    style="
    background: #f5f6f8;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
">

    @forelse ($comments->sortBy('created_at') as $comment)

        @if ($comment->sender_type === 'requester')

            <div style="
                text-align: right;
                margin-bottom: 12px;
            ">

                <div style="
                    display: inline-block;
                    background: #dbeafe;
                    padding: 10px 14px;
                    border-radius: 10px;
                    max-width: 75%;
                    text-align: left;
                ">

                    <strong>You</strong>
                    <br>

                    {{ $comment->body }}

                    <br>

                    <small>
                        {{ $comment->created_at->format('d M Y, h:i A') }}
                    </small>

                </div>

            </div>

        @else

            <div style="
                text-align: left;
                margin-bottom: 12px;
            ">

                <div style="
                    display: inline-block;
                    background: #eeeeee;
                    padding: 10px 14px;
                    border-radius: 10px;
                    max-width: 75%;
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

            </div>

        @endif

    @empty

        <p>No messages yet.</p>

    @endforelse

</div>


@if (!in_array($ticket->status, ['Closed', 'Cancelled']))

    <form
        method="POST"
        action="{{ route('tickets.track.messages', $ticket) }}"
    >

        @csrf

        <textarea
            name="body"
            rows="3"
            required
            placeholder="Type your message to IT Support..."
            style="
                width: 100%;
                box-sizing: border-box;
                padding: 10px;
            "
        ></textarea>

        <br><br>

        <button type="submit">
            Send Message
        </button>

    </form>

@else

    <p>
        <em>
            Messaging is disabled because this ticket is {{ strtolower($ticket->status) }}.
        </em>
    </p>

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
                    '<p>No messages yet.</p>';
                return;
            }

            container.innerHTML = messages.map(message => {

                const body = escapeHtml(message.body);
                const time = escapeHtml(message.created_at);

                if (message.sender_type === 'requester') {
                    return `
                        <div style="
                            text-align: right;
                            margin-bottom: 12px;
                        ">
                            <div style="
                                display: inline-block;
                                background: #dbeafe;
                                padding: 10px 14px;
                                border-radius: 10px;
                                max-width: 75%;
                                text-align: left;
                            ">
                                <strong>You</strong>
                                <br>
                                ${body}
                                <br>
                                <small>${time}</small>
                            </div>
                        </div>
                    `;
                }

                return `
                    <div style="
                        text-align: left;
                        margin-bottom: 12px;
                    ">
                        <div style="
                            display: inline-block;
                            background: #eeeeee;
                            padding: 10px 14px;
                            border-radius: 10px;
                            max-width: 75%;
                        ">
                            <strong>${escapeHtml(message.sender_name)}</strong>
                            <br>
                            ${body}
                            <br>
                            <small>${time}</small>
                        </div>
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

    srefreshRequesterChat();
    setInterval(refreshRequesterChat, 3000);
</script>
</body>
</html>