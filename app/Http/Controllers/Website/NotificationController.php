<?php

namespace App\Http\Controllers\Website;

use Redot\Http\Controllers\Controller;

class NotificationController extends Controller
{
    public function index()
    {
        return view('website.notifications.index');
    }
}
