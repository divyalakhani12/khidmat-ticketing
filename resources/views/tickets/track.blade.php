<!DOCTYPE html>
<html>
<head>
    <title>Track Ticket {{ $ticket->ticket_number }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6f8;
            margin: 0;
        }

        .container {
            max-width: 700px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        .row {
            margin-bottom: 12px;
        }

        .label {
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Ticket {{ $ticket->ticket_number }}</h1>

    <div class="row">
        <span class="label">Subject:</span>
        {{ $ticket->subject }}
    </div>

    <div class="row">
        <span class="label">Status:</span>
        {{ $ticket->status }}
    </div>

    <div class="row">
        <span class="label">Priority:</span>
        {{ $ticket->priority }}
    </div>

    <div class="row">
        <span class="label">Submitted:</span>
        {{ $ticket->created_at }}
    </div>

    <h2>Attachments</h2>

    @forelse ($ticket->attachments as $attachment)
        <div class="row">
            <a href="{{ route('tickets.track.attachments.download', [$ticket, $attachment]) }}">
                {{ $attachment->original_name }}
            </a>
        </div>
    @empty
        <p>No attachments.</p>
    @endforelse

    <h2>Updates</h2>

    @forelse ($updates as $update)
        <div class="row">
            {{ $update->old_value }} &rarr; {{ $update->new_value }}
            <br>
            <small>{{ $update->created_at }}</small>
        </div>
    @empty
        <p>No updates yet. We'll notify you when there is progress.</p>
    @endforelse

        <h2>Messages from IT Support</h2>

    @forelse ($comments as $comment)
        <div class="row">
            {{ $comment->body }}
            <br>
            <small>{{ $comment->created_at }}</small>
        </div>
    @empty
        <p>No messages yet.</p>
    @endforelse

</div>

</body>
</html>