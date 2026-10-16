<div wire:poll.10s.visible>
    <div class="row row-cards mb-3">
        @foreach (['memory' => __('Memory Usage'), 'cpu' => __('CPU Load'), 'disk' => __('Disk Usage')] as $key => $label)
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h3">{{ $label }}</h2>
                        <p class="h1">{{ $stats[$key]['percent'] !== null ? $stats[$key]['percent'] . '%' : __('Unknown') }}</p>
                        @if ($stats[$key]['percent'] !== null)
                            <div class="progress" role="progressbar" aria-label="{{ $label }}" aria-valuenow="{{ $stats[$key]['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-{{ $stats[$key]['color'] }}" style="width: {{ $stats[$key]['percent'] }}%"></div>
                            </div>
                        @endif
                        @if (isset($stats[$key]['used_formatted']))
                            <p class="text-secondary mt-2 mb-0">{{ __('Used') }}: {{ $stats[$key]['used_formatted'] }} · {{ __('Free') }}: {{ $stats[$key]['free_formatted'] }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                @foreach (['hostname' => __('Hostname'), 'php_version' => __('PHP Version'), 'laravel_version' => __('Laravel Version'), 'php_memory_usage' => __('PHP Memory'), 'uptime' => __('Uptime')] as $key => $label)
                    <dt class="col-sm-4">{{ $label }}</dt>
                    <dd class="col-sm-8 text-break">{{ $stats['system'][$key] ?? __('Unknown') }}</dd>
                @endforeach
            </dl>
        </div>
    </div>
</div>
