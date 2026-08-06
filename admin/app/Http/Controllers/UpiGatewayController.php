<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UpiGatewayController extends Controller
{
    public function status(Request $request)
    {
        return response()->json(['status' => 'ok']);
    }

    public function callBack(Request $request)
    {
        return response()->json(['status' => 'ok']);
    }
}
