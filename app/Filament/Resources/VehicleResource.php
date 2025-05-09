<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Widgets\VehicleStatusOverview;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Fleet Management';

    protected static ?string $recordTitleAttribute = 'registration_number';

    protected static ?int $navigationSort = 10;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'active')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('registration_number')
                            ->label('Registration Number')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g. T 123 ABC')
                            ->helperText('Enter the official registration number')
                            ->autocapitalize(),

                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'active' => 'Active',
                                'maintenance' => 'Maintenance',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->helperText('Current operational status of the vehicle')
                            ->native(false),
                    ])
                    ->columns(1),

                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Placeholder::make('driver_heading')
                            ->label('Driver Assignment')
                            ->content('Assign a permanent driver to this vehicle.'),

                        Forms\Components\Select::make('permanent_driver_id')
                            ->label('Permanent Driver')
                            ->relationship(
                                'permanentDriver',
                                'name',
                                fn ($query) => $query->where('role', 'driver')->where('status', 'active')
                            )
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Hidden::make('role')
                                    ->default('driver'),
                                Forms\Components\Select::make('status')
                                    ->required()
                                    ->options([
                                        'active' => 'Active',
                                        'on_leave' => 'On Leave',
                                        'inactive' => 'Inactive',
                                    ])
                                    ->default('active'),
                            ]),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('registration_number')
                    ->label('Reg. Number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-o-identification')
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'maintenance',
                        'danger' => 'inactive',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'active',
                        'heroicon-o-wrench' => 'maintenance',
                        'heroicon-o-x-circle' => 'inactive',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('permanentDriver.name')
                    ->label('Permanent Driver')
                    ->searchable()
                    ->placeholder('Not Assigned')
                    ->icon('heroicon-o-user')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Added On'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Last Updated'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'maintenance' => 'Maintenance',
                        'inactive' => 'Inactive',
                    ])
                    ->indicator('Status'),

                Tables\Filters\Filter::make('has_driver')
                    ->label('Has Permanent Driver')
                    ->query(fn (Builder $query): Builder => $query->whereHas('permanentDriver'))
                    ->toggle(),

                Tables\Filters\Filter::make('no_driver')
                    ->label('No Permanent Driver')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('permanentDriver'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square'),

                    Tables\Actions\Action::make('maintenance')
                        ->label('Set to Maintenance')
                        ->icon('heroicon-o-wrench')
                        ->color('warning')
                        ->action(function (Vehicle $record) {
                            $record->status = 'maintenance';
                            $record->save();
                        })
                        ->visible(fn (Vehicle $record) => $record->status !== 'maintenance'),

                    Tables\Actions\Action::make('activate')
                        ->label('Set to Active')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Vehicle $record) {
                            $record->status = 'active';
                            $record->save();
                        })
                        ->visible(fn (Vehicle $record) => $record->status !== 'active'),

                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-o-trash'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('set_active')
                        ->label('Set to Active')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->status = 'active';
                                $record->save();
                            });
                        }),

                    Tables\Actions\BulkAction::make('set_maintenance')
                        ->label('Set to Maintenance')
                        ->icon('heroicon-o-wrench')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->status = 'maintenance';
                                $record->save();
                            });
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-truck')
            ->emptyStateHeading('No vehicles found')
            ->emptyStateDescription('Start by adding a new vehicle to your fleet.')
            ->emptyStateActions([
                Tables\Actions\Action::make('create')
                    ->label('Add Vehicle')
                    ->url(route('filament.admin.resources.vehicles.create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ])
            ->defaultSort('registration_number')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
         VehicleStatusOverview::class,
        ];
    }
}
