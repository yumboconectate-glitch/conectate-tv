<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;

class ActivityController extends Controller
{
    public function index()
    {
        $logs = ActivityLog::with('subscriber')
            ->orderByDesc('created_at')
            ->limit(250)
            ->get();

        return view('activity', compact('logs'));
    }
}
