<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .info { margin-bottom: 20px; }
        table { width:100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f8f9fa; color: #555; font-weight: bold; text-transform: uppercase; font-size: 9pt; }
        .text-center { text-align: center; }
        .status-present { color: #28a745; font-weight: bold; }
        .status-absent { color: #dc3545; font-weight: bold; }
        .status-permission { color: #ffc107; font-weight: bold; }
        .totals-box { margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .totals-grid { display: table; width: 100%; }
        .totals-col { display: table-cell; width: 25%; }
        .percentage { font-size: 14pt; font-weight: bold; color: #007bff; }
    </style>
</head>
<body>
    <div class="header">
        <h2>ATTENDANCE REPORT</h2>
    </div>

    <div class="info">
        <p><strong>Period:</strong> {{ $period['from'] }} to {{ $period['to'] }}</p>
        <p><strong>Academic Year/Term:</strong> {{ $term_name }}</p>
        <p><strong>Class:</strong> {{ $class_name }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Student Code</th>
                <th>Student Name</th>
                <th class="text-center">Present</th>
                <th class="text-center">Absent</th>
                <th class="text-center">Permission</th>
                <th class="text-center">Rate (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['student_code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="text-center status-present">{{ $row['present'] }}</td>
                    <td class="text-center status-absent">{{ $row['absent'] }}</td>
                    <td class="text-center status-permission">{{ $row['permission'] }}</td>
                    <td class="text-center"><strong>{{ $row['percentage'] }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-box">
        <div class="totals-grid">
            <div class="totals-col">
                <small>TOTAL PRESENT</small><br>
                <strong>{{ $totals['present'] }}</strong>
            </div>
            <div class="totals-col">
                <small>TOTAL ABSENT</small><br>
                <strong>{{ $totals['absent'] }}</strong>
            </div>
            <div class="totals-col">
                <small>TOTAL PERMISSION</small><br>
                <strong>{{ $totals['permission'] }}</strong>
            </div>
            <div class="totals-col">
                <small>CLASS ATTENDANCE RATE</small><br>
                <span class="percentage">{{ $class_percentage }}</span>
            </div>
        </div>
    </div>
</body>
</html>