<?php

namespace App\Filament\Resources\PermanentAssignmentResource\Pages;

use App\Filament\Resources\PermanentAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPermanentAssignments extends ListRecords
{
    protected static string $resource = PermanentAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
