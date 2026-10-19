<h2>New IT Ticket Received</h2>

<p>A new IT support ticket has been submitted.</p>

<p>
    <strong>Ticket ID:</strong> {{ $ticket->ticket_number }}<br>
    <strong>Requester:</strong> {{ $ticket->requester_name }}<br>
    <strong>Subject:</strong> {{ $ticket->subject }}<br>
    <strong>Priority:</strong> {{ $ticket->priority }}<br>
    <strong>Status:</strong> {{ $ticket->status }}
</p>

<p>Please log in to the IT Ticketing System to review the request.</p>

<p>Regards,<br>Zindagi Trust IT Ticketing System</p>