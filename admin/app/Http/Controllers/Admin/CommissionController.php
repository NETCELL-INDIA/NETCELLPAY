<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    public function index()
    {
        $schemes = DB::table('schemes')
            ->where('deleted_at', '!=', 1)
            ->where('status', 1)
            ->orderBy('scheme_name')
            ->get(['id', 'scheme_name']);

        $services = DB::table('services')
            ->where('deleted_at', '!=', 1)
            ->where('status', 1)
            ->orderBy('id')
            ->get(['id', 'service_name']);

        return view('admin.commission.index', compact('schemes', 'services'));
    }
}
