<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; }
        table { width:100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #777; padding: 6px 8px; }
        th { background: #f5f5f5; }
        .totals { margin-top: 25px; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Attendance Report</h2>

    <p><strong>Period:</strong> {{ $period['from'] }} — {{ $period['to'] }}</p>
    <p><strong>Term:</strong> {{ $term_name }}</p>
    <p><strong>Class:</strong> {{ $class_name }}</p>

    <table>
        <thead>
            <tr>
                <th>Student Code</th>
                <th>Name</th>
                <th>Class</th>
                <th>Present</th>
                <th>Absent</th>
                <th>Permission</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['student_code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['class_name'] }}</td>
                    <td>{{ $row['present'] }}</td>
                    <td>{{ $row['absent'] }}</td>
                    <td>{{ $row['permission'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <p>Total Present: {{ $totals['present'] }}</p>
        <p>Total Absent: {{ $totals['absent'] }}</p>
        <p>Total Permission: {{ $totals['permission'] }}</p>
    </div>
</body>
</html>