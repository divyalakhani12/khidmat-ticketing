<!DOCTYPE html>
<html>
<head>
    <title>IT Dashboard</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f3f3f3;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>

<body>

<div class="header">

    <h1>IT Ticket Dashboard</h1>

    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <button type="submit">
            Logout
        </button>
    </form>

</div>

<table>

    <thead>
        <tr>
            <th>Ticket ID</th>
            <th>Requester</th>
            <th>Subject</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Created</th>
        </tr>
    </thead>

    <tbody>

        @forelse ($tickets as $ticket)

            <tr>
    <td>
        <a href="{{ route('dashboard.tickets.show', $ticket) }}">
            {{ $ticket->ticket_number }}
        </a>
    </td>

    <td>{{ $ticket->requester_name }}</td>
    <td>{{ $ticket->subject }}</td>
    <td>{{ $ticket->priority }}</td>
    <td>{{ $ticket->status }}</td>
    <td>{{ $ticket->created_at }}</td>
</tr>

        @empty

            <tr>
                <td colspan="6">
                    No tickets found.
                </td>
            </tr>

        @endforelse

    </tbody>

</table>

</body>
</html>