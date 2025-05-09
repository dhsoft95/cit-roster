<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermanentAssignmentResource\Pages;
use App\Filament\Resources\PermanentAssignmentResource\RelationManagers;
use App\Models\PermanentAssignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PermanentAssignmentResource extends Resource
{
    protected static ?string $model = PermanentAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Fleet Management';

    protected static ?string $navigationLabel = 'Permanent Assignments';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vehicle_id')
                    ->relationship('vehicle', 'registration_number')
                    ->preload()
                    ->searchable()
                    ->required()
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
                    ]),
                Forms\Components\Select::make('driver_id')
                    ->relationship('driver', 'name', fn ($query) => $query->where('role', 'driver'))
                    ->preload()
                    ->searchable()
                    ->required()
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vehicle.registration_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('driver.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListPermanentAssignments::route('/'),
            'create' => Pages\CreatePermanentAssignment::route('/create'),
            'edit' => Pages\EditPermanentAssignment::route('/{record}/edit'),
        ];
    }
}
