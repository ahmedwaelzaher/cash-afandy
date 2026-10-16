<x-layouts::dashboard>
    <x-page-header class="mb-3" />
    <div class="card">
        <div class="card-header justify-content-between">
            <h2 class="card-title">{{ __('Backup status') }}: {{ $healthy ? __('Healthy') : __('Unhealthy') }}</h2>
            @if (route_allowed('dashboard.backups.run'))
                <x-form :action="route('dashboard.backups.run')" method="POST">
                    <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
                </x-form>
            @endif
        </div>
        <div class="card-body">
            @foreach ($failures as $failure)
                <x-alert type="warning" :title="$failure['message']" class="mb-3" />
            @endforeach
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Date') }}</th><th>{{ __('Size') }}</th></tr></thead>
                    <tbody>
                        @forelse ($backups as $backup)
                            <tr><td class="text-break">{{ basename($backup->path()) }}</td><td>{{ $backup->date()->format('Y-m-d H:i') }}</td><td>{{ \Illuminate\Support\Number::fileSize($backup->sizeInBytes()) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-secondary">{{ __('No backups found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts::dashboard>
