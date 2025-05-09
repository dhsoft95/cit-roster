<?php

namespace App\Http\Controllers;

use App\Models\DailyRoster;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\File;

class RosterExportController extends Controller
{
    /**
     * Export a daily roster as PDF
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportPdf(Request $request)
    {
        $date = $request->input('date');
        $type = $request->input('type', 'summary'); // Default to summary if not specified

        if (!$date) {
            abort(404, 'Date parameter is required');
        }

        try {
            $parsedDate = Carbon::parse($date);
        } catch (\Exception $e) {
            abort(400, 'Invalid date format');
        }

        // Get all roster entries for the specified date
        $rosterEntries = DailyRoster::where('roster_date', $parsedDate->format('Y-m-d'))
            ->with(['vehicle', 'driver', 'carCommander', 'crew'])
            ->get();

        if ($rosterEntries->isEmpty()) {
            abort(404, 'No roster found for the specified date');
        }

        // Determine which template to use based on the requested type
        $viewName = $type === 'signatures'
            ? 'pdfs.daily-roster-signatures'
            : 'pdfs.daily-roster';

        // Check if the view exists
        if (!View::exists($viewName)) {
            Log::error("PDF template not found: {$viewName}");
            abort(500, 'PDF template not found. Please contact the administrator.');
        }

        // Prepare the data for the view
        $data = [
            'date' => $parsedDate,
            'entries' => $rosterEntries,
        ];

        try {
            // Load the PDF view
            $pdf = app('dompdf.wrapper')->loadView($viewName, $data);

            // Set PDF options
            if ($type === 'signatures') {
                // Portrait is better for the signatures version
                $pdf->setPaper('a4', 'portrait');
            } else {
                // Landscape for the summary version
                $pdf->setPaper('a4', 'landscape');
            }

            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'margin_top' => 15,
                'margin_right' => 15,
                'margin_bottom' => 15,
                'margin_left' => 15,
                'dpi' => 150,
                'defaultFont' => 'sans-serif',
            ]);

            // Return the PDF as a download
            $filename = $type === 'signatures'
                ? "roster-signatures-{$parsedDate->format('Y-m-d')}.pdf"
                : "roster-{$parsedDate->format('Y-m-d')}.pdf";

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error("PDF generation error: " . $e->getMessage());
            abort(500, 'Error generating PDF: ' . $e->getMessage());
        }
    }
}
