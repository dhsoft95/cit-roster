<?php

namespace App\Filament\Resources\PersonnelResource\Pages;

use App\Filament\Resources\PersonnelResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewPersonnel extends ViewRecord
{
    protected static string $resource = PersonnelResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Personal Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Full Name')
                            ->weight('bold')
                            ->columnSpan(2),

                        Infolists\Components\TextEntry::make('badge_number')
                            ->label('Badge/ID Number')
                            ->columnSpan(1),

                        Infolists\Components\TextEntry::make('date_of_joining')
                            ->label('Date of Joining')
                            ->date()
                            ->columnSpan(1),

                        Infolists\Components\TextEntry::make('role')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'driver' => 'primary',
                                'car_commander' => 'success',
                                'crew' => 'warning',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                'driver' => 'heroicon-m-truck',
                                'car_commander' => 'heroicon-m-shield-check',
                                'crew' => 'heroicon-m-user-group',
                                default => 'heroicon-m-user',
                            })
                            ->columnSpan(1),

                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'on_leave' => 'warning',
                                'inactive' => 'danger',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                'active' => 'heroicon-m-check-circle',
                                'on_leave' => 'heroicon-m-clock',
                                'inactive' => 'heroicon-m-x-circle',
                                default => 'heroicon-m-question-mark-circle',
                            })
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Contact Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('phone_number')
                            ->label('Phone Number')
                            ->icon('heroicon-m-phone')
                            ->columnSpan(1),

                        Infolists\Components\TextEntry::make('email')
                            ->label('Email Address')
                            ->icon('heroicon-m-envelope')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsed(false),

                Infolists\Components\Section::make('Vehicle Assignment')
                    ->schema([
                        Infolists\Components\TextEntry::make('assignedVehicle.registration_number')
                            ->label('Assigned Vehicle')
                            ->icon('heroicon-m-truck')
                            ->url(fn ($record) => $record->assignedVehicle
                                ? route('filament.admin.resources.vehicles.edit', ['record' => $record->assignedVehicle])
                                : null)
                            ->badge()
                            ->color('primary'),
                    ])
                    ->visible(fn ($record) => $record->role === 'driver')
                    ->collapsed(false),

                Infolists\Components\Section::make('Additional Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notes')
                            ->markdown()
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime()
                            ->columnSpan(1),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime()
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsed(true),
            ]);
    }
}
