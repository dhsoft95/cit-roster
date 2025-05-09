<?php

namespace App\Filament\Pages;

use App\Models\DailyRoster;
use App\Services\RosterGeneratorService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class GenerateRosterPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-m-calendar';
    protected static string $view = 'filament.pages.generate-roster-page';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?string $navigationLabel = 'Generate Roster';
    protected static ?int $navigationSort = 100;
    protected static ?string $title = 'Create Daily Roster';
//    protected static ?string $subheading = 'Generate vehicle and personnel assignments for a specific date';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'roster_date' => now()->addDay()->format('Y-m-d'),
            'use_permanent_drivers' => true,
            'overwrite_existing' => false,
            'crew_count' => 1,
            'max_vehicles' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Roster Configuration')
                    ->description('Configure how the roster should be generated')
                    ->Icon('heroicon-o-cog')
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                DatePicker::make('roster_date')
                                    ->label('Roster Date')
                                    ->required()
                                    ->default(now()->addDay())
                                    ->weekStartsOnMonday()
                                    ->closeOnDateSelection()
                                    ->displayFormat('F d, Y')
                                    ->minDate(now()),

                                Select::make('crew_count')
                                    ->label('Crew Members per Vehicle')
                                    ->options([
                                        1 => '1 crew member',
                                        2 => '2 crew members',
                                        3 => '3 crew members',
                                        4 => '4 crew members',
                                    ])
                                    ->default(1)
                                    ->required()
                                    ->helperText('Select the number of crew members to assign to each vehicle')
                                    ->prefixIcon('heroicon-o-user-group'),

                                TextInput::make('max_vehicles')
                                    ->label('Maximum Vehicles')
                                    ->numeric()
                                    ->minValue(1)
                                    ->helperText('Leave empty to include all available vehicles')
                                    ->placeholder('No limit')
                                    ->prefixIcon('heroicon-o-truck'),
                            ]),

                        Grid::make()
                            ->columns(2)
                            ->schema([
                                Toggle::make('use_permanent_drivers')
                                    ->label('Use Permanent Drivers')
                                    ->default(true)
                                    ->helperText('When enabled, permanent driver assignments will be used')
                                    ->onIcon('heroicon-m-check')
                                    ->offIcon('heroicon-m-x-mark'),

                                Toggle::make('overwrite_existing')
                                    ->label('Overwrite Existing Roster')
                                    ->default(false)
                                    ->helperText('When enabled, any existing roster for the selected date will be deleted before generating a new one')
                                    ->onIcon('heroicon-m-arrow-path')
                                    ->offIcon('heroicon-m-shield-check')
                                    ->onColor('danger'),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function generateRoster(RosterGeneratorService $rosterService)
    {
        $data = $this->form->getState();

        $date = Carbon::parse($data['roster_date']);
        $crewCount = (int) $data['crew_count'];
        $maxVehicles = !empty($data['max_vehicles']) ? (int) $data['max_vehicles'] : null;
        $usePermanentDrivers = (bool) $data['use_permanent_drivers'];
        $overwriteExisting = (bool) $data['overwrite_existing'];

        // Log the parameters for debugging
        Log::info('Generate roster parameters', [
            'date' => $date->format('Y-m-d'),
            'crew_count' => $crewCount,
            'max_vehicles' => $maxVehicles,
            'use_permanent_drivers' => $usePermanentDrivers,
            'overwrite_existing' => $overwriteExisting,
        ]);

        // Check if roster already exists for this date
        $existingCount = DailyRoster::where('roster_date', $date->format('Y-m-d'))->count();

        if ($existingCount > 0 && !$overwriteExisting) {
            $this->showExistingRosterNotification($date);
            return;
        }

        // If overwrite is enabled, delete existing entries
        if ($existingCount > 0 && $overwriteExisting) {
            DailyRoster::where('roster_date', $date->format('Y-m-d'))->delete();
        }

        // Generate roster with specified crew count
        $roster = $rosterService->generateRoster(
            $date,
            $crewCount,
            $maxVehicles,
            $usePermanentDrivers
        );

        if (empty($roster)) {
            $this->showNoVehiclesFoundNotification();
            return;
        }

        // Save roster
        $result = $rosterService->saveRoster($roster);

        if ($result['success']) {
            $this->showSuccessNotification($result, $roster, $date, $crewCount);
        } else {
            $this->showFailureNotification($result);
        }
    }

    /**
     * Show notification for existing roster
     */
    private function showExistingRosterNotification(Carbon $date): void
    {
        Notification::make()
            ->title('Roster already exists for this date')
            ->warning()

            ->actions([
                Action::make('view')
                    ->label('View Existing Roster')
                    ->url($this->getRosterViewUrl($date))
                    ->icon('heroicon-m-eye'),
                Action::make('overwrite')
                    ->label('Overwrite It')
                    ->color('danger')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        $this->data['overwrite_existing'] = true;
                        $this->generateRoster(app(RosterGeneratorService::class));
                    }),
            ])
            ->persistent()
            ->send();
    }

    /**
     * Show notification when no vehicles are found
     */
    private function showNoVehiclesFoundNotification(): void
    {
        Notification::make()
            ->title('Could not generate roster')
            ->body('No eligible vehicles or personnel found. Please check that you have active vehicles with permanent drivers assigned.')
            ->danger()

            ->persistent()
            ->send();
    }

    /**
     * Show success notification
     */
    private function showSuccessNotification(array $result, array $roster, Carbon $date, int $crewCount): void
    {
        $count = $result['count'];
        $vehicleCount = count(array_unique(array_column($roster, 'vehicle_id')));
        $crewText = $crewCount > 1 ? "with {$crewCount} crew members each" : "";

        $successMessage = "{$vehicleCount} vehicles with a total of {$count} assignments created for {$date->format('F d, Y')} {$crewText}";

        Notification::make()
            ->title("Roster generated successfully")
            ->body($successMessage)
            ->success()

            ->actions([
                Action::make('view')
                    ->label('View Roster')
                    ->url($this->getRosterViewUrl($date))
                    ->icon('heroicon-m-eye'),
                Action::make('print')
                    ->label('Print Roster')
                    ->url($this->getRosterPrintUrl($date))
                    ->openUrlInNewTab()
                    ->icon('heroicon-m-printer'),
            ])
            ->persistent()
            ->send();

        if (!empty($result['errors'])) {
            // Some entries failed
            Notification::make()
                ->title("Some entries had errors")
                ->body(implode("\n", array_slice($result['errors'], 0, 3)) .
                    (count($result['errors']) > 3 ? "\n..." : ""))
                ->warning()

                ->actions([
                    Action::make('details')
                        ->label('View Details')
                        ->action(fn () => $this->showDetailedErrors($result['errors'])),
                ])
                ->send();
        }
    }

    /**
     * Show failure notification
     */
    private function showFailureNotification(array $result): void
    {
        Notification::make()
            ->title("Failed to generate roster")
            ->body($result['message'] ?? 'An unknown error occurred')
            ->danger()

            ->persistent()
            ->send();
    }

    /**
     * Show detailed errors in a modal
     */
    private function showDetailedErrors(array $errors): void
    {
        // Implementation depends on your UI framework
        // This is a placeholder for where you'd show all errors in a modal
        Notification::make()
            ->title('All Errors')
            ->body(implode("\n", $errors))
            ->warning()
            ->persistent()
            ->send();
    }

    /**
     * Get URL for viewing the roster
     */
    private function getRosterViewUrl(Carbon $date): string
    {
        // Check if we're using the new page-based approach
        if (class_exists('App\Filament\Pages\ViewRosterDatePage')) {
            return route('filament.admin.pages.view-roster-date', [
                'date' => $date->format('Y-m-d')
            ]);
        }

        // Find a record for this date to use in the link
        $record = DailyRoster::where('roster_date', $date->format('Y-m-d'))->first();

        if ($record) {
            return route('filament.admin.resources.daily-rosters.view', $record);
        }

        return route('filament.admin.resources.daily-rosters.index');
    }

    /**
     * Get URL for printing the roster
     */
    private function getRosterPrintUrl(Carbon $date): string
    {
        if (class_exists('App\Filament\Pages\PrintRosterPage')) {
            return route('filament.admin.pages.print-roster', [
                'date' => $date->format('Y-m-d')
            ]);
        }

        return $this->getRosterViewUrl($date) . '?print=true';
    }
}
