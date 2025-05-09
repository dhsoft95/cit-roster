<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyRosterResource\Pages;
use App\Models\DailyRoster;
use App\Models\Vehicle;
use App\Models\Personnel;
use Carbon\Carbon;
use CWSPS154\UsersRolesPermissions\Models\HasRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class DailyRosterResource extends Resource
{
    protected static ?string $model = DailyRoster::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Daily Roster';

    protected static ?string $recordTitleAttribute = 'roster_date';

    protected static ?int $navigationSort = 20;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('roster_date', Carbon::today())->exists()
            ? 'Today'
            : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('roster_date')
                    ->label('Date')
                    ->date('l, F j, Y') // Format: Monday, January 1, 2025
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (DailyRoster $record): ?string =>
                    Carbon::parse($record->roster_date)->isToday()
                        ? 'Today'
                        : Carbon::parse($record->roster_date)->diffForHumans()
                    )
                    ->color(fn (DailyRoster $record) =>
                    Carbon::parse($record->roster_date)->isToday()
                        ? 'success'
                        : (Carbon::parse($record->roster_date)->isPast()
                        ? 'danger'
                        : 'primary')
                    ),

                Tables\Columns\TextColumn::make('vehicle_count')
                    ->label('Vehicles')
                    ->getStateUsing(function ($record) {
                        return DailyRoster::where('roster_date', $record->roster_date)
                            ->select('vehicle_id')
                            ->distinct()
                            ->count();
                    })
                    ->icon('heroicon-o-truck')
                    ->color('primary')
                    ->size('lg'),

                Tables\Columns\TextColumn::make('crew_count')
                    ->label('Crew Members')
                    ->getStateUsing(function ($record) {
                        // Count driver, car commander and crew assignments
                        return DailyRoster::where('roster_date', $record->roster_date)
                            ->where(function($query) {
                                $query->whereNotNull('driver_id')
                                    ->orWhereNotNull('car_commander_id')
                                    ->orWhereNotNull('crew_id');
                            })
                            ->count();
                    })
                    ->icon('heroicon-o-user-group')
                    ->color('warning')
                    ->size('lg'),

                Tables\Columns\TagsColumn::make('status_summary')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        // Count total personnel
                        $personnelCount = DailyRoster::where('roster_date', $record->roster_date)
                            ->where(function($query) {
                                $query->whereNotNull('driver_id')
                                    ->orWhereNotNull('car_commander_id')
                                    ->orWhereNotNull('crew_id');
                            })
                            ->count();

                        // Count unique vehicles
                        $vehicleCount = DailyRoster::where('roster_date', $record->roster_date)
                            ->select('vehicle_id')
                            ->distinct()
                            ->count();

                        $tags = [];

                        if ($personnelCount > 0) {
                            $tags[] = 'Active';
                        }

                        if (Carbon::parse($record->roster_date)->isToday()) {
                            $tags[] = 'Today';
                        }

                        if ($vehicleCount >= 3) {
                            $tags[] = 'Full Crew';
                        }

                        return $tags;
                    })
                    ->colors([
                        'success' => 'Active',
                        'warning' => 'Today',
                        'primary' => 'Full Crew',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('View Details')
                        ->tooltip('View complete roster details')
                        ->icon('heroicon-o-eye')
                        ->color('info'),

                    Tables\Actions\Action::make('print')
                        ->label('Print Roster')
                        ->tooltip('Generate printable version')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->action(function (DailyRoster $record): void {
                            // We'll handle printing directly through an action
                            // This avoids needing a custom route
                            $date = $record->roster_date;
                            $vehicles = DailyRoster::where('roster_date', $date)
                                ->select('vehicle_id')
                                ->distinct()
                                ->with(['vehicle'])
                                ->get()
                                ->pluck('vehicle');

                            // Flash some data to the session that can be retrieved by the success notification
                            session()->flash('print_roster_date', $date->format('l, F j, Y'));
                            session()->flash('print_roster_vehicles', $vehicles->count());

                            // Show a notification with print instructions
                            Notification::make()
                                ->title('Print Ready')
                                ->body('Use your browser\'s print function (Ctrl+P or Cmd+P) to print the roster.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('clone')
                        ->label('Clone to New Date')
                        ->tooltip('Copy this roster to another date')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('success')
                        ->form([
                            Forms\Components\DatePicker::make('new_date')
                                ->label('New Date')
                                ->required()
                                ->unique('daily_rosters', 'roster_date')
                                ->prefixIcon('heroicon-o-calendar')
                                ->default(
                                    fn (DailyRoster $record) => Carbon::parse($record->roster_date)->addDay()
                                ),
                        ])
                        ->action(function (DailyRoster $record, array $data): void {
                            // Get all entries for this roster date
                            $rosterEntries = DailyRoster::where('roster_date', $record->roster_date)->get();

                            // Clone each entry with the new date
                            foreach ($rosterEntries as $entry) {
                                DailyRoster::create([
                                    'roster_date' => $data['new_date'],
                                    'vehicle_id' => $entry->vehicle_id,
                                    'driver_id' => $entry->driver_id,
                                    'car_commander_id' => $entry->car_commander_id,
                                    'crew_id' => $entry->crew_id,
                                    'crew_number' => $entry->crew_number,
                                ]);
                            }

                            // Show a success notification
                            Notification::make()
                                ->title('Roster cloned')
                                ->body('The roster has been successfully cloned to the new date')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\EditAction::make()
                        ->tooltip('Edit roster')
                        ->icon('heroicon-o-pencil-square'),

                    Tables\Actions\DeleteAction::make()
                        ->tooltip('Delete roster')
                        ->icon('heroicon-o-trash'),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records): void {
                            // Export logic here
                        }),

                    Tables\Actions\BulkAction::make('print_multiple')
                        ->label('Print Selected')
                        ->icon('heroicon-o-printer')
                        ->action(function (Collection $records): void {
                            // Print logic here
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            // Use a subquery approach to avoid GROUP BY issues with MySQL
            ->modifyQueryUsing(function (Builder $query) {
                // Step 1: Get distinct dates with their min ID
                $subquery = DailyRoster::select('roster_date')
                    ->selectRaw('MIN(id) as id')
                    ->groupBy('roster_date');

                // Step 2: Join with the original table to get complete records
                return DailyRoster::joinSub(
                    $subquery,
                    'dr_dates',
                    function ($join) {
                        $join->on('daily_rosters.id', '=', 'dr_dates.id');
                    }
                )->select('daily_rosters.*')
                    ->orderBy('daily_rosters.roster_date', 'desc');
            })
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->emptyStateHeading('No Rosters Created')
            ->emptyStateDescription('Start by creating a daily roster for your team.')
            ->emptyStateActions([
                Tables\Actions\Action::make('create')
                    ->label('Create Roster')
                    ->url(route('filament.admin.resources.daily-rosters.create'))
                    ->icon('heroicon-o-plus')
                    ->button(),

                Tables\Actions\Action::make('create_today')
                    ->label('Create Today\'s Roster')
                    ->url(function () {
                        // Get the list routes
                        $listRoute = route('filament.admin.resources.daily-rosters.index');
                        $createRoute = route('filament.admin.resources.daily-rosters.create');

                        // Check if today's roster already exists
                        $todayExists = DailyRoster::whereDate('roster_date', Carbon::today())->exists();

                        if ($todayExists) {
                            // If today's roster exists, just go to the list page
                            // where it will be the first item
                            return $listRoute;
                        }

                        // Otherwise go to create with today's date prefilled
                        return $createRoute . '?date=' . Carbon::today()->format('Y-m-d');
                    })
                    ->icon('heroicon-o-calendar')
                    ->button()
                    ->color('success'),
            ])
            ->defaultSort('roster_date', 'desc')
            ->paginated([10, 25, 50, 100])
            ->poll('30s');
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            //RosterCalendarWidget::class,
            //TodaysSummaryWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyRosters::route('/'),
            'create' => Pages\CreateDailyRoster::route('/create'),
            'view' => Pages\ViewDailyRoster::route('/{record}'),
            'edit' => Pages\EditDailyRoster::route('/{record}/edit'),
        ];
    }
}
