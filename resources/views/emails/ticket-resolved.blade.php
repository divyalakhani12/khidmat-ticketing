<h2>Your IT Ticket Has Been Resolved</h2>

<p>Dear {{ $ticket->requester_name }},</p>

<p>Your IT support ticket has been marked as resolved.</p>

<p>
    <strong>Ticket ID:</strong> {{ $ticket->ticket_number }}<br>
    <strong>Subject:</strong> {{ $ticket->subject }}<br>
    <strong>Status:</strong> {{ $ticket->status }}
</p>

<p>Please use your ticket tracking link to review the latest updates.</p>

<p>
    <a href="{{ route('tickets.track', $ticket->tracking_token) }}">
        View Ticket
    </a>
</p>

<p>Regards,<br>Zindagi Trust IT Support</p>