<?php

namespace App\Http\Controllers\ApiPartner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    public function checkBalance(Request $post)
    {
        $user = DB::table('users')
            ->where('api_key', $post->api_key)
            ->whereNotIn('role_id', [1, 2])
            ->first();

        if (!$user) {
            return response()->json([
                'type' => 'error',
                'message' => 'invaild api key',
            ]);
        }

        return response()->json([
            'type' => 'success',
            'message' => 'Get sucessfuly',
            'balance' => round((float) $user->wallet_balance, 2),
        ]);
    }
}
