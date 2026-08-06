<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RechargeMenuController extends Controller
{
    private array $pages = [
        'rehit-recharge-history' => [
            'title' => 'Rehit Recharge History',
            'description' => 'Retry/rehit attempt history',
        ],
        'amountwise-report' => [
            'title' => 'Amountwise Report',
            'description' => 'Report grouped by recharge amounts',
        ],
        'consumption-report' => [
            'title' => 'Consumption Report',
            'description' => 'Service consumption analytics',
        ],
        'r-offer-report' => [
            'title' => 'R-Offer Report',
            'description' => 'Special offer/redemption report',
        ],
        'plan-logs-report' => [
            'title' => 'Plan Logs Report',
            'description' => 'Mobile/DTH plan fetch logs',
        ],
    ];

    public function show(Request $request, string $slug)
    {
        if (!isset($this->pages[$slug])) {
            abort(404);
        }

        $page = $this->pages[$slug];

        return view('admin.recharge-reports.placeholder', [
            'title' => $page['title'],
            'description' => $page['description'],
            'slug' => $slug,
            'section' => 'Recharge Reports',
        ]);
    }
}
