<?php

namespace App\Filament\Resources\DailyRosterResource\Pages;

use App\Filament\Resources\DailyRosterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailyRosters extends ListRecords
{
    protected static string $resource = DailyRosterResource::class;

//    protected function getHeaderActions(): array
//    {
//        return [
//            Actions\CreateAction::make(),
//        ];
//    }
}
