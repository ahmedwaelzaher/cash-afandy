<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\FailedJob;
use Illuminate\Support\Facades\Artisan;
use Redot\Http\Controllers\Controller;

class FailedJobController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard.failed-jobs.index');
    }

    /**
     * Push the failed job back onto its queue.
     */
    public function retry(FailedJob $failedJob)
    {
        Artisan::call('queue:retry', ['id' => [$failedJob->uuid]]);

        return $this->success(__('Job has been pushed back onto the queue.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FailedJob $failedJob)
    {
        $failedJob->delete();

        return $this->deleted(__('Failed Job'));
    }
}
