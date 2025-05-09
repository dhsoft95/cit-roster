{{-- Save this at: resources/views/pdfs/daily-roster.blade.php --}}

    <!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Roster - {{ $date->format('F d, Y') }}</title>
    <style>
        @page {
            margin: 20px;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
            position: relative;
        }
        /* Watermark styling */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(200, 200, 200, 0.2);
            z-index: -1000;
            width: 100%;
            text-align: center;
        }
        /* Header styling */
        .header {
            padding: 15px 0;
            margin-bottom: 20px;
            border-bottom: 2px solid #4a5568;
            display: table;
            width: 100%;
        }
        .logo-container {
            display: table-cell;
            width: 20%;
            vertical-align: middle;
        }
        .logo {
            max-width: 120px;
            max-height: 60px;
        }
        .header-text {
            display: table-cell;
            width: 80%;
            vertical-align: middle;
            text-align: right;
        }
        h1 {
            color: #2d3748;
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        h2 {
            color: #4a5568;
            margin: 5px 0 0 0;
            font-size: 16px;
            font-weight: normal;
        }
        /* Info box styling */
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .info-box p {
            margin: 5px 0;
        }
        .info-box strong {
            color: #2d3748;
        }
        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        thead tr {
            background-color: #4a5568;
            color: white;
        }
        th {
            text-align: left;
            padding: 12px 10px;
            font-weight: 600;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        tbody tr:hover {
            background-color: #edf2f7;
        }
        .signature-td {
            height: 30px;
        }
        /* Vehicle section styling */
        .vehicle-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .vehicle-header {
            background-color: #4a5568;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            font-size: 14px;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
        }
        .vehicle-content {
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-top: none;
            border-bottom-left-radius: 6px;
            border-bottom-right-radius: 6px;
        }
        .vehicle-grid {
            display: table;
            width: 100%;
        }
        .vehicle-grid-cell {
            display: table-cell;
            vertical-align: top;
            padding: 0 10px;
        }
        .vehicle-grid-cell:first-child {
            padding-left: 0;
        }
        .vehicle-grid-cell:last-child {
            padding-right: 0;
        }
        .vehicle-grid-cell h4 {
            margin-top: 0;
            margin-bottom: 8px;
            color: #4a5568;
            font-size: 13px;
        }
        .crew-list {
            margin-top: 5px;
        }
        .crew-item {
            margin-bottom: 5px;
            padding-bottom: 5px;
            border-bottom: 1px dotted #e2e8f0;
        }
        .crew-item:last-child {
            border-bottom: none;
        }

        /* Signature section styling */
        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .signature-box {
            display: table-cell;
            width: 48%;
            padding: 0 1%;
            vertical-align: top;
        }
        .signature-line {
            border-bottom: 1px solid #4a5568;
            height: 50px;
            margin-bottom: 5px;
        }
        .signature-name {
            text-align: center;
            font-weight: bold;
            color: #2d3748;
        }
        .signature-title {
            text-align: center;
            font-size: 11px;
            color: #718096;
        }
        /* Footer styling */
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #718096;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
        /* Utility classes */
        .text-center {
            text-align: center;
        }
        .status-active {
            color: #48bb78;
            font-weight: bold;
        }
        .status-inactive {
            color: #e53e3e;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="watermark">CONFIDENTIAL</div>

<div class="header">
    <div class="logo-container">
        <img src="{{ public_path('images/logo.png') }}" alt="Company Logo" class="logo">
    </div>
    <div class="header-text">
        <h1>DAILY ROSTER</h1>
        <h2>{{ $date->format('l, F d, Y') }}</h2>
    </div>
</div>

<div class="info-box">
    <p><strong>Date:</strong> {{ $date->format('F d, Y') }}</p>
    <p><strong>Day:</strong> {{ $date->format('l') }}</p>

    @php
        // Get unique vehicles
        $uniqueVehicles = $entries->pluck('vehicle_id')->unique()->count();
        // Get total crew members
        $totalCrew = $entries->whereNotNull('crew_id')->count();
        // Get unique car commanders
        $uniqueCommanders = $entries->pluck('car_commander_id')->unique()->count();
    @endphp

    <p><strong>Total Vehicles Assigned:</strong> {{ $uniqueVehicles }}</p>
    <p><strong>Total Crew Members:</strong> {{ $totalCrew }}</p>
    <p><strong>Total Car Commanders:</strong> {{ $uniqueCommanders }}</p>
    <p><strong>Roster Generated:</strong> {{ now()->format('F d, Y H:i:s') }}</p>
</div>

@php
    // Group entries by vehicle for better organization
    $vehicleGroups = $entries->groupBy('vehicle_id');
@endphp

@foreach($vehicleGroups as $vehicleId => $vehicleEntries)
    @php
        // Get first entry to access vehicle and driver info
        $firstEntry = $vehicleEntries->first();
        // Get all crew members for this vehicle
        $crewAssignments = $vehicleEntries->sortBy('crew_number');
    @endphp

    <div class="vehicle-section">
        <div class="vehicle-header">
            Vehicle #{{ $loop->index + 1 }}: {{ $firstEntry->vehicle->registration_number }}
        </div>
        <div class="vehicle-content">
            <div class="vehicle-grid">
                <div class="vehicle-grid-cell" style="width: 30%;">
                    <h4>Driver</h4>
                    <div>{{ $firstEntry->driver->name }}</div>
                </div>
                <div class="vehicle-grid-cell" style="width: 70%;">
                    <h4>Crew Assignments</h4>
                    <table>
                        <thead>
                        <tr>
                            <th>Crew #</th>
                            <th>Crew Member</th>
                            <th>Car Commander</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($crewAssignments as $assignment)
                            <tr>
                                <td>{{ $assignment->crew_number }}</td>
                                <td>{{ $assignment->crew->name }}</td>
                                <td>{{ $assignment->carCommander->name }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endforeach

<div class="signature-section">
    <h3 class="text-center">APPROVALS</h3>

    <div class="signature-grid">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-name">Prepared By</div>
            <div class="signature-title">Operations Coordinator</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-name">Approved By</div>
            <div class="signature-title">Fleet Manager</div>
        </div>
    </div>

    <div class="signature-grid">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-name">Operations Officer</div>
            <div class="signature-title">Date: _______________</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-name">Commanding Officer</div>
            <div class="signature-title">Date: _______________</div>
        </div>
    </div>
</div>

<div class="footer">
    <p>This is an official document of the organization. Unauthorized alteration or reproduction is prohibited.</p>
    <p>Document ID: ROSTER-{{ $date->format('Ymd') }}-{{ strtoupper(substr(md5($date->format('Y-m-d')), 0, 6)) }}</p>
</div>
</body>
</html>
