<x-filament::page>
    <div class="space-y-6">
        <div class="filament-card p-6 bg-white rounded-xl shadow">
            <h2 class="text-xl font-bold mb-4">Generate Random Roster</h2>
            <p class="mb-4">
                This tool will help you generate a random roster for a specific date. The system will keep drivers assigned to their permanent vehicles but will randomly assign car commanders and crew members.
            </p>

            {{ $this->form }}

            <div class="mt-4">
                <x-filament::button wire:click="generatePreview">
                    Preview Random Roster
                </x-filament::button>
            </div>
        </div>
        @if($isPreviewReady)
            <div class="filament-card p-6 bg-white rounded-xl shadow">
                <h2 class="text-xl font-bold mb-4">Roster Preview</h2>
                <p class="mb-4">
                    Review the generated roster below. If you're satisfied with the assignments, click "Save Roster" to save it to the database.
                </p>

                {{ $this->table }}

                <div class="mt-4">
                    <x-filament::button wire:click="saveRoster" color="success">
                        Save Roster
                    </x-filament::button>
                </div>
            </div>
        @endif
    </div>
</x-filament::page>
