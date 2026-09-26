<!DOCTYPE html>
<html>
<head>
    <title>Ticket Submitted</title>
</head>

<body>

    <h1>Ticket Submitted Successfully</h1>

    <p>Your ticket number is:</p>

    <h2>{{ $ticket->ticket_number }}</h2>

    <p>
        Current Status:
        <strong>{{ $ticket->status }}</strong>
    </p>

    <a href="{{ route('tickets.reset') }}">
    Submit Another Ticket
    </a>

</body>
</html>