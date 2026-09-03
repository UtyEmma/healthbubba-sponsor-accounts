<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $report['title'] }} — {{ $workspace->name }}</title>
    <style>
        body { font-family: Inter, Arial, sans-serif; color: #18201f; margin: 32px; font-size: 12px; }
        header { display: flex; justify-content: space-between; align-items: end; border-bottom: 1px solid #dfe4e3; padding-bottom: 16px; margin-bottom: 24px; }
        h1 { margin: 0; font-size: 24px; } p { margin: 4px 0 0; color: #66706f; }
        table { width: 100%; border-collapse: collapse; } th, td { border-bottom: 1px solid #e8eceb; padding: 10px 8px; text-align: left; }
        th { color: #66706f; font-size: 10px; text-transform: uppercase; }
        @media print { body { margin: 0; } .print-action { display: none; } }
    </style>
</head>
<body>
    <header>
        <div><h1>{{ $report['title'] }}</h1><p>{{ $workspace->name }} · Generated {{ now()->format('j M Y, g:i A') }}</p></div>
        <button class="print-action" onclick="window.print()">Print / Save PDF</button>
    </header>
    <table>
        <thead><tr>@foreach ($report['headers'] as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse ($report['rows'] as $row)
            <tr>@foreach ($row as $cell)<td>{{ $cell ?? '—' }}</td>@endforeach</tr>
        @empty
            <tr><td colspan="{{ count($report['headers']) }}">No records are available.</td></tr>
        @endforelse
        </tbody>
    </table>
    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
