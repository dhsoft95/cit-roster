<?php

namespace App\Filament\Widgets;

use App\Models\DailyRoster;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TodayRosterWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DailyRoster::query()
                    ->where('roster_date', Carbon::today()->format('Y-m-d'))
                    ->with(['vehicle', 'driver', 'carCommander', 'crew'])
            )
            ->heading("Today's Roster - " . Carbon::today()->format('F d, Y'))
            ->emptyStateHeading("No roster for today")
            ->emptyStateDescription("There is no roster created for today yet. Use the generate roster tool to create one.")
            ->emptyStateActions([
                Tables\Actions\Action::make('create_roster')
                    ->label('Generate Roster')
                    ->url(route('filament.admin.pages.generate-roster-page'))
                    ->icon('heroicon-o-plus'),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('vehicle.registration_number')
                    ->label('Vehicle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('driver.name')
                    ->label('Driver')
                    ->searchable(),
                Tables\Columns\TextColumn::make('carCommander.name')
                    ->label('Car Commander')
                    ->searchable(),
                Tables\Columns\TextColumn::make('crew.name')
                    ->label('Crew')
                    ->searchable(),
            ]);
    }
}
