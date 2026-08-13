<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WebsiteCmsController extends Controller
{
    public const KINDS = [
        'ad' => 'Create Ads',
        'banner' => 'Banner Setting',
        'popup' => 'Popup Master',
    ];

    public const PAGES = [
        'home' => ['name' => 'Home', 'path' => '/', 'editable' => true],
        'about-us' => ['name' => 'About Us', 'path' => '/about-us', 'editable' => true],
        'services' => ['name' => 'Services', 'path' => '/services', 'editable' => true],
        'contact-us' => ['name' => 'Contact Us', 'path' => '/contact-us', 'editable' => true],
        'privacy-policy' => ['name' => 'Privacy Policy', 'path' => '/privacy-policy', 'editable' => true],
        'term-and-condition' => ['name' => 'Terms & Conditions', 'path' => '/term-and-condition', 'editable' => true],
        'refunds' => ['name' => 'Refunds', 'path' => '/refunds', 'editable' => true],
        'users-login' => ['name' => 'User Login', 'path' => '/users/login', 'editable' => false],
    ];

    public static function ensureTable(): void
    {
        if (Schema::hasTable('website_media')) {
            return;
        }

        Schema::create('website_media', function ($table) {
            $table->id();
            $table->string('kind', 20);
            $table->string('title')->nullable();
            $table->string('image')->nullable();
            $table->string('link_url', 500)->nullable();
            $table->text('body')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('deleted_at')->default(0);
            $table->timestamps();
        });
    }

    public static function ensurePagesTable(): void
    {
        if (!Schema::hasTable('website_pages')) {
            Schema::create('website_pages', function ($table) {
                $table->id();
                $table->string('slug', 64)->unique();
                $table->string('name');
                $table->string('path', 120);
                $table->string('title')->nullable();
                $table->string('heading')->nullable();
                $table->longText('body')->nullable();
                $table->timestamps();
            });
        }

        foreach (self::PAGES as $slug => $meta) {
            if (!($meta['editable'] ?? false)) {
                continue;
            }
            $exists = DB::table('website_pages')->where('slug', $slug)->exists();
            if ($exists) {
                continue;
            }
            DB::table('website_pages')->insert([
                'slug' => $slug,
                'name' => $meta['name'],
                'path' => $meta['path'],
                'title' => $meta['name'],
                'heading' => '',
                'body' => '',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    public function ads()
    {
        return $this->mediaPage('ad');
    }

    public function banners()
    {
        return $this->mediaPage('banner');
    }

    public function popups()
    {
        return $this->mediaPage('popup');
    }

    public function pages()
    {
        self::ensurePagesTable();
        $company = $this->company();
        $domain = trim((string) ($company->domain ?? ''));
        $siteBase = $domain === ''
            ? rtrim((string) env('USER_HOST', ''), '/')
            : (preg_match('#^https?://#i', $domain) ? rtrim($domain, '/') : 'https://'.ltrim($domain, '/'));
        if ($siteBase === '') {
            $siteBase = rtrim((string) config('app.url'), '/');
        }

        $saved = DB::table('website_pages')->get()->keyBy('slug');
        $pages = [];
        foreach (self::PAGES as $slug => $meta) {
            $row = $saved[$slug] ?? null;
            $pages[] = [
                'slug' => $slug,
                'name' => $meta['name'],
                'path' => $meta['path'],
                'editable' => (bool) ($meta['editable'] ?? false),
                'title' => $row->title ?? $meta['name'],
            ];
        }

        return view('admin.website.pages', [
            'company' => $company,
            'siteBase' => $siteBase,
            'pages' => $pages,
        ]);
    }

    public function pageGet(Request $post)
    {
        self::ensurePagesTable();
        $slug = (string) $post->slug;
        if (!isset(self::PAGES[$slug]) || empty(self::PAGES[$slug]['editable'])) {
            return response()->json(['type' => 'error', 'message' => 'This page cannot be edited.']);
        }

        $row = DB::table('website_pages')->where('slug', $slug)->first();
        if (!$row) {
            return response()->json(['type' => 'error', 'message' => 'Page not found.']);
        }

        return response()->json(['type' => 'success', 'data' => $row]);
    }

    public function pageSave(Request $post)
    {
        self::ensurePagesTable();
        $slug = (string) $post->slug;
        if (!isset(self::PAGES[$slug]) || empty(self::PAGES[$slug]['editable'])) {
            return response()->json(['type' => 'error', 'message' => 'This page cannot be edited.']);
        }

        $validator = \Validator::make($post->all(), [
            'title' => 'required|string|max:190',
            'heading' => 'nullable|string|max:190',
            'body' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['type' => 'error', 'message' => $validator->errors()->first()]);
        }

        DB::table('website_pages')->updateOrInsert(
            ['slug' => $slug],
            [
                'name' => self::PAGES[$slug]['name'],
                'path' => self::PAGES[$slug]['path'],
                'title' => $post->title,
                'heading' => $post->heading,
                'body' => (string) $post->body,
                'updated_at' => Carbon::now(),
            ]
        );

        return response()->json(['type' => 'success', 'message' => 'Page saved. It will show on the user website.']);
    }

    public function setting()
    {
        return view('admin.website.setting', ['company' => $this->company()]);
    }

    public function policy()
    {
        return view('admin.website.policy', ['company' => $this->company()]);
    }

    public function saveSetting(Request $post)
    {
        $company = $this->company();
        if (!$company) {
            return response()->json(['type' => 'error', 'message' => 'Company profile not found.']);
        }

        $validator = \Validator::make($post->all(), [
            'company_name' => 'required|string|max:190',
            'support_number' => 'nullable|string|max:30',
            'support_email' => 'nullable|email|max:190',
            'company_address' => 'nullable|string',
            'meta_kewords' => 'nullable|string',
            'header_value' => 'nullable|string',
            'footer_value' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['type' => 'error', 'message' => $validator->errors()->first()]);
        }

        DB::table('companies')->where('id', $company->id)->update([
            'company_name' => $post->company_name,
            'support_number' => $post->support_number,
            'support_email' => $post->support_email,
            'company_address' => $post->company_address,
            'meta_kewords' => $post->meta_kewords,
            'header_value' => $post->header_value,
            'footer_value' => $post->footer_value,
            'updated_at' => Carbon::now(),
        ]);

        return response()->json(['type' => 'success', 'message' => 'Website setting saved.']);
    }

    public function savePolicy(Request $post)
    {
        $company = $this->company();
        if (!$company) {
            return response()->json(['type' => 'error', 'message' => 'Company profile not found.']);
        }

        DB::table('companies')->where('id', $company->id)->update([
            'privacy_policy' => (string) $post->privacy_policy,
            'terms_and_conditions' => (string) $post->terms_and_conditions,
            'refund_policy' => (string) $post->refund_policy,
            'updated_at' => Carbon::now(),
        ]);

        return response()->json(['type' => 'success', 'message' => 'Web policy saved.']);
    }

    public function mediaList(Request $post)
    {
        self::ensureTable();
        $kind = $this->validKind($post->kind);
        $list = DB::table('website_media')
            ->where('kind', $kind)
            ->where('deleted_at', '!=', 1)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        if ($list->isEmpty()) {
            return '<h4 class="text-center text-secondary my-3">No record found</h4>';
        }

        $html = '<table id="scroll-vertical" class="table table-bordered dt-responsive nowrap align-middle" style="width:100%">
            <thead><tr><th>ID</th><th>Title</th><th>Image</th><th>Link</th><th>Status</th><th>Action</th></tr></thead><tbody>';
        $i = 1;
        foreach ($list as $row) {
            $img = $row->image ? '<img src="'.e(website_media_admin_url($row->image)).'" style="height:80px;width:140px;object-fit:cover;border-radius:8px">' : '-';
            $status = ((int) $row->status === 1) ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>';
            $html .= '<tr>
                <td>'.$i.'</td>
                <td>'.e($row->title).'</td>
                <td>'.$img.'</td>
                <td>'.e($row->link_url).'</td>
                <td>'.$status.'</td>
                <td>
                    <a href="javascript:void(0)" id="'.$row->id.'" class="badge text-bg-info editData">Edit</a>
                    <a href="javascript:void(0)" id="'.$row->id.'" class="badge text-bg-danger deleteData">Delete</a>
                </td>
            </tr>';
            $i++;
        }

        return $html.'</tbody></table>';
    }

    public function mediaGet(Request $post)
    {
        self::ensureTable();
        $row = DB::table('website_media')->where('id', $post->id)->where('deleted_at', '!=', 1)->first();
        if (!$row) {
            return response()->json(['type' => 'error', 'message' => 'Record not found.']);
        }

        return response()->json(['type' => 'success', 'data' => $row]);
    }

    public function mediaSave(Request $post)
    {
        self::ensureTable();
        $kind = $this->validKind($post->kind);
        $rules = [
            'title' => 'required|string|max:190',
            'status' => 'required|in:0,1',
            'sort_order' => 'nullable|numeric',
            'link_url' => 'nullable|string|max:500',
            'body' => 'nullable|string',
        ];
        if ((int) $post->edit_id === 0) {
            $rules['image'] = 'required|mimes:jpeg,jpg,png,webp,gif|max:4096';
        } else {
            $rules['image'] = 'nullable|mimes:jpeg,jpg,png,webp,gif|max:4096';
        }

        $validator = \Validator::make($post->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['type' => 'error', 'message' => $validator->errors()->first()]);
        }

        $payload = [
            'kind' => $kind,
            'title' => $post->title,
            'link_url' => $post->link_url,
            'body' => $post->body,
            'status' => (int) $post->status,
            'sort_order' => (int) ($post->sort_order ?: 0),
            'updated_at' => Carbon::now(),
        ];

        if ($post->hasFile('image')) {
            $filename = $kind.'_'.time().'.'.$post->file('image')->extension();
            $post->file('image')->move(website_media_dir(), $filename);
            $payload['image'] = $filename;
        }

        if ((int) $post->edit_id > 0) {
            DB::table('website_media')->where('id', $post->edit_id)->update($payload);
            return response()->json(['type' => 'success', 'message' => 'Updated successfully.']);
        }

        $payload['deleted_at'] = 0;
        $payload['created_at'] = Carbon::now();
        DB::table('website_media')->insert($payload);

        return response()->json(['type' => 'success', 'message' => 'Created successfully.']);
    }

    public function mediaDelete(Request $post)
    {
        self::ensureTable();
        $ok = DB::table('website_media')->where('id', $post->id)->update(['deleted_at' => 1, 'updated_at' => Carbon::now()]);

        return response()->json([
            'type' => $ok ? 'success' : 'error',
            'message' => $ok ? 'Deleted successfully.' : 'Something went wrong.',
        ]);
    }

    public function showImage(string $filename)
    {
        $filename = basename($filename);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($filename === '' || !in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            abort(404);
        }

        $path = website_media_disk_path($filename);
        if (!$path) {
            abort(404);
        }

        return response()->file($path);
    }

    private function mediaPage(string $kind)
    {
        self::ensureTable();

        return view('admin.website.media', [
            'kind' => $kind,
            'title' => self::KINDS[$kind],
        ]);
    }

    private function validKind($kind): string
    {
        $kind = (string) $kind;

        return array_key_exists($kind, self::KINDS) ? $kind : 'ad';
    }

    private function company()
    {
        return DB::table('companies')->where('deleted_at', '!=', 1)->orderBy('id')->first();
    }
}
