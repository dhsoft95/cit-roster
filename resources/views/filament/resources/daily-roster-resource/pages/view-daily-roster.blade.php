{{-- Save this at: resources/views/filament/resources/daily-roster-resource/pages/view-daily-roster.blade.php --}}

<x-filament::page>
    <x-filament::card>
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">
                Daily Roster for {{ $record->roster_date->format('F d, Y') }}
            </h2>
            <div>
                <x-filament::button
                    tag="a"
                    href="{{ route('filament.admin.resources.daily-rosters.edit', $record) }}"
                >
                    Edit Roster
                </x-filament::button>
            </div>
        </div>

        {{ $this->table }}
    </x-filament::card>
</x-filament::page>
