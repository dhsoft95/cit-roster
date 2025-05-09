<?php

use App\Filament\Pages\ViewRosterDatePage;
use App\Http\Controllers\RosterExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/roster/export-pdf', [RosterExportController::class, 'exportPdf'])
    ->name('roster.export-pdf')
    ->middleware(['auth']);
