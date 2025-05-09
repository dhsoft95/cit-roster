<?php

namespace App\Filament\Resources\PermanentAssignmentResource\Pages;

use App\Filament\Resources\PermanentAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPermanentAssignment extends EditRecord
{
    protected static string $resource = PermanentAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
