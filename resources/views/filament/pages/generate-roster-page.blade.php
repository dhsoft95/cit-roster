{{-- resources/views/filament/pages/generate-roster-page.blade.php --}}

<x-filament-panels::page>
    <div class="mb-6">
        <h2 class="text-xl font-bold">Generate Daily Roster</h2>
    </div>

    <div class="mb-6">
        <p>
            This tool will generate a new roster for the selected date using available vehicles and personnel.
            It will randomly assign car commanders and crew members to vehicles with permanent drivers.
        </p>
    </div>

    <form wire:submit="generateRoster">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Generate Roster
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
