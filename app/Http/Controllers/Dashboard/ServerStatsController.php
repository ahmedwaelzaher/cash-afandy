<?php

namespace App\Http\Controllers\Dashboard;

use Redot\Http\Controllers\Controller;

class ServerStatsController extends Controller
{
    /**
     * Display the server stats page.
     */
    public function index()
    {
        return view('dashboard.server-stats.index');
    }
}
