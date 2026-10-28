<!DOCTYPE html>
<html>
<head>
    <title>SLA Settings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; color: #222; }
        .container { max-width: 700px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f3f3f3; }
        input { width: 90px; padding: 6px; }
        .success { color: green; margin-top: 15px; }
        .actions { margin-top: 20px; }
        button {
            padding: 9px 18px; border: none; border-radius: 6px;
            background: #2563eb; color: white; cursor: pointer;
        }
        a { color: #2563eb; }
    </style>
</head>
<body>

<div class="container">

    <a href="{{ route('dashboard') }}">&larr; Back to Dashboard</a>

    <h1>SLA Settings</h1>
    <p>Set the target hours for each priority level.</p>

    @if (session('success'))
        <p class="success">{{ session('success') }}</p>
    @endif

    @if ($errors->any())
        <ul style="color:red;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('dashboard.sla.update') }}">
        @csrf

        <table>
            <thead>
                <tr>
                    <th>Priority</th>
                    <th>Response (hours)</th>
                    <th>Resolution (hours)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($settings as $sla)
                    <tr>
                        <td>{{ $sla->priority }}</td>
                        <td>
                            <input type="number" min="1"
                                name="settings[{{ $sla->id }}][response_hours]"
                                value="{{ $sla->response_hours }}">
                        </td>
                        <td>
                            <input type="number" min="1"
                                name="settings[{{ $sla->id }}][resolution_hours]"
                                value="{{ $sla->resolution_hours }}">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="actions">
            <button type="submit">Save Settings</button>
        </div>
    </form>

</div>

</body>
</html>