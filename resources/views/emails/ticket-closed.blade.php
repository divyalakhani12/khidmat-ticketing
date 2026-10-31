<h2>Ticket Closed</h2>

<p>Dear {{ $ticket->requester_name }},</p>

<p>Your IT support request has been closed.</p>

<p>
    <strong>Ticket ID:</strong> {{ $ticket->ticket_number }}<br>
    <strong>Subject:</strong> {{ $ticket->subject }}<br>
    <strong>Status:</strong> {{ $ticket->status }}
</p>

<p>If your issue persists, please submit a new request.</p>

<p>Regards,<br>Zindagi Trust IT Support</p>