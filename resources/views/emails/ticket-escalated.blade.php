<h2>Overdue Ticket Requires Attention</h2>

<p>The following ticket has passed its resolution deadline and is still open.</p>

<p>
    <strong>Ticket ID:</strong> {{ $ticket->ticket_number }}<br>
    <strong>Subject:</strong> {{ $ticket->subject }}<br>
    <strong>Priority:</strong> {{ $ticket->priority }}<br>
    <strong>Status:</strong> {{ $ticket->status }}<br>
    <strong>Due:</strong> {{ $ticket->due_at }}
</p>

<p>Please review and take action.</p>

<p>Regards,<br>Zindagi Trust IT Ticketing System</p>