<html>
<head>
    <title>IT Dashboard</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background: #ffffff;
            color: #222;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .header h1 {
            margin: 0;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f5f6f8;
            padding: 10px 16px;
            border-radius: 10px;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #222;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }

        .user-name {
            font-weight: bold;
            font-size: 16px;
        }

        .user-meta {
            font-size: 13px;
            color: #666;
            margin-top: 2px;
        }

        .logout-button {
            padding: 10px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 7px;
            cursor: pointer;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 15px;
            margin-top: 25px;
        }

        .card {
            background: #f5f6f8;
            padding: 18px;
            border-radius: 8px;
            text-align: center;
        }

        .card h2 {
            margin: 0;
            font-size: 28px;
        }

        .card p {
            margin: 6px 0 0;
            color: #555;
        }

        .overdue-card {
            background: #fff0f0;
            border: 1px solid #f1b8b8;
        }

        .overdue-card h2,
        .overdue-card p {
            color: #b42318;
        }

        .filters {
            margin-top: 25px;
            padding: 20px;
            background: #f5f6f8;
            border-radius: 8px;
        }

        .filters form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .filters input,
        .filters select {
            padding: 9px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .filters input {
            min-width: 280px;
        }

        .filters button {
            padding: 9px 14px;
            border: none;
            border-radius: 6px;
            background: #222;
            color: white;
            cursor: pointer;
        }

        .filters a {
            padding: 9px 14px;
            text-decoration: none;
            color: #333;
            background: white;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #f3f3f3;
        }

        td a {
            color: #1769aa;
            text-decoration: none;
            font-weight: 600;
        }

        td a:hover {
            text-decoration: underline;
        }

        .overdue-badge {
            display: inline-block;
            margin-top: 4px;
            padding: 4px 8px;
            border-radius: 6px;
            background: #ffe5e5;
            color: #b42318;
            font-size: 11px;
            font-weight: bold;
        }

        .no-tickets {
            text-align: center;
            color: #666;
            padding: 25px;
        }

        @media (max-width: 1100px) {
            .cards {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 700px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>

<body>

<div class="header">

    <h1>IT Ticket Dashboard</h1>

    <div class="header-right">

        <div class="user-info">

            <div class="user-avatar">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>

            <div>

                <div class="user-name">
                    {{ auth()->user()->name }}
                </div>

                <div class="user-meta">

                    @if (auth()->user()->role === 'super_admin')

                        Super Admin · All Branches

                    @elseif (auth()->user()->role === 'branch_admin')

                        Branch Admin ·
                        {{ \App\Models\Branch::find(auth()->user()->branch_id)?->name ?? 'No Branch' }}

                    @else

                        IT Support ·
                        {{ \App\Models\Branch::find(auth()->user()->branch_id)?->name ?? 'No Branch' }}

                    @endif

                </div>

            </div>

        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button
                type="submit"
                class="logout-button"
            >
                Logout
            </button>
        </form>

    </div>

</div>


{{-- Dashboard Summary Cards --}}

<div class="cards">

    <div class="card">
        <h2>{{ $totalCount }}</h2>
        <p>Total Tickets</p>
    </div>

    <div class="card">
        <h2>{{ $newCount }}</h2>
        <p>New</p>
    </div>

    <div class="card">
        <h2>{{ $inProgressCount }}</h2>
        <p>In Progress</p>
    </div>

    <div class="card">
        <h2>{{ $resolvedCount }}</h2>
        <p>Resolved</p>
    </div>

    <div class="card">
        <h2>{{ $closedCount }}</h2>
        <p>Closed</p>
    </div>

    <div class="card overdue-card">
        <h2>{{ $overdueCount }}</h2>
        <p>Overdue</p>
    </div>

</div>


{{-- Filters --}}

<div class="filters">

    <form method="GET" action="{{ route('dashboard') }}">

        <input
            type="text"
            name="search"
            placeholder="Search Ticket ID, requester, email or subject"
            value="{{ request('search') }}"
        >


        @if (auth()->user()->role === 'super_admin')

            <select name="branch_id">

                <option value="">
                    All Branches
                </option>

                @foreach ($branches as $branch)

                    <option
                        value="{{ $branch->id }}"
                        @selected(request('branch_id') == $branch->id)
                    >
                        {{ $branch->name }}
                    </option>

                @endforeach

            </select>

        @endif


        <select name="category_id">

            <option value="">
                All Categories
            </option>

            @foreach ($categories as $category)

                <option
                    value="{{ $category->id }}"
                    @selected(request('category_id') == $category->id)
                >
                    {{ $category->name }}
                </option>

            @endforeach

        </select>


        <select name="priority">

            <option value="">
                All Priorities
            </option>

            <option
                value="Low"
                @selected(request('priority') === 'Low')
            >
                Low
            </option>

            <option
                value="Medium"
                @selected(request('priority') === 'Medium')
            >
                Medium
            </option>

            <option
                value="High"
                @selected(request('priority') === 'High')
            >
                High
            </option>

            <option
                value="Critical"
                @selected(request('priority') === 'Critical')
            >
                Critical
            </option>

        </select>


        <select name="status">

            <option value="">
                All Statuses
            </option>

            <option
                value="New"
                @selected(request('status') === 'New')
            >
                New
            </option>

            <option
                value="Assigned"
                @selected(request('status') === 'Assigned')
            >
                Assigned
            </option>

            <option
                value="In Progress"
                @selected(request('status') === 'In Progress')
            >
                In Progress
            </option>

            <option
                value="On Hold"
                @selected(request('status') === 'On Hold')
            >
                On Hold
            </option>

            <option
                value="Resolved"
                @selected(request('status') === 'Resolved')
            >
                Resolved
            </option>

            <option
                value="Closed"
                @selected(request('status') === 'Closed')
            >
                Closed
            </option>

            <option
                value="Reopened"
                @selected(request('status') === 'Reopened')
            >
                Reopened
            </option>

        </select>


        @if (auth()->user()->role !== 'it_support')

            <select name="assigned_to">

                <option value="">
                    All Officers
                </option>

                @foreach ($officers as $officer)

                    <option
                        value="{{ $officer->id }}"
                        @selected(request('assigned_to') == $officer->id)
                    >
                        {{ $officer->name }}
                    </option>

                @endforeach

            </select>

        @endif


        <button type="submit">
            Apply Filters
        </button>

        <a href="{{ route('dashboard') }}">
            Reset
        </a>

    </form>

</div>


{{-- Ticket Table --}}

<table>

    <thead>

        <tr>
            <th>Ticket ID</th>
            <th>Requester</th>
            <th>Subject</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Created</th>
            <th>Due</th>
        </tr>

    </thead>

    <tbody>

        @forelse ($tickets as $ticket)

            @php

                $isOverdue =
                    $ticket->due_at &&
                    $ticket->due_at->isPast() &&
                    !in_array(
                        $ticket->status,
                        ['Resolved', 'Closed']
                    );

            @endphp

            <tr>

                <td>

                    <a
                        href="{{ route('dashboard.tickets.show', $ticket) }}"
                    >
                        {{ $ticket->ticket_number }}
                    </a>

                </td>

                <td>
                    {{ $ticket->requester_name }}
                </td>

                <td>
                    {{ $ticket->subject }}
                </td>

                <td>
                    {{ $ticket->priority }}
                </td>

                <td>
                    {{ $ticket->status }}
                </td>

                <td>
                    {{ $ticket->created_at->format('d M Y, h:i A') }}
                </td>

                <td>

                    @if ($ticket->due_at)

                        {{ $ticket->due_at->format('d M Y, h:i A') }}

                        @if ($isOverdue)

                            <br>

                            <span class="overdue-badge">
                                OVERDUE
                            </span>

                        @endif

                    @else

                        -

                    @endif

                </td>

            </tr>

        @empty

            <tr>

                <td
                    colspan="7"
                    class="no-tickets"
                >
                    No tickets found.
                </td>

            </tr>

        @endforelse

    </tbody>

</table>

</body>
</html>