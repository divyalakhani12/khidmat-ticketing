<h2>IT Ticket Submitted Successfully</h2>

<p>Dear {{ $ticket->requester_name }},</p>

<p>Your IT support request has been submitted successfully.</p>

<p>
    <strong>Ticket ID:</strong> {{ $ticket->ticket_number }}<br>
    <strong>Subject:</strong> {{ $ticket->subject }}<br>
    <strong>Priority:</strong> {{ $ticket->priority }}<br>
    <strong>Status:</strong> {{ $ticket->status }}
</p>

<p>You can track your ticket using the link below:</p>

<p>
    <a href="{{ route('tickets.track', $ticket->tracking_token) }}">
        Track Your Ticket
    </a>
</p>

<p>Regards,<br>Zindagi Trust IT Support</p>