<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mobile Plan API (server-side only)
    |--------------------------------------------------------------------------
    |
    | Optional override for the plan list endpoint. When PLAN_API_BASE_URL is
    | empty, the app uses the URL from the apis table (plan_info_fetch_settings).
    |
    | Verified provider path (JSON, not 404): https://www.mplan.in/api/plans.php
    | Deprecated/broken path (HTML 404): http://planapi.in/api/plans.php
    |
    */

    'base_url' => env('PLAN_API_BASE_URL'),

    'api_key' => env('PLAN_API_KEY'),

    'connect_timeout' => max(3, (int) env('PLAN_API_CONNECT_TIMEOUT', env('RECHARGE_API_CONNECT_TIMEOUT', 10))),

    'timeout' => max(5, (int) env('PLAN_API_TIMEOUT', env('RECHARGE_API_TIMEOUT', 30))),

];
