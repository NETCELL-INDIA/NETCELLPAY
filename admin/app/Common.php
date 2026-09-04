<?php

namespace App;

use Carbon\Carbon;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use Illuminate\Http\Request;

    class Common {



        public static function pt($data){

            return $data;

        }

    public static function getHost($host = null)
    {
        if ($host) {
            return $host;
        }

        $host = request()->header('host') ?? request()->server('HTTP_HOST') ?? request()->server('SERVER_NAME');

        if (!empty($host)) {
            return $host;
        }

        $url = config('app.url');
        return parse_url($url, PHP_URL_HOST) ?: 'localhost';
    }

    public static function getCompanyByHost($host = null)
    {
        try {
            $host = self::getHost($host);
            return Cache::remember('company_by_host_'.$host, 60, function () use ($host) {
            $hosts = array_values(array_unique(array_filter([
                $host,
                explode(':', (string) $host)[0],
                request()->getHttpHost(),
                request()->getHost(),
            ])));

            foreach ($hosts as $candidate) {
                $company = DB::table('companies')
                    ->where('status', 1)
                    ->where('domain', $candidate)
                    ->first();
                if ($company) {
                    return $company;
                }
            }

            $bare = explode(':', (string) $host)[0];
            $company = DB::table('companies')
                ->where('status', 1)
                ->where('domain', 'like', $bare . '%')
                ->first();

            return $company ?: DB::table('companies')->where('status', 1)->first();
            });
        } catch (\Throwable $e) {
            \Log::warning('Company lookup failed: ' . $e->getMessage());
            return null;
        }
    }

    public static function ensureDenominationCommissionTable(): void
    {
        try {
            if (Schema::hasTable('scheme_commission_denominations')) {
                return;
            }
            Schema::create('scheme_commission_denominations', function ($table) {
                $table->id();
                $table->unsignedBigInteger('scheme_id')->index();
                $table->unsignedBigInteger('provider_id')->index();
                $table->decimal('min_amount', 12, 2)->default(0);
                $table->decimal('max_amount', 12, 2)->default(0);
                $table->string('md_amount_type', 50)->nullable();
                $table->decimal('md_amount_value', 12, 4)->default(0);
                $table->string('dt_amount_type', 50)->nullable();
                $table->decimal('dt_amount_value', 12, 4)->default(0);
                $table->string('rt_amount_type', 50)->nullable();
                $table->decimal('rt_amount_value', 12, 4)->default(0);
                $table->string('ap_amount_type', 50)->nullable();
                $table->decimal('ap_amount_value', 12, 4)->default(0);
                $table->timestamps();
                $table->index(['scheme_id', 'provider_id']);
            });
        } catch (\Throwable $e) {
        }
    }

    public static function denominationCommissionRow($scheme, $provider_id, $amount)
    {
        self::ensureDenominationCommissionTable();
        if (! Schema::hasTable('scheme_commission_denominations')) {
            return null;
        }

        $amount = (float) $amount;

        return DB::table('scheme_commission_denominations')
            ->where('scheme_id', $scheme)
            ->where('provider_id', $provider_id)
            ->where('min_amount', '<=', $amount)
            ->where('max_amount', '>=', $amount)
            ->orderByRaw('(max_amount - min_amount) asc')
            ->orderByDesc('id')
            ->first();
    }

     public static function getCommission($amount, $scheme, $provider_id, $role_id)

     {
 
         $myscheme = DB::table('schemes')->where('id', $scheme)->first();
 
         
 
         if($myscheme && $myscheme->status == "1"){
 
             $comdata = null;
             if (in_array((int) $role_id, [3, 4, 5, 6], true)) {
                 $comdata = self::denominationCommissionRow($scheme, $provider_id, $amount);
             }
             if (! $comdata) {
                 $comdata = DB::table('scheme_commissions')->where('provider_id', $provider_id)->where('scheme_id', $scheme)->first();
             }
 
             if ($comdata) {
 
                if($role_id== 6){

                    if ($comdata->rt_amount_type == "Commission Percent") {

                        $commission = $amount * $comdata->rt_amount_value / 100;

                    }else{

                        $commission = $comdata->rt_amount_value;

                    }

                }else if($role_id== 3){

                    $apType = $comdata->ap_amount_type ?? '';
                    $apValue = $comdata->ap_amount_value ?? null;
                    if ($apType === '' || $apType === '0' || $apType === 0) {
                        $apType = $comdata->rt_amount_type;
                        $apValue = $comdata->rt_amount_value;
                    }
                    if ($apType == "Commission Percent") {
                        $commission = $amount * $apValue / 100;
                    } else {
                        $commission = $apValue;
                    }

                }else if($role_id== 5){
 
                     if ($comdata->dt_amount_type == "Commission Percent") {
 
                         $commission = $amount * $comdata->dt_amount_value / 100;
 
                     }else{
 
                         $commission = $comdata->dt_amount_value;
 
                     }
 
                 }else if($role_id== 4){
 
                     if ($comdata->md_amount_type == "Commission Percent") {
 
                         $commission = $amount * $comdata->md_amount_value / 100;
 
                     }else{
 
                         $commission = $comdata->md_amount_value;
 
                     }
 
                 }else if($role_id== 2){
 
                     if ($comdata->wt_amount_type == "Commission Percent") {
 
                         $commission = $amount * $comdata->wt_amount_value / 100;
 
                     }else{
 
                         $commission = $comdata->wt_amount_value;
 
                     }
 
                 }else{
 
                     $commission = 0;
 
                 }
 
                 if($commission == null){
 
                     $commission = 0;
 
                 }
 
             }else{
 
                 $commission = 0;
 
             }
 
         }else{
 
             $commission = 0;
 
         }
 
         
 
         return $commission;
 
     }





        public static function whatsappPublicOrigin(): string
        {
            $origin = rtrim((string) env('APP_URL', ''), '/');
            $origin = preg_replace('#/admin$#i', '', $origin) ?: $origin;
            if ($origin === '') {
                $origin = 'https://netcellpay.in';
            }

            return $origin;
        }

        public static function whatsappPublicFileUrl(string $filename): string
        {
            $filename = ltrim(basename($filename), '/');
            if ($filename === '') {
                return '';
            }

            return self::whatsappPublicOrigin().'/wa-media/'.$filename;
        }

        public static function companyWhatsappLogoUrl(): string
        {
            $company = DB::table('companies')->where('id', 1)->first(['company_logo', 'company_icon']);
            $file = (string) ($company->company_icon ?? $company->company_logo ?? '');
            if ($file === '') {
                return '';
            }

            return self::whatsappPublicFileUrl($file);
        }

        public static function whatsappTemplateImageUrl(string $filename): string
        {
            $filename = ltrim(basename($filename), '/');
            if ($filename === '') {
                return '';
            }
            if (function_exists('admin_asset')) {
                $url = (string) admin_asset('whatsapp_template/'.$filename);
                $path = public_path('whatsapp_template/'.$filename);
                if (is_file($path)) {
                    $url .= (str_contains($url, '?') ? '&' : '?').'v='.filemtime($path);
                }

                return $url;
            }

            return self::whatsappPublicFileUrl($filename);
        }

        public static function whatsappPlainMediaUrl(string $url): string
        {
            $url = trim($url);
            if ($url === '') {
                return '';
            }
            $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
            $base = ltrim(basename($path), '/');
            if ($base !== '' && preg_match('/^[A-Za-z0-9._-]+$/', $base)) {
                return self::whatsappPublicFileUrl($base);
            }
            $cut = strtok($url, '?');

            return $cut !== false ? $cut : $url;
        }

        public static function whatsappEnabled(string $slug, $smsTmp = null): bool
        {
            try {
                if ($slug !== '' && \Illuminate\Support\Facades\Schema::hasTable('whatsapp_templates')) {
                    $status = DB::table('whatsapp_templates')->where('slug', $slug)->value('status');
                    if ((int) $status === 1) {
                        return true;
                    }
                }
            } catch (\Throwable $e) {
            }

            return $smsTmp && (int) $smsTmp->status === 1;
        }

        public static function loginOtpRecentlySent($user, int $seconds = 60): bool
        {
            if (! $user || empty($user->otp) || empty($user->otp_created_at)) {
                return false;
            }
            try {
                return \Carbon\Carbon::parse($user->otp_created_at)->gt(\Carbon\Carbon::now()->subSeconds($seconds));
            } catch (\Throwable $e) {
                return false;
            }
        }

        public static function sendQueuedWhatsapp(string $slug, $toUserId, string $mobile, string $content, $smsTmp = null): void
        {
            if ($mobile === '' || ! self::whatsappEnabled($slug, $smsTmp)) {
                return;
            }
            self::sendWhatasappMsg([
                'mobile_number' => $mobile,
                'content' => $content,
                'template_id' => (string) ($smsTmp->template_id ?? ''),
                'slug' => $slug,
            ]);
            try {
                if (! \Illuminate\Support\Facades\Schema::hasTable('messages') || ! $toUserId) {
                    return;
                }
                DB::table('messages')->insert([
                    'user_id' => 1,
                    'to_user_id' => $toUserId,
                    'subject' => $slug,
                    'msg_source' => 'WHATSAPP',
                    'template_id' => (string) ($smsTmp->template_id ?? ''),
                    'content' => $content,
                    'status' => 1,
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ]);
            } catch (\Throwable $e) {
            }
        }

        public static function sendWhatasappMsg($data){

            $w_api = DB::table('companies')->where('id', 1)->first(['whatsapp_request_url','whatsapp_api_method']);

            if (!$w_api) {
                return 1;
            }

            $rawUrl = trim((string) ($w_api->whatsapp_request_url ?? ''));

            if ($rawUrl === '' || $rawUrl === '0') {
                return 1;
            }

                $content = (string) ($data['content'] ?? '');
                $templateId = (string) ($data['template_id'] ?? '');
                $slug = (string) ($data['slug'] ?? '');
                $attach = !empty($data['attach_logo']);
                $attachImage = array_key_exists('attach_image', $data) ? !empty($data['attach_image']) : null;
                $imageUrl = (string) ($data['image_url'] ?? '');
                $tpl = null;

                if (\Illuminate\Support\Facades\Schema::hasTable('whatsapp_templates')) {
                    if ($slug !== '') {
                        $tpl = DB::table('whatsapp_templates')->where('slug', $slug)->first();
                    }
                    if (!$tpl && $templateId !== '') {
                        $tpl = DB::table('whatsapp_templates')->where('template_id', $templateId)->first();
                    }
                    if ($tpl && !array_key_exists('attach_logo', $data)) {
                        $attach = (int) ($tpl->attach_logo ?? 0) === 1;
                    }
                    if ($tpl && $attachImage === null) {
                        $attachImage = (int) ($tpl->attach_image ?? 0) === 1;
                    }
                    if ($tpl && $content === '' && (int) ($tpl->status ?? 0) === 1) {
                        $content = (string) $tpl->content;
                        $templateId = (string) ($tpl->template_id ?: $templateId);
                    }
                    if ($imageUrl === '' && $tpl && !empty($tpl->image)) {
                        $imageUrl = self::whatsappTemplateImageUrl((string) $tpl->image);
                    }
                }
                if ($imageUrl === '' && !empty($data['image'])) {
                    $imageUrl = self::whatsappTemplateImageUrl((string) $data['image']);
                }
                $attachImage = (bool) $attachImage;

                $logoUrl = self::whatsappPlainMediaUrl(self::companyWhatsappLogoUrl());
                $imageUrl = self::whatsappPlainMediaUrl($imageUrl);
                $content = str_replace(['{LOGO}', '{LOGO_URL}', '{IMG}', '{IMAGE}', '{TEMPLATE_IMAGE}'], '', $content);
                $content = trim($content);

                $mediaFiles = [];
                if ($attachImage && $imageUrl !== '') {
                    $mediaFiles[] = $imageUrl;
                }
                if ($attach && $logoUrl !== '' && ! in_array($logoUrl, $mediaFiles, true)) {
                    $mediaFiles[] = $logoUrl;
                }
                $isOtp = strtolower($slug) === 'otp';
                if ($isOtp && count($mediaFiles) > 1) {
                    $mediaFiles = array_slice($mediaFiles, 0, 1);
                }
                $mediaCaption = $isOtp ? 'NETCELL PAY' : $content;

                $method = $w_api->whatsapp_api_method ?: 'GET';
                $hasMediaPlaceholder = str_contains($rawUrl, '{IMG}')
                    || str_contains($rawUrl, '{IMAGE}')
                    || str_contains($rawUrl, '{MEDIA}')
                    || str_contains($rawUrl, '{MEDIA_URL}')
                    || str_contains($rawUrl, '{FILE}')
                    || str_contains($rawUrl, '{LOGO}');

                $buildUrl = function (string $message, string $image, bool $asMedia) use ($rawUrl, $data, $templateId, $logoUrl, $hasMediaPlaceholder) {
                    $u = $rawUrl;
                    $u = str_replace('{MOB}', (string) ($data['mobile_number'] ?? ''), $u);
                    $u = str_replace('{MSG}', urlencode($message), $u);
                    $u = str_replace('{TMP_ID}', $templateId, $u);
                    $u = str_replace('{TMPID}', $templateId, $u);
                    $u = str_replace('{LOGO}', urlencode($asMedia ? $image : $logoUrl), $u);
                    $u = str_replace('{IMG}', urlencode($image), $u);
                    $u = str_replace('{IMAGE}', urlencode($image), $u);
                    $u = str_replace('{MEDIA}', urlencode($image), $u);
                    $u = str_replace('{MEDIA_URL}', urlencode($image), $u);
                    $u = str_replace('{FILE}', urlencode($image), $u);
                    if ($asMedia && $image !== '' && ! $hasMediaPlaceholder) {
                        if (preg_match('/([?&])type=text\b/i', $u)) {
                            $u = preg_replace('/([?&])type=text\b/i', '$1type=image', $u);
                        } elseif (! preg_match('/[?&]type=/i', $u)) {
                            $u .= (str_contains($u, '?') ? '&' : '?').'type=image';
                        }
                        $name = basename((string) (parse_url($image, PHP_URL_PATH) ?: 'netcell.png'));
                        $sep = str_contains($u, '?') ? '&' : '?';
                        $u .= $sep.'file='.rawurlencode($image)
                            .'&media_url='.rawurlencode($image)
                            .'&caption='.rawurlencode($message)
                            .'&filename='.rawurlencode($name)
                            .'&mediatype=image';
                    }

                    return $u;
                };

                $header = [];
                $parameters = '';
                foreach ($mediaFiles as $idx => $img) {
                    Common::curl($buildUrl($mediaCaption, $img, true), $method, $parameters, $header, 'yes', 'WHATSAPP_URL', 'WAS'.date('YmdHis').rand(11111, 999999).'I'.$idx);
                }
                Common::curl($buildUrl($content, '', false), $method, $parameters, $header, 'yes', 'WHATSAPP_URL', 'WAS'.date('YmdHis').rand(11111, 999999));

                return 0;
        }



        public static function curl($url, $method = 'GET', $parameters = null, $header = [], $log = "no", $modal = "none", $txnid = "none")

    {   

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_setopt($curl, CURLOPT_URL, $url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);

        curl_setopt($curl, CURLOPT_ENCODING, "");

        curl_setopt($curl, CURLOPT_TIMEOUT, 180);

        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        if($parameters != ""){

            curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);

        }



        if(sizeof($header) > 0){

            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        }

        

        $response = curl_exec($curl);

        $err = curl_error($curl);

        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($log != "no"){

            try {
                DB::table('apilogs')->insert([

                    "url" => $url,

                    "modal" => $modal,

                    "txnid" => $txnid,

                    "header" => json_encode($header),

                    "request" => json_encode($parameters),

                    "response" => $response,

                    'created_at' => Carbon::now(),

                    'updated_at' => Carbon::now()

                ]);
            } catch (\Throwable $e) {
                // API logging should never break the primary request flow.
            }

        }



        return ["response" => $response, "error" => $err, 'code' => $code];

    }

    /**
     * Parse User-Agent into browser / platform / device details.
     */
    public static function parseUserAgent(?string $ua = null): array
    {
        $ua = $ua ?: (string) (request()->userAgent() ?? '');
        $uaLower = strtolower($ua);

        $browser = 'Unknown';
        if (str_contains($uaLower, 'edg/')) {
            $browser = 'Microsoft Edge';
        } elseif (str_contains($uaLower, 'opr/') || str_contains($uaLower, 'opera')) {
            $browser = 'Opera';
        } elseif (str_contains($uaLower, 'chrome/') && !str_contains($uaLower, 'edg/')) {
            $browser = 'Chrome';
        } elseif (str_contains($uaLower, 'safari/') && !str_contains($uaLower, 'chrome/')) {
            $browser = 'Safari';
        } elseif (str_contains($uaLower, 'firefox/')) {
            $browser = 'Firefox';
        } elseif (str_contains($uaLower, 'msie') || str_contains($uaLower, 'trident/')) {
            $browser = 'Internet Explorer';
        }

        if (preg_match('/edg\/([\d.]+)/i', $ua, $m)
            || preg_match('/chrome\/([\d.]+)/i', $ua, $m)
            || preg_match('/firefox\/([\d.]+)/i', $ua, $m)
            || preg_match('/version\/([\d.]+).*safari/i', $ua, $m)
            || preg_match('/opr\/([\d.]+)/i', $ua, $m)
        ) {
            $browser .= ' ' . $m[1];
        }

        $platform = 'Unknown';
        if (str_contains($uaLower, 'windows nt 10')) {
            $platform = 'Windows 10/11';
        } elseif (str_contains($uaLower, 'windows nt 6.3')) {
            $platform = 'Windows 8.1';
        } elseif (str_contains($uaLower, 'windows nt 6.2')) {
            $platform = 'Windows 8';
        } elseif (str_contains($uaLower, 'windows nt 6.1')) {
            $platform = 'Windows 7';
        } elseif (str_contains($uaLower, 'windows')) {
            $platform = 'Windows';
        } elseif (str_contains($uaLower, 'android')) {
            $platform = 'Android';
            if (preg_match('/android\s([\d.]+)/i', $ua, $m)) {
                $platform .= ' ' . $m[1];
            }
        } elseif (str_contains($uaLower, 'iphone') || str_contains($uaLower, 'ipad') || str_contains($uaLower, 'ios')) {
            $platform = 'iOS';
            if (preg_match('/os\s([\d_]+)/i', $ua, $m)) {
                $platform .= ' ' . str_replace('_', '.', $m[1]);
            }
        } elseif (str_contains($uaLower, 'mac os x') || str_contains($uaLower, 'macintosh')) {
            $platform = 'macOS';
            if (preg_match('/mac os x\s([\d_]+)/i', $ua, $m)) {
                $platform .= ' ' . str_replace('_', '.', $m[1]);
            }
        } elseif (str_contains($uaLower, 'linux')) {
            $platform = 'Linux';
        }

        $deviceType = 'Desktop';
        if (str_contains($uaLower, 'ipad') || str_contains($uaLower, 'tablet')) {
            $deviceType = 'Tablet';
        } elseif (str_contains($uaLower, 'mobile') || str_contains($uaLower, 'iphone') || str_contains($uaLower, 'android')) {
            $deviceType = 'Mobile';
        }

        $device = 'Unknown';
        if (preg_match('/\(([^)]+)\)/', $ua, $m)) {
            $parts = array_map('trim', explode(';', $m[1]));
            $device = $parts[0] ?? 'Unknown';
            foreach ($parts as $part) {
                if (preg_match('/iphone|ipad|pixel|sm-|mi |redmi|oneplus|vivo|oppo|realme|huawei|motorola|nexus/i', $part)) {
                    $device = $part;
                    break;
                }
            }
        }

        return [
            'user_agent' => $ua,
            'browser' => $browser,
            'platform' => $platform,
            'device_type' => $deviceType,
            'device' => $device,
        ];
    }

    public static function ensureLoginHistoriesTable(): void
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('login_histories')) {
                self::ensureLoginHistoryColumns();

                return;
            }
        } catch (\Throwable $e) {
        }

        try {
            DB::statement("CREATE TABLE IF NOT EXISTS `login_histories` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED NOT NULL,
                `ip_address` VARCHAR(45) NULL,
                `login_path` VARCHAR(50) NULL,
                `user_agent` TEXT NULL,
                `browser` VARCHAR(120) NULL,
                `platform` VARCHAR(120) NULL,
                `device_type` VARCHAR(50) NULL,
                `device` VARCHAR(120) NULL,
                `latitude` DECIMAL(10,7) NULL,
                `longitude` DECIMAL(10,7) NULL,
                `created_at` TIMESTAMP NULL DEFAULT NULL,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `login_histories_user_id_index` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {
            \Log::warning('login_histories create failed: '.$e->getMessage());
        }
    }

    protected static function ensureLoginHistoryColumns(): void
    {
        $columns = [
            'user_agent' => 'TEXT NULL',
            'browser' => 'VARCHAR(120) NULL',
            'platform' => 'VARCHAR(120) NULL',
            'device_type' => 'VARCHAR(50) NULL',
            'device' => 'VARCHAR(120) NULL',
            'latitude' => 'DECIMAL(10,7) NULL',
            'longitude' => 'DECIMAL(10,7) NULL',
        ];

        foreach ($columns as $column => $definition) {
            try {
                if (! \Illuminate\Support\Facades\Schema::hasColumn('login_histories', $column)) {
                    DB::statement("ALTER TABLE `login_histories` ADD COLUMN `{$column}` {$definition}");
                }
            } catch (\Throwable $e) {
            }
        }
    }

    public static function fetchLoginHistoryForUser(?int $userId, int $limit = 50)
    {
        if (! $userId) {
            return collect();
        }

        try {
            self::ensureLoginHistoriesTable();

            $historyColumns = ['id', 'user_id', 'ip_address', 'login_path', 'created_at'];
            foreach (['user_agent', 'browser', 'platform', 'device_type', 'device', 'latitude', 'longitude'] as $column) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('login_histories', $column)) {
                    $historyColumns[] = $column;
                }
            }

            $select = array_map(static fn ($column) => 'lh.'.$column, $historyColumns);
            $select = array_merge($select, [
                'u.first_name',
                'u.middle_name',
                'u.last_name',
                'u.outlet_name',
                'u.mobile_number',
            ]);

            return DB::table('login_histories as lh')
                ->leftJoin('users as u', 'u.id', '=', 'lh.user_id')
                ->where('lh.user_id', $userId)
                ->orderByDesc('lh.id')
                ->take($limit)
                ->get($select)
                ->map(function ($row) use ($historyColumns) {
                    $name = trim(implode(' ', array_filter([
                        $row->first_name ?? '',
                        $row->middle_name ?? '',
                        $row->last_name ?? '',
                    ])));
                    if ($name === '') {
                        $name = $row->outlet_name ?: ('User #'.($row->user_id ?? ''));
                    }

                    $item = [
                        'id' => (int) ($row->id ?? 0),
                        'user_name' => $name,
                        'mobile_number' => $row->mobile_number ?? '',
                        'ip_address' => $row->ip_address ?? '',
                        'login_path' => $row->login_path ?? '',
                        'created_at' => $row->created_at ?? '',
                    ];

                    foreach (['user_agent', 'browser', 'platform', 'device_type', 'device', 'latitude', 'longitude'] as $column) {
                        if (in_array($column, $historyColumns, true)) {
                            $item[$column] = $row->{$column} ?? '';
                        } else {
                            $item[$column] = '';
                        }
                    }

                    $item['maps_url'] = self::googleMapsUrl($item['latitude'] ?? null, $item['longitude'] ?? null);

                    return $item;
                })
                ->values();
        } catch (\Throwable $e) {
            \Log::warning('fetchLoginHistoryForUser failed: '.$e->getMessage());

            return collect();
        }
    }

    public static function fetchProfileUser(?int $userId)
    {
        if (! $userId) {
            return null;
        }

        $select = [
            'users.first_name',
            'users.middle_name',
            'users.last_name',
            'users.outlet_name',
            'users.email_address',
            'users.mobile_number',
            'users.wallet_balance',
        ];

        foreach ([
            'date_of_birth',
            'city',
            'state',
            'district',
            'minium_balance',
            'profile_pic',
            'kyc_status',
            'callback_url',
            'ip_address',
        ] as $column) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasColumn('users', $column)) {
                    $select[] = 'users.'.$column;
                }
            } catch (\Throwable $e) {
            }
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('roles')) {
                $select[] = 'roles.role_name';
            }
        } catch (\Throwable $e) {
        }

        try {
            $query = DB::table('users')->where('users.id', $userId);
            if (in_array('roles.role_name', $select, true)) {
                $query->leftJoin('roles', 'users.role_id', '=', 'roles.id');
            }

            return $query->first($select);
        } catch (\Throwable $e) {
            \Log::warning('fetchProfileUser failed: '.$e->getMessage());

            try {
                return DB::table('users')
                    ->where('id', $userId)
                    ->first([
                        'first_name',
                        'middle_name',
                        'last_name',
                        'outlet_name',
                        'email_address',
                        'mobile_number',
                        'wallet_balance',
                    ]);
            } catch (\Throwable $e2) {
                return null;
            }
        }
    }

    public static function loginCoordinatesFromRequest($request = null): array
    {
        $req = $request ?: request();
        $latRaw = $req->input('latitude', $req->input('lat'));
        $lngRaw = $req->input('longitude', $req->input('lng', $req->input('long')));

        if (! is_numeric($latRaw) || ! is_numeric($lngRaw)) {
            return [null, null];
        }

        $lat = round((float) $latRaw, 7);
        $lng = round((float) $lngRaw, 7);
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat == 0.0 && $lng == 0.0)) {
            return [null, null];
        }

        return [$lat, $lng];
    }

    public static function googleMapsUrl($lat, $lng): ?string
    {
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;
        if ($lat == 0.0 && $lng == 0.0) {
            return null;
        }

        return 'https://www.google.com/maps?q='.rawurlencode($lat.','.$lng);
    }

    public static function recordLoginHistory(int $userId, string $loginPath = 'WEB'): void
    {
        try {
            self::ensureLoginHistoriesTable();
        } catch (\Throwable $e) {
            return;
        }

        $parsed = self::parseUserAgent();
        $row = [
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'login_path' => $loginPath,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        foreach (['user_agent', 'browser', 'platform', 'device_type', 'device'] as $col) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('login_histories', $col)) {
                $row[$col] = $parsed[$col] ?? null;
            }
        }

        [$lat, $lng] = self::loginCoordinatesFromRequest();
        if (\Illuminate\Support\Facades\Schema::hasColumn('login_histories', 'latitude')) {
            $row['latitude'] = $lat;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('login_histories', 'longitude')) {
            $row['longitude'] = $lng;
        }

        try {
            DB::table('login_histories')->insert($row);
        } catch (\Throwable $e) {
            \Log::warning('recordLoginHistory failed: '.$e->getMessage());
        }
    }

    public static function roleCodeFromId(int $roleId): string
    {
        return match ($roleId) {
            3 => 'API',
            4 => 'MD',
            5 => 'DST',
            6 => 'RET',
            1 => 'ADM',
            default => 'USR',
        };
    }

    public static function buildUserCode(int $roleId, int $sequence): string
    {
        return self::roleCodeFromId($roleId) . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public static function getUserRoleSequence(int $userId, ?int $roleId = null): int
    {
        if ($roleId === null) {
            $roleId = (int) DB::table('users')->where('id', $userId)->value('role_id');
        }

        return (int) DB::table('users')
            ->where('role_id', $roleId)
            ->where('id', '<=', $userId)
            ->count();
    }

    public static function getUserRoleSequencesForIds(array $userIds): array
    {
        $userIds = array_values(array_filter(array_map('intval', $userIds)));
        if ($userIds === []) {
            return [];
        }

        $placeholders = implode(',', $userIds);
        $rows = DB::select("
            SELECT u.id, (
                SELECT COUNT(*)
                FROM users u2
                WHERE u2.role_id = u.role_id AND u2.id <= u.id
            ) AS role_seq
            FROM users u
            WHERE u.id IN ($placeholders)
        ");

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->id] = (int) $row->role_seq;
        }

        return $map;
    }

    public static function buildUserCodeForUser($user): string
    {
        if (!$user) {
            return 'USR0000';
        }

        $userId = (int) ($user->id ?? 0);
        $roleId = (int) ($user->role_id ?? 0);
        if ($userId <= 0) {
            return self::buildUserCode($roleId, 0);
        }

        return self::buildUserCode($roleId, self::getUserRoleSequence($userId, $roleId));
    }

    public static function roleIdFromCodePrefix(string $prefix): ?int
    {
        return match (strtoupper($prefix)) {
            'API' => 3,
            'MD' => 4,
            'DST' => 5,
            'RET' => 6,
            'ADM' => 1,
            default => null,
        };
    }

    public static function findUserIdByCode(string $userCode): ?int
    {
        if (!preg_match('/^(API|MD|DST|RET|ADM|USR)(\d+)$/i', trim($userCode), $matches)) {
            return null;
        }

        $roleId = self::roleIdFromCodePrefix($matches[1]);
        $sequence = (int) $matches[2];
        if ($sequence < 1 || $roleId === null) {
            return null;
        }

        $row = DB::selectOne('
            SELECT u.id
            FROM users u
            WHERE u.role_id = ?
            AND (
                SELECT COUNT(*)
                FROM users u2
                WHERE u2.role_id = u.role_id AND u2.id <= u.id
            ) = ?
            LIMIT 1
        ', [$roleId, $sequence]);

        return $row ? (int) $row->id : null;
    }

    public static function fcmServerKey(): ?string
    {
        $key = trim((string) env('FCM_SERVER_KEY', ''));
        if ($key !== '') {
            return $key;
        }

        try {
            $key = trim((string) \App\Services\SystemSettingService::get('fcm_server_key', ''));
        } catch (\Throwable $e) {
            $key = '';
        }

        return $key !== '' ? $key : null;
    }

    public static function pushNotifyUser(int $userId, string $title, string $body, array $data = [], string $subject = 'admin_notification')
    {
        if ($userId <= 0) {
            return false;
        }

        $msgId = 0;
        $storedSubject = trim($title) !== '' ? $title : $subject;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('messages')) {
                $msgId = (int) DB::table('messages')->insertGetId([
                    'user_id' => 1,
                    'to_user_id' => $userId,
                    'subject' => $storedSubject,
                    'msg_source' => 'PUSH',
                    'template_id' => 0,
                    'content' => $body,
                    'status' => 0,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        } catch (\Throwable $e) {
        }

        $setStatus = function (int $status) use ($msgId) {
            if ($msgId <= 0) {
                return;
            }
            try {
                DB::table('messages')->where('id', $msgId)->update([
                    'status' => $status,
                    'updated_at' => Carbon::now(),
                ]);
            } catch (\Throwable $e) {
            }
        };

        try {
            foreach (['fcm_token', 'device_token'] as $column) {
                if (! \Illuminate\Support\Facades\Schema::hasColumn('users', $column)) {
                    DB::statement("ALTER TABLE `users` ADD COLUMN `{$column}` TEXT NULL");
                }
            }

            $user = DB::table('users')->where('id', $userId)->first(['fcm_token', 'device_token']);
            $token = trim((string) ($user->fcm_token ?? ($user->device_token ?? '')));
            if ($token === '') {
                $setStatus(2);
                return 'no_token';
            }

            $serverKey = self::fcmServerKey();
            if (! $serverKey) {
                $setStatus(3);
                return false;
            }

            $payloadData = ['title' => (string) $title, 'body' => (string) $body];
            foreach ($data as $key => $value) {
                $payloadData[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value);
            }

            $payload = json_encode([
                'to' => $token,
                'priority' => 'high',
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                ],
                'data' => $payloadData,
            ]);

            self::curl(
                'https://fcm.googleapis.com/fcm/send',
                'POST',
                $payload,
                [
                    'Content-Type: application/json',
                    'Authorization: key='.$serverKey,
                ],
                'yes',
                'FCM',
                (string) time()
            );

            $setStatus(1);
            return true;
        } catch (\Throwable $e) {
            \Log::warning('pushNotifyUser failed: '.$e->getMessage());
            $setStatus(3);

            return false;
        }
    }

    }