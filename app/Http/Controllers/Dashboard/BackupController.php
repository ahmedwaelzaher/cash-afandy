<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Support\Facades\Artisan;
use Redot\Http\Controllers\Controller;
use Spatie\Backup\Config\Config;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatusFactory;

class BackupController extends Controller
{
    /**
     * Display the local backup status and archives.
     */
    public function index(Config $config)
    {
        $status = BackupDestinationStatusFactory::createForMonitorConfig($config->monitoredBackups)->first();
        $healthy = $status->isHealthy();
        $destination = $status->backupDestination();

        return view('dashboard.backups.index', [
            'backups' => $destination->backups(),
            'destination' => $destination,
            'failures' => $status->failureMessages(),
            'healthy' => $healthy,
        ]);
    }

    /**
     * Create a full backup on the local disk.
     */
    public function run()
    {
        $exitCode = Artisan::call('backup:run', [
            '--only-to-disk' => 'local',
            '--disable-notifications' => true,
        ]);

        if ($exitCode !== 0) {
            return $this->error(__('Backup could not be created. Check the application logs for details.'));
        }

        return $this->success(__('Backup has been created successfully.'), 'dashboard.backups.index');
    }
}
