<!DOCTYPE html>
<html>
<head>
    <title>Ticket Already Submitted</title>
</head>

<body>

    <h1>Your ticket has already been submitted.</h1>

    <p>
        If you need to report another issue, you can create a new ticket.
    </p>

    <a href="{{ route('tickets.reset') }}">
        Submit Another Ticket
    </a>

</body>
</html>