<?php

/**
 * Netcell Pay multi-recharge service catalog.
 * IDs match production DB `services` table where configured.
 * Bill-payment categories use dedicated service_id values from BBPS setup.
 */
return [
    'recharge' => [
        ['id' => 1, 'name' => 'Mobile Recharge', 'icon' => 'service_icon/mobile_1.png', 'route' => 'users/services/mobile', 'type' => 'prepaid'],
        ['id' => 4, 'name' => 'Postpaid', 'icon' => 'service_icon/smartphone.png', 'route' => 'users/services/postpaid', 'type' => 'postpaid'],
        ['id' => 2, 'name' => 'DTH Recharge', 'icon' => 'service_icon/smart-tv.png', 'route' => 'users/services/dth', 'type' => 'dth'],
    ],
    'bbps' => [
        ['id' => 3, 'name' => 'Electricity', 'logo' => 'service_logo/10.png'],
        ['id' => 5, 'name' => 'Water', 'logo' => 'service_logo/9.png'],
        ['id' => 6, 'name' => 'FasTag', 'logo' => 'service_logo/5.png'],
        ['id' => 7, 'name' => 'Cable TV', 'logo' => 'service_logo/6.png'],
        ['id' => 9, 'name' => 'Book Cylinder', 'logo' => 'service_logo/7.png'],
        ['id' => 10, 'name' => 'Piped Gas', 'logo' => 'service_logo/8.png'],
        ['id' => 15, 'name' => 'Postpaid Bill', 'logo' => 'service_logo/3.png'],
        ['id' => 16, 'name' => 'Wifi/Landline', 'logo' => 'service_logo/11.png'],
        ['id' => 14, 'name' => 'Broadband', 'logo' => 'service_logo/23.png'],
        ['id' => 17, 'name' => 'Housing Society', 'logo' => 'service_logo/24.png'],
        ['id' => 12, 'name' => 'Credit Card', 'logo' => 'service_logo/15.png'],
        ['id' => 8, 'name' => 'Insurance', 'logo' => 'service_logo/16.png'],
        ['id' => 4, 'name' => 'Loan Repayment', 'logo' => 'service_logo/19.png'],
        ['id' => 11, 'name' => 'Subscription Fees', 'logo' => 'service_logo/20.png'],
        ['id' => 21, 'name' => 'Google Play', 'logo' => 'service_logo/21.png'],
        ['id' => 13, 'name' => 'Municipal Taxes', 'logo' => 'service_logo/22.png'],
    ],
    'bbps_hub_route' => 'users/services/bill-payments',
    'plan_api_id' => 7,
    'bbps_params_api_id' => 22,
];
