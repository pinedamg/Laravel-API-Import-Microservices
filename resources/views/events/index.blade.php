<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events List</title>
    <style>
        body { font-family: sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Events List</h1>

    @if($events->isEmpty())
        <p>No events found.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Base Plan ID</th>
                    <th>Plan ID</th>
                    <th>Title</th>
                    <th>Starts At</th>
                    <th>Ends At</th>
                    <th>Min Price</th>
                    <th>Max Price</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($events as $event)
                    <tr>
                        <td>{{ $event->id }}</td>
                        <td>{{ $event->base_plan_id }}</td>
                        <td>{{ $event->plan_id }}</td>
                        <td>{{ $event->title }}</td>
                        <td>{{ $event->starts_at }}</td>
                        <td>{{ $event->ends_at }}</td>
                        <td>{{ $event->min_price }}</td>
                        <td>{{ $event->max_price }}</td>
                        <td>{{ $event->status }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
