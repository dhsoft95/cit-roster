{{-- Save this at: resources/views/pdfs/daily-roster-signatures.blade.php --}}

    <!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Roster Signatures - {{ $date->format('F d, Y') }}</title>
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
            max-width: 100px;
            max-height: 50px;
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
            font-size: 20px;
            font-weight: bold;
        }
        h2 {
            color: #4a5568;
            margin: 5px 0 0 0;
            font-size: 14px;
            font-weight: normal;
        }
        h3 {
            color: #2d3748;
            margin: 15px 0 10px 0;
            font-size: 16px;
            font-weight: bold;
        }
        /* Info box styling */
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .info-box p {
            margin: 5px 0;
            font-size: 11px;
        }
        .info-box strong {
            color: #2d3748;
        }
        /* Assignment section styling */
        .assignment-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .assignment-header {
            background-color: #4a5568;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            font-size: 14px;
        }
        .assignment-subheader {
            background-color: #e2e8f0;
            color: #2d3748;
            padding: 5px 15px;
            font-weight: bold;
            font-size: 12px;
            border-bottom: 1px solid #cbd5e0;
        }
        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: none;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background-color: #f8fafc;
            color: #4a5568;
            text-align: left;
            padding: 10px 15px;
            font-weight: 600;
            width: 40%;
        }
        td {
            padding: 10px 15px;
        }
        tr:last-child th, tr:last-child td {
            border-bottom: none;
        }
        .signature-td {
            height: 40px;
            background-color: #f9fafb;
        }
        /* Personnel info styling */
        .personnel-info {
            font-weight: bold;
            color: #2d3748;
        }
        .personnel-role {
            font-size: 10px;
            color: #718096;
            font-style: italic;
        }
        /* Footer styling */
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #718096;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
        /* Page break */
        .page-break {
            page-break-after: always;
        }
        /* Utility classes */
        .text-center {
            text-align: center;
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
        <h1>DAILY ROSTER SIGNATURES</h1>
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

    <p><strong>Total Vehicles:</strong> {{ $uniqueVehicles }}</p>
    <p><strong>Total Crew Members:</strong> {{ $totalCrew }}</p>
    <p><strong>Total Car Commanders:</strong> {{ $uniqueCommanders }}</p>
    <p><strong>Reference:</strong> ROSTER-{{ $date->format('Ymd') }}</p>
</div>

@php
    // Group entries by vehicle and then by crew assignment
    $vehicleGroups = $entries->groupBy('vehicle_id');
@endphp

@foreach($vehicleGroups as $vehicleId => $vehicleEntries)
    @php
        // Get first entry to access vehicle and driver info
        $firstEntry = $vehicleEntries->first();
        // Get all crew assignments for this vehicle
        $crewAssignments = $vehicleEntries->sortBy('crew_number');
    @endphp

    <div class="assignment-section">
        <div class="assignment-header">
            Vehicle #{{ $loop->index + 1 }}: {{ $firstEntry->vehicle->registration_number }}
        </div>

        <div class="assignment-subheader">
            Driver: {{ $firstEntry->driver->name }}
        </div>

        <table>
            <tbody>
            <tr>
                <th>Driver Signature</th>
                <td class="signature-td"></td>
            </tr>
            </tbody>
        </table>

        @foreach($crewAssignments as $assignment)
            <div class="assignment-subheader">
                Crew #{{ $assignment->crew_number }} Assignment
            </div>

            <table>
                <tbody>
                <tr>
                    <th>Crew Member</th>
                    <td>
                        <div class="personnel-info">{{ $assignment->crew->name }}</div>
                        <div class="personnel-role">Crew Member</div>
                    </td>
                </tr>
                <tr>
                    <th>Crew Member Signature</th>
                    <td class="signature-td"></td>
                </tr>
                <tr>
                    <th>Car Commander</th>
                    <td>
                        <div class="personnel-info">{{ $assignment->carCommander->name }}</div>
                        <div class="personnel-role">Car Commander</div>
                    </td>
                </tr>
                <tr>
                    <th>Car Commander Signature</th>
                    <td class="signature-td"></td>
                </tr>
                </tbody>
            </table>
        @endforeach

        <div class="assignment-subheader">
            Operational Information
        </div>

        <table>
            <tbody>
            <tr>
                <th>Pre-Departure Equipment Check</th>
                <td class="signature-td"></td>
            </tr>
            <tr>
                <th>Post-Return Equipment Check</th>
                <td class="signature-td"></td>
            </tr>
            <tr>
                <th>Departure Time</th>
                <td class="signature-td"></td>
            </tr>
            <tr>
                <th>Return Time</th>
                <td class="signature-td"></td>
            </tr>
            </tbody>
        </table>
    </div>

    @if(!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach

<div class="footer">
    <p>This is an official document of the organization. All crew members and car commanders must sign before and after duty.</p>
    <p>Document ID: ROSTER-SIG-{{ $date->format('Ymd') }}-{{ strtoupper(substr(md5($date->format('Y-m-d')), 0, 6)) }}</p>
    <p>Generated on {{ now()->format('F d, Y H:i:s') }}</p>
</div>
</body>
</html>
