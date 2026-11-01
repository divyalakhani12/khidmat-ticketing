<!doctype html>
<html><head><meta name="viewport" content="width=device-width, initial-scale=1"><title>Ticket Reports</title></head><body>
<h1>Ticket Reports</h1>
<p><a href="{{ route('dashboard') }}">Back to dashboard</a> · <a href="{{ route('dashboard.export') }}">Export active tickets</a></p>
<h2>By status</h2><ul>@foreach($byStatus as $label => $total)<li>{{ $label }}: {{ $total }}</li>@endforeach</ul>
<h2>By priority</h2><ul>@foreach($byPriority as $label => $total)<li>{{ $label }}: {{ $total }}</li>@endforeach</ul>
<h2>By branch</h2><ul>@foreach($byBranch as $label => $total)<li>{{ $label }}: {{ $total }}</li>@endforeach</ul>
<h2>By category</h2><ul>@foreach($byCategory as $label => $total)<li>{{ $label }}: {{ $total }}</li>@endforeach</ul>
<h2>By department</h2><ul>@foreach($byDepartment as $label => $total)<li>{{ $label }}: {{ $total }}</li>@endforeach</ul>
<h2>Operational metrics</h2><p>Overdue active tickets: {{ $overdue }}</p><p>Average first response: {{ $averageFirstResponse === null ? 'N/A' : round($averageFirstResponse / 60, 1).' hours' }}</p><p>Average resolution: {{ $averageResolution === null ? 'N/A' : round($averageResolution / 60, 1).' hours' }}</p><p>SLA compliance: {{ $slaCompliance === null ? 'N/A' : round($slaCompliance, 1).'%' }}</p>
</body></html>
