<?php

namespace App\Filament\Widgets;

use App\Models\Vehicle;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VehicleStatusOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $totalVehicles = Vehicle::count();
        $activeVehicles = Vehicle::where('status', 'active')->count();
        $maintenanceVehicles = Vehicle::where('status', 'maintenance')->count();
        $inactiveVehicles = Vehicle::where('status', 'inactive')->count();
        $withDrivers = Vehicle::whereHas('permanentDriver')->count();
        $withoutDrivers = Vehicle::whereDoesntHave('permanentDriver')->count();

        return [
            Stat::make('Total Vehicles', $totalVehicles)
                ->description('All registered vehicles')
                ->descriptionIcon('heroicon-m-truck')
                ->chart([7, 2, 10, 3, 15, 4, $totalVehicles])
                ->color('gray'),

            Stat::make('Active Vehicles', $activeVehicles)
                ->description(number_format(($totalVehicles > 0) ? ($activeVehicles / $totalVehicles * 100) : 0, 1) . '% of fleet')
                ->descriptionIcon('heroicon-m-check-circle')
                ->chart([2, 5, 8, 3, 7, 5, $activeVehicles])
                ->color('success'),

            Stat::make('In Maintenance', $maintenanceVehicles)
                ->description(number_format(($totalVehicles > 0) ? ($maintenanceVehicles / $totalVehicles * 100) : 0, 1) . '% of fleet')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->chart([1, 3, 2, 4, 1, 3, $maintenanceVehicles])
                ->color('warning'),

            Stat::make('With Assigned Driver', $withDrivers)
                ->description(number_format(($totalVehicles > 0) ? ($withDrivers / $totalVehicles * 100) : 0, 1) . '% assigned')
                ->descriptionIcon('heroicon-m-user')
                ->chart([3, 5, 7, 4, 8, 3, $withDrivers])
                ->color('info'),
        ];
    }
}
