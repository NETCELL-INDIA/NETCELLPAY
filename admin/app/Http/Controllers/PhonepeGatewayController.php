<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PhonepeGatewayController extends Controller
{
    public function status(Request $request)
    {
        return response()->json(['status' => 'ok']);
    }

    public function callback(Request $request)
    {
        return response()->json(['status' => 'ok']);
    }

    public function cronjob(Request $request)
    {
        return response()->json(['status' => 'ok']);
    }
}
