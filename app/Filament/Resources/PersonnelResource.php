<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PersonnelResource\Pages;
use App\Filament\Resources\PersonnelResource\RelationManagers;
use App\Filament\Resources\PersonnelResource\Widgets;
use App\Filament\Widgets\PersonnelStatsOverview;
use App\Models\Personnel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PersonnelResource extends Resource
{
    protected static ?string $model = Personnel::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Staff Management';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

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
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Full name')
                            ->columnSpan(2),

                        Forms\Components\Select::make('role')
                            ->required()
                            ->options([
                                'driver' => 'Driver',
                                'car_commander' => 'Car Commander',
                                'crew' => 'Crew Member',
                            ])
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                // Clear vehicle assignment if role is changed from driver
                                $set('assigned_vehicle_id', null);
                            })
                            // Using prefix instead of icon
                            ->prefixIcon('heroicon-o-identification')
                            ->native(false)
                            ->columnSpan(1),

                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'active' => 'Active',
                                'on_leave' => 'On Leave',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            // Using prefix instead of icon
                            ->prefixIcon('heroicon-o-signal')
                            ->native(false)
                            ->columnSpan(1),

                        Forms\Components\Fieldset::make('Contact Information')
                            ->schema([
                                Forms\Components\TextInput::make('phone_number')
                                    ->tel()
                                    ->maxLength(255)
                                    ->placeholder('Phone number')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->maxLength(255)
                                    ->placeholder('Email address')
                                    ->columnSpan(1),
                            ])
                            ->columns(2)
                            ->columnSpan(2),

                        Forms\Components\DatePicker::make('date_of_joining')
                            ->label('Date of Joining')
                            ->placeholder('Select date')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('badge_number')
                            ->label('Badge/ID Number')
                            ->placeholder('ID number')
                            ->maxLength(50)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Placeholder::make('vehicle_assignment_heading')
                            ->label('Permanent Vehicle Assignment')
                            ->content('Assign a permanent vehicle to this driver')
                            ->visible(fn (Forms\Get $get) => $get('role') === 'driver'),

                        Forms\Components\Select::make('assigned_vehicle_id')
                            ->label('Assigned Vehicle')
                            ->relationship(
                                'assignedVehicle',
                                'registration_number',
                                fn ($query) => $query->where('status', 'active')
                            )
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('registration_number')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('status')
                                    ->required()
                                    ->options([
                                        'active' => 'Active',
                                        'maintenance' => 'Maintenance',
                                        'inactive' => 'Inactive',
                                    ])
                                    ->default('active'),
                            ])
                            ->visible(fn (Forms\Get $get) => $get('role') === 'driver'),

                        Forms\Components\Textarea::make('notes')
                            ->placeholder('Additional notes about this personnel')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('role') === 'driver')
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-user')
                    ->description(fn (Personnel $record): ?string => $record->badge_number ? "ID: {$record->badge_number}" : null),

                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'primary' => 'driver',
                        'success' => 'car_commander',
                        'warning' => 'crew',
                    ])
                    ->icons([
                        'heroicon-m-truck' => 'driver',
                        'heroicon-m-shield-check' => 'car_commander',
                        'heroicon-m-user-group' => 'crew',
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'on_leave',
                        'danger' => 'inactive',
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => 'active',
                        'heroicon-m-clock' => 'on_leave',
                        'heroicon-m-x-circle' => 'inactive',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('assignedVehicle.registration_number')
                    ->label('Assigned Vehicle')
                    ->searchable()
                    ->placeholder('Not Assigned')
                    ->icon('heroicon-o-truck')
                    ->visible(fn ($record) => $record && $record->role === 'driver'),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Phone')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-phone'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-envelope'),

                Tables\Columns\TextColumn::make('date_of_joining')
                    ->label('Joined')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-calendar'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'driver' => 'Driver',
                        'car_commander' => 'Car Commander',
                        'crew' => 'Crew Member',
                    ])
                    ->indicator('Role'),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'on_leave' => 'On Leave',
                        'inactive' => 'Inactive',
                    ])
                    ->indicator('Status'),

                Tables\Filters\Filter::make('has_vehicle')
                    ->label('Has Assigned Vehicle')
                    ->query(fn (Builder $query): Builder => $query->whereHas('assignedVehicle'))
                    ->toggle()
                    ->visible(fn (Personnel $record) => $record && $record->role === 'driver'),
            ])
            ->filtersFormWidth('sm')
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye'),

                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square'),

                    Tables\Actions\Action::make('activate')
                        ->label('Set to Active')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Personnel $record) {
                            $record->status = 'active';
                            $record->save();
                        })
                        ->visible(fn (Personnel $record) => $record->status !== 'active'),

                    Tables\Actions\Action::make('on_leave')
                        ->label('Set to On Leave')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->action(function (Personnel $record) {
                            $record->status = 'on_leave';
                            $record->save();
                        })
                        ->visible(fn (Personnel $record) => $record->status !== 'on_leave'),

                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-o-trash'),
                ])
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

                    Tables\Actions\BulkAction::make('set_on_leave')
                        ->label('Set to On Leave')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->status = 'on_leave';
                                $record->save();
                            });
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultGroup('role')
            ->defaultSort('name')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateHeading('No personnel found')
            ->emptyStateDescription('Start by adding personnel to your system.')
            ->emptyStateActions([
                Tables\Actions\Action::make('create')
                    ->label('Add Personnel')
                    ->url(route('filament.admin.resources.personnels.create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ])
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
            'index' => Pages\ListPersonnels::route('/'),
            'create' => Pages\CreatePersonnel::route('/create'),
            'view' => Pages\ViewPersonnel::route('/{record}'),
            'edit' => Pages\EditPersonnel::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            PersonnelStatsOverview::class,
        ];
    }
}
