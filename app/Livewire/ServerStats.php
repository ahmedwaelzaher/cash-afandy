<?php

namespace App\Livewire;

use Carbon\CarbonInterval;
use Illuminate\Support\Number;
use Livewire\Component;

class ServerStats extends Component
{
    /**
     * Render the server stats component.
     */
    public function render()
    {
        return view('livewire.server-stats', [
            'stats' => $this->collectStats(),
        ]);
    }

    /**
     * Collect the current server usage metrics.
     *
     * @return array<string, mixed>
     */
    protected function collectStats(): array
    {
        $memory = $this->memoryStats();
        $cpu = $this->cpuStats();
        $disk = $this->diskStats(base_path());

        return [
            'memory' => $memory,
            'cpu' => $cpu,
            'disk' => $disk,
            'system' => $this->systemStats(),
            'updated_at' => now(),
        ];
    }

    /**
     * @return array{total: int|null, used: int|null, free: int|null, percent: float|null, total_formatted: string|null, used_formatted: string|null, free_formatted: string|null, color: string}
     */
    protected function memoryStats(): array
    {
        $meminfo = $this->readMeminfo();

        if ($meminfo === null || ! isset($meminfo['MemTotal'])) {
            return $this->emptyUsageStats();
        }

        $total = $meminfo['MemTotal'] * 1024;
        $free = ($meminfo['MemAvailable'] ?? $meminfo['MemFree'] ?? 0) * 1024;
        $used = max(0, $total - $free);
        $percent = $total > 0 ? round(($used / $total) * 100, 1) : 0.0;

        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'percent' => $percent,
            'total_formatted' => Number::fileSize($total),
            'used_formatted' => Number::fileSize($used),
            'free_formatted' => Number::fileSize($free),
            'color' => $this->usageColor($percent),
        ];
    }

    /**
     * @return array{load_1: float|null, load_5: float|null, load_15: float|null, cores: int|null, percent: float|null, color: string}
     */
    protected function cpuStats(): array
    {
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : false;
        $cores = $this->cpuCores();

        if ($load === false) {
            return [
                'load_1' => null,
                'load_5' => null,
                'load_15' => null,
                'cores' => $cores,
                'percent' => null,
                'color' => 'secondary',
            ];
        }

        $load1 = round((float) $load[0], 2);
        $percent = $cores > 0 ? round(min(100, ($load1 / $cores) * 100), 1) : null;

        return [
            'load_1' => $load1,
            'load_5' => round((float) $load[1], 2),
            'load_15' => round((float) $load[2], 2),
            'cores' => $cores,
            'percent' => $percent,
            'color' => $percent === null ? 'secondary' : $this->usageColor($percent),
        ];
    }

    /**
     * @return array{total: int|null, used: int|null, free: int|null, percent: float|null, total_formatted: string|null, used_formatted: string|null, free_formatted: string|null, color: string, path: string}
     */
    protected function diskStats(string $path): array
    {
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if ($total === false || $free === false || $total <= 0) {
            return array_merge($this->emptyUsageStats(), ['path' => $path]);
        }

        $used = max(0, $total - $free);
        $percent = round(($used / $total) * 100, 1);

        return [
            'total' => (int) $total,
            'used' => (int) $used,
            'free' => (int) $free,
            'percent' => $percent,
            'total_formatted' => Number::fileSize((int) $total),
            'used_formatted' => Number::fileSize((int) $used),
            'free_formatted' => Number::fileSize((int) $free),
            'color' => $this->usageColor($percent),
            'path' => $path,
        ];
    }

    /**
     * @return array{hostname: string, os: string, php_version: string, laravel_version: string, php_memory_usage: string, php_memory_limit: string, uptime: string|null}
     */
    protected function systemStats(): array
    {
        return [
            'hostname' => gethostname() ?: __('Unknown'),
            'os' => php_uname('s') . ' ' . php_uname('r'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'php_memory_usage' => Number::fileSize(memory_get_usage(true)),
            'php_memory_limit' => ini_get('memory_limit') ?: __('Unknown'),
            'uptime' => $this->uptime(),
        ];
    }

    /**
     * @return array{total: null, used: null, free: null, percent: null, total_formatted: null, used_formatted: null, free_formatted: null, color: string}
     */
    protected function emptyUsageStats(): array
    {
        return [
            'total' => null,
            'used' => null,
            'free' => null,
            'percent' => null,
            'total_formatted' => null,
            'used_formatted' => null,
            'free_formatted' => null,
            'color' => 'secondary',
        ];
    }

    /**
     * @return array<string, int>|null
     */
    protected function readMeminfo(): ?array
    {
        if (! is_readable('/proc/meminfo')) {
            return null;
        }

        $contents = @file_get_contents('/proc/meminfo');

        if ($contents === false) {
            return null;
        }

        $stats = [];

        foreach (explode("\n", $contents) as $line) {
            if (preg_match('/^(\w+):\s+(\d+)/', $line, $matches)) {
                $stats[$matches[1]] = (int) $matches[2];
            }
        }

        return $stats;
    }

    protected function cpuCores(): ?int
    {
        if (is_readable('/proc/cpuinfo')) {
            $contents = @file_get_contents('/proc/cpuinfo');

            if ($contents !== false) {
                $cores = substr_count($contents, 'processor');

                if ($cores > 0) {
                    return $cores;
                }
            }
        }

        $cores = (int) (getenv('NUMBER_OF_PROCESSORS') ?: 0);

        return $cores > 0 ? $cores : null;
    }

    protected function uptime(): ?string
    {
        if (! is_readable('/proc/uptime')) {
            return null;
        }

        $contents = @file_get_contents('/proc/uptime');

        if ($contents === false) {
            return null;
        }

        $seconds = (int) floatval(explode(' ', trim($contents))[0] ?? 0);

        if ($seconds <= 0) {
            return null;
        }

        return CarbonInterval::seconds($seconds)->cascade()->forHumans(['parts' => 3]);
    }

    protected function usageColor(float $percent): string
    {
        return match (true) {
            $percent >= 90 => 'danger',
            $percent >= 70 => 'warning',
            default => 'primary',
        };
    }
}
