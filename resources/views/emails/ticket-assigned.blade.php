<h2>IT Ticket Assigned</h2>

<p>Dear {{ $officer->name }},</p>

<p>An IT support ticket has been assigned to you.</p>

<p>
    <strong>Ticket ID:</strong> {{ $ticket->ticket_number }}<br>
    <strong>Requester:</strong> {{ $ticket->requester_name }}<br>
    <strong>Subject:</strong> {{ $ticket->subject }}<br>
    <strong>Priority:</strong> {{ $ticket->priority }}<br>
    <strong>Status:</strong> {{ $ticket->status }}
</p>

<p>Please log in to the IT Ticketing System to review the ticket.</p>

<p>Regards,<br>Zindagi Trust IT Ticketing System</p>