<!DOCTYPE html>
<html>
<body>

    <h2>New message on ticket {{ $ticket->ticket_number }}</h2>

    <p>
        <strong>Subject:</strong>
        {{ $ticket->subject }}
    </p>

    <p>
        <strong>From:</strong>
        {{ $senderName }}
    </p>

    <p>
        <strong>Message:</strong>
    </p>

    <p>
        {{ $messageBody }}
    </p>

    @if ($recipientType === 'requester')

        <p>
            You can view the full conversation and track your ticket here:
        </p>

        <p>
            <a href="{{ route('tickets.track', ['ticket' => $ticket->tracking_token]) }}">
                Track Ticket {{ $ticket->ticket_number }}
            </a>
        </p>

    @else

        <p>
            You can view the ticket and reply to the requester here:
        </p>

        <p>
            <a href="{{ route('dashboard.tickets.show', ['ticket' => $ticket->id]) }}">
                Open Ticket {{ $ticket->ticket_number }}
            </a>
        </p>

    @endif

</body>
</html>
