<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueueController extends Controller
{
    public function index(Request $request)
    {
        $jobsCount = DB::table('jobs')->count();
        $failedCount = DB::table('failed_jobs')->count();
        $recentFailed = DB::table('failed_jobs')->orderBy('id', 'desc')->limit(50)->get();

        return view('admin.queue_monitor', [
            'jobsCount' => $jobsCount,
            'failedCount' => $failedCount,
            'recentFailed' => $recentFailed,
        ]);
    }
}
