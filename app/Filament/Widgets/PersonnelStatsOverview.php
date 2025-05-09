<?php

namespace App\Filament\Widgets;

use App\Models\Personnel;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PersonnelStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $totalPersonnel = Personnel::count();

        // Count by role
        $drivers = Personnel::where('role', 'driver')->count();
        $carCommanders = Personnel::where('role', 'car_commander')->count();
        $crewMembers = Personnel::where('role', 'crew')->count();

        // Count by status
        $activePersonnel = Personnel::where('status', 'active')->count();
        $onLeavePersonnel = Personnel::where('status', 'on_leave')->count();
        $inactivePersonnel = Personnel::where('status', 'inactive')->count();

        // Drivers with vehicles assigned
        $driversWithVehicles = Personnel::where('role', 'driver')
            ->whereHas('assignedVehicle')
            ->count();

        $driversWithoutVehicles = Personnel::where('role', 'driver')
            ->whereDoesntHave('assignedVehicle')
            ->count();

        return [
            Stat::make('Total Personnel', $totalPersonnel)
                ->description('All staff members')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('gray'),

            Stat::make('Drivers', $drivers)
                ->description($driversWithVehicles . ' with assigned vehicles')
                ->descriptionIcon('heroicon-m-truck')
                ->chart([$drivers, $carCommanders, $crewMembers])
                ->color('primary'),

            Stat::make('Car Commanders', $carCommanders)
                ->description(number_format(($totalPersonnel > 0) ? ($carCommanders / $totalPersonnel * 100) : 0, 1) . '% of staff')
                ->descriptionIcon('heroicon-m-shield-check')
                ->chart([$drivers, $carCommanders, $crewMembers])
                ->color('success'),

            Stat::make('Crew Members', $crewMembers)
                ->description(number_format(($totalPersonnel > 0) ? ($crewMembers / $totalPersonnel * 100) : 0, 1) . '% of staff')
                ->descriptionIcon('heroicon-m-user-group')
                ->chart([$drivers, $carCommanders, $crewMembers])
                ->color('warning'),

            Stat::make('Active Personnel', $activePersonnel)
                ->description(number_format(($totalPersonnel > 0) ? ($activePersonnel / $totalPersonnel * 100) : 0, 1) . '% of staff')
                ->descriptionIcon('heroicon-m-check-circle')
                ->chart([$activePersonnel, $onLeavePersonnel, $inactivePersonnel])
                ->color('success'),

            Stat::make('On Leave', $onLeavePersonnel)
                ->description(number_format(($totalPersonnel > 0) ? ($onLeavePersonnel / $totalPersonnel * 100) : 0, 1) . '% of staff')
                ->descriptionIcon('heroicon-m-clock')
                ->chart([$activePersonnel, $onLeavePersonnel, $inactivePersonnel])
                ->color('warning'),
        ];
    }
}
