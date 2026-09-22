<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mobile Plan API (server-side only)
    |--------------------------------------------------------------------------
    |
    | Used only when the selected API row has an empty api_url. Do not set this
    | to force one provider over another — PlanAPI, MPlan, and PlanConnect each
    | use their own host from the apis table (primary and backup).
    |
    | PlanAPI:  https://planapi.in/api/Mobile/Operatorplan
    | MPlan:    https://www.mplan.in/api/plans.php
    | PlanConnect: /api/getMobilePlans
    |
    */

    'base_url' => env('PLAN_API_BASE_URL'),

    'api_key' => env('PLAN_API_KEY'),

    'connect_timeout' => max(3, (int) env('PLAN_API_CONNECT_TIMEOUT', env('RECHARGE_API_CONNECT_TIMEOUT', 10))),

    'timeout' => max(5, (int) env('PLAN_API_TIMEOUT', env('RECHARGE_API_TIMEOUT', 30))),

];
