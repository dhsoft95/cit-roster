<?php

namespace App\Filament\Resources\DailyRosterResource\Pages;

use App\Filament\Resources\DailyRosterResource;
use App\Models\DailyRoster;
use App\Models\Vehicle;
use App\Models\Personnel;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Card;
use Filament\Infolists\Components\Tabs;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class ViewDailyRoster extends ViewRecord
{
    protected static string $resource = DailyRosterResource::class;

    public function getTitle(): string
    {
        // Use the record date for the title
        $rosterDate = $this->record->roster_date;
        return "Roster for {$rosterDate->format('l, F d, Y')}";
    }

    public function getSubheading(): string
    {
        // Use the record date for the subheading
        $rosterDate = $this->record->roster_date;

        // Show relative date for context
        if ($rosterDate->isToday()) {
            return "Today's roster";
        } elseif ($rosterDate->isTomorrow()) {
            return "Tomorrow's roster";
        } elseif ($rosterDate->isYesterday()) {
            return "Yesterday's roster";
        } else {
            return $rosterDate->diffForHumans();
        }
    }

    protected function mutateFormData(array $data): array
    {
        // Get a single record to display in the title
        $record = DailyRoster::find($this->record->id);

        return [
            ...$data,
            'title' => "Roster for {$record->roster_date->format('F d, Y')}",
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        // Get the roster date from the current record
        $rosterDate = $this->record->roster_date;

        // Get unique vehicles with all their crew members for this date
        $vehicles = DailyRoster::where('roster_date', $rosterDate->format('Y-m-d'))
            ->select('vehicle_id', 'driver_id', 'car_commander_id')
            ->distinct()
            ->with(['vehicle', 'driver', 'carCommander'])
            ->get();

        // Get roster stats
        $driverCount = DailyRoster::where('roster_date', $rosterDate->format('Y-m-d'))
            ->whereNotNull('driver_id')
            ->count();

        $commanderCount = DailyRoster::where('roster_date', $rosterDate->format('Y-m-d'))
            ->whereNotNull('car_commander_id')
            ->count();

        $crewCount = DailyRoster::where('roster_date', $rosterDate->format('Y-m-d'))
            ->whereNotNull('crew_id')
            ->count();

        $totalPersonnel = $driverCount + $commanderCount + $crewCount;

        // For each vehicle, get its crew members
        $vehiclesWithCrew = $vehicles->map(function ($vehicle) use ($rosterDate) {
            // Get crew members for this vehicle
            $crewMembers = DailyRoster::where('roster_date', $rosterDate->format('Y-m-d'))
                ->where('vehicle_id', $vehicle->vehicle_id)
                ->whereNotNull('crew_id')
                ->with('crew')
                ->orderBy('crew_number')
                ->get();

            // Add crew members to the vehicle
            $vehicle->crewMembers = $crewMembers;

            // Count active crew members for this vehicle
            $vehicle->crewCount = $crewMembers->count();

            // Calculate roster completeness
            $vehicle->hasDriver = !empty($vehicle->driver_id);
            $vehicle->hasCommander = !empty($vehicle->car_commander_id);
            $vehicle->hasCrew = $vehicle->crewCount > 0;

            // Calculate completeness score (0-3)
            $completeness = 0;
            if ($vehicle->hasDriver) $completeness++;
            if ($vehicle->hasCommander) $completeness++;
            if ($vehicle->hasCrew) $completeness++;

            $vehicle->completenessScore = $completeness;
            $vehicle->completenessLabel = match($completeness) {
                0 => 'Unassigned',
                1 => 'Minimal',
                2 => 'Partial',
                3 => 'Complete',
                default => 'Unknown'
            };

            $vehicle->statusColor = match($completeness) {
                0 => 'danger',
                1 => 'warning',
                2 => 'primary',
                3 => 'success',
                default => 'gray'
            };

            return $vehicle;
        });

        // Calculate overall completeness percentage
        $totalPositions = $vehicles->count() * 3; // Driver, Commander, Crew for each vehicle
        $filledPositions = $driverCount + $commanderCount + ($crewCount > 0 ? $vehicles->count() : 0);
        $completenessPercentage = $totalPositions > 0 ? round(($filledPositions / $totalPositions) * 100) : 0;

        return $infolist
            ->schema([
                Grid::make(3)
                    ->schema([
                        Card::make()
                            ->schema([
                                TextEntry::make('roster_date')
                                    ->date('l, F d, Y')
                                    ->label('Roster Date')
                                    ->icon('heroicon-o-calendar')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color(function () use ($rosterDate) {
                                        if ($rosterDate->isToday()) {
                                            return 'success';
                                        } elseif ($rosterDate->isFuture()) {
                                            return 'primary';
                                        } else {
                                            return 'danger';
                                        }
                                    }),

                                TextEntry::make('created_at')
                                    ->dateTime()
                                    ->label('Created')
                                    ->icon('heroicon-o-clock')
                                    ->size('sm'),

                                TextEntry::make('updated_at')
                                    ->dateTime()
                                    ->label('Last Updated')
                                    ->icon('heroicon-o-arrow-path')
                                    ->size('sm'),
                            ])
                            ->columnSpan(1),

                        Card::make()
                            ->schema([
                                IconEntry::make('completeness')
                                    ->label('Roster Completeness')
                                    ->icon(function () use ($completenessPercentage) {
                                        if ($completenessPercentage >= 90) {
                                            return 'heroicon-o-check-circle';
                                        } elseif ($completenessPercentage >= 50) {
                                            return 'heroicon-o-check';
                                        } else {
                                            return 'heroicon-o-exclamation-circle';
                                        }
                                    })
                                    ->color(function () use ($completenessPercentage) {
                                        if ($completenessPercentage >= 90) {
                                            return 'success';
                                        } elseif ($completenessPercentage >= 50) {
                                            return 'warning';
                                        } else {
                                            return 'danger';
                                        }
                                    })
                                    ->size('xl'),

                                TextEntry::make('completeness_percentage')
                                    ->label('Completion Rate')
                                    ->state(function () use ($completenessPercentage) {
                                        return "{$completenessPercentage}%";
                                    })
                                    ->color(function () use ($completenessPercentage) {
                                        if ($completenessPercentage >= 90) {
                                            return 'success';
                                        } elseif ($completenessPercentage >= 50) {
                                            return 'warning';
                                        } else {
                                            return 'danger';
                                        }
                                    })
                                    ->size('lg')
                                    ->weight('bold'),
                            ])
                            ->columnSpan(1),

                        Card::make()
                            ->schema([
                                TextEntry::make('vehicle_count')
                                    ->label('Vehicles')
                                    ->state($vehicles->count())
                                    ->icon('heroicon-o-truck')
                                    ->color('primary')
                                    ->weight('bold'),

                                TextEntry::make('total_personnel')
                                    ->label('Total Personnel')
                                    ->state($totalPersonnel)
                                    ->icon('heroicon-o-user-group')
                                    ->color('warning')
                                    ->weight('bold'),

                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('drivers')
                                            ->label('Drivers')
                                            ->state($driverCount)
                                            ->icon('heroicon-o-user')
                                            ->color('success'),

                                        TextEntry::make('commanders')
                                            ->label('Commanders')
                                            ->state($commanderCount)
                                            ->icon('heroicon-o-shield-check')
                                            ->color('info'),

                                        TextEntry::make('crew')
                                            ->label('Crew')
                                            ->state($crewCount)
                                            ->icon('heroicon-o-users')
                                            ->color('warning'),
                                    ]),
                            ])
                            ->columnSpan(1),
                    ]),

                Tabs::make('Roster Information')
                    ->tabs([
                        Tabs\Tab::make('Vehicle Assignments')
                            ->icon('heroicon-o-truck')
                            ->schema([
                                RepeatableEntry::make('vehicles')
                                    ->schema([
                                        Card::make()
                                            ->schema([
                                                Grid::make(12)
                                                    ->schema([
                                                        TextEntry::make('vehicle.registration_number')
                                                            ->label('Vehicle')
                                                            ->icon('heroicon-o-truck')
                                                            ->weight('bold')
                                                            ->columnSpan(4),

                                                        IconEntry::make('status')
                                                            ->label('Status')
                                                            ->icon(function ($record) {
                                                                return match($record->completenessScore) {
                                                                    0 => 'heroicon-o-x-circle',
                                                                    1 => 'heroicon-o-exclamation-triangle',
                                                                    2 => 'heroicon-o-check',
                                                                    3 => 'heroicon-o-check-circle',
                                                                    default => 'heroicon-o-question-mark-circle'
                                                                };
                                                            })
                                                            ->color(fn ($record) => $record->statusColor)
                                                            ->columnSpan(1),

                                                        TextEntry::make('completenessLabel')
                                                            ->label('Crew Status')
                                                            ->color(fn ($record) => $record->statusColor)
                                                            ->columnSpan(3),

                                                        TextEntry::make('crewCount')
                                                            ->label('Crew Size')
                                                            ->icon('heroicon-o-users')
                                                            ->color('warning')
                                                            ->columnSpan(2),

                                                        // Action buttons would go here in a real implementation
                                                        TextEntry::make('spacer')
                                                            ->state('')
                                                            ->columnSpan(2),
                                                    ]),

                                                Grid::make(3)
                                                    ->schema([
                                                        TextEntry::make('driver.name')
                                                            ->label('Driver')
                                                            ->icon('heroicon-o-user')
                                                            ->color('success')
                                                            ->placeholder('No driver assigned')
                                                            ->visible(fn ($record) => !empty($record->driver)),

                                                        TextEntry::make('no_driver')
                                                            ->label('Driver')
                                                            ->state('No driver assigned')
                                                            ->icon('heroicon-o-x-mark')
                                                            ->color('danger')
                                                            ->visible(fn ($record) => empty($record->driver)),

                                                        TextEntry::make('carCommander.name')
                                                            ->label('Car Commander')
                                                            ->icon('heroicon-o-shield-check')
                                                            ->color('info')
                                                            ->placeholder('No commander assigned')
                                                            ->visible(fn ($record) => !empty($record->carCommander)),

                                                        TextEntry::make('no_commander')
                                                            ->label('Car Commander')
                                                            ->state('No commander assigned')
                                                            ->icon('heroicon-o-x-mark')
                                                            ->color('danger')
                                                            ->visible(fn ($record) => empty($record->carCommander)),
                                                    ]),

                                                RepeatableEntry::make('crewMembers')
                                                    ->label('Crew Members')
                                                    ->hintIcon('heroicon-o-information-circle')
                                                    ->hintColor('warning')
                                                    ->hint(fn ($record) => $record->crewCount > 0
                                                        ? "Total: {$record->crewCount} crew members"
                                                        : "No crew members assigned")
                                                    ->schema([
                                                        Grid::make(12)
                                                            ->schema([
                                                                TextEntry::make('crew_number')
                                                                    ->label('Position')
                                                                    ->state(fn ($record) => "#{$record->crew_number}")
                                                                    ->weight('bold')
                                                                    ->columnSpan(2),

                                                                TextEntry::make('crew.name')
                                                                    ->label('Name')
                                                                    ->columnSpan(5),

                                                                TextEntry::make('crew.role')
                                                                    ->label('Role')
                                                                    ->formatStateUsing(fn ($state) => ucfirst($state))
                                                                    ->columnSpan(5),
                                                            ]),
                                                    ])
                                                    ->visible(fn ($record) => $record->crewCount > 0)
                                                    ->contained(false),

                                                TextEntry::make('no_crew')
                                                    ->label('Crew Members')
                                                    ->state('No crew members assigned to this vehicle')
                                                    ->icon('heroicon-o-x-mark')
                                                    ->color('danger')
                                                    ->visible(fn ($record) => $record->crewCount === 0),
                                            ]),
                                    ])
                                    ->contained(false)
                            ]),

                        Tabs\Tab::make('Staff Overview')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                // This would be a custom component to show all personnel assigned
                                // by role, with their assignments, for a different view
                                TextEntry::make('staff_view')
                                    ->label('Staff View Coming Soon')
                                    ->state('A staff-centric view of the roster will be implemented here.')
                                    ->columnSpanFull(),
                            ]),

                        Tabs\Tab::make('Notes & Special Instructions')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                // Notes would be stored in a different table in a real implementation
                                TextEntry::make('notes')
                                    ->label('Roster Notes')
                                    ->state('No special notes or instructions for this roster.')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->activeTab(0),
            ]);
    }

    protected function getHeaderActions(): array
    {
        // Get the roster date from the current record
        $rosterDate = $this->record->roster_date;

        return [
            ActionGroup::make([
                Action::make('exportSummaryPDF')
                    ->label('Summary PDF')
                    ->icon('heroicon-o-document')
                    ->url(fn () => route('roster.export-pdf', [
                        'date' => $rosterDate->format('Y-m-d'),
                        'type' => 'summary'
                    ]))
                    ->openUrlInNewTab(),

                Action::make('exportSignaturesPDF')
                    ->label('Signatures PDF')
                    ->icon('heroicon-o-document-text')
                    ->url(fn () => route('roster.export-pdf', [
                        'date' => $rosterDate->format('Y-m-d'),
                        'type' => 'signatures'
                    ]))
                    ->openUrlInNewTab(),
            ])
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down'),

            Action::make('cloneToday')
                ->label('Clone to Today')
                ->visible(fn () => !$this->record->roster_date->isToday())
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->action(function () use ($rosterDate) {
                    // Get all entries for this roster date
                    $rosterEntries = DailyRoster::where('roster_date', $rosterDate->format('Y-m-d'))->get();

                    // Only proceed if we don't already have a roster for today
                    $today = Carbon::today();
                    $todayExists = DailyRoster::whereDate('roster_date', $today)->exists();

                    if ($todayExists) {
                        Notification::make()
                            ->title('Cannot Clone')
                            ->body("A roster for today already exists.")
                            ->danger()
                            ->send();
                        return;
                    }

                    // Clone each entry with today's date
                    foreach ($rosterEntries as $entry) {
                        DailyRoster::create([
                            'roster_date' => $today,
                            'vehicle_id' => $entry->vehicle_id,
                            'driver_id' => $entry->driver_id,
                            'car_commander_id' => $entry->car_commander_id,
                            'crew_id' => $entry->crew_id,
                            'crew_number' => $entry->crew_number,
                        ]);
                    }

                    // Show a success notification
                    Notification::make()
                        ->title('Roster Cloned')
                        ->body("The roster has been successfully cloned to today.")
                        ->success()
                        ->send();

                    // Redirect to today's roster
                    redirect()->route('filament.admin.resources.daily-rosters.index');
                }),

//            Action::make('edit')
//                ->url(fn () => route('filament.admin.resources.daily-rosters.edit', $this->record))
//                ->icon('heroicon-o-pencil'),
        ];
    }
}
