<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogsController extends Controller
{
    public function apilogs(Request $request)
    {
        $query = DB::table('apilogs')->orderBy('id', 'desc');

        if ($request->filled('url')) {
            $query->where('url', 'like', '%' . $request->url . '%');
        }
        if ($request->filled('txnid')) {
            $query->where('txnid', $request->txnid);
        }
        if ($request->filled('modal')) {
            $query->where('modal', $request->modal);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs = $query->paginate(50);

        return view('admin.apilogs', [
            'logs' => $logs,
            'filters' => $request->only(['url','txnid','modal','from','to'])
        ]);
    }
}
