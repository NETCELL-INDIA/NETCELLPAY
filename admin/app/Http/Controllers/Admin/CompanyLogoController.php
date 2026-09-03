<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyLogoController extends Controller
{
    public function index()
    {
        $company = DB::table('companies')->where('id', 1)->first([
            'company_logo',
            'company_icon',
            'invoice_logo',
        ]);

        $slots = array_map(function (array $slot) {
            $file = $slot['file'];
            $slot['url'] = $file !== '' ? admin_company_logo($file) : null;
            $slot['pixels'] = $this->pixelSize($file);

            return $slot;
        }, $this->slots($company));

        return view('admin.company.logos', [
            'slots' => $slots,
        ]);
    }

    public function update(Request $post)
    {
        $validator = \Validator::make($post->all(), [
            'slot' => 'required|in:company_logo,company_icon,invoice_logo',
            'image' => 'required|mimes:jpeg,jpg,png,webp|max:5120',
        ]);
        if ($validator->fails()) {
            return response()->json(['type' => 'error', 'message' => $validator->errors()->first()]);
        }

        $slot = (string) $post->slot;
        $company = DB::table('companies')->where('id', 1)->first([$slot]);
        $oldName = (string) ($company->{$slot} ?? '');

        $file = $post->file('image');
        $ext = strtolower((string) ($file->getClientOriginalExtension() ?: 'png'));
        $prefix = [
            'company_logo' => 'wordmark',
            'company_icon' => 'icon',
            'invoice_logo' => 'invoice',
        ][$slot];
        $name = $prefix.'_'.time().'_'.bin2hex(random_bytes(3)).'.'.$ext;

        $this->storeLogo($file, $name);
        if ($oldName !== '' && $oldName !== $name) {
            $this->deleteLogo($oldName);
        }

        DB::table('companies')->whereIn('id', [1, 2])->update([$slot => $name]);

        return response()->json([
            'type' => 'success',
            'message' => 'Logo saved',
            'url' => admin_company_logo($name),
        ]);
    }

    private function slots(?object $company): array
    {
        return [
            [
                'key' => 'company_logo',
                'title' => 'Company Logo (Wordmark)',
                'shape' => 'wide',
                'used_for' => [
                    'Admin header / sidebar',
                    'User portal header',
                    'Admin login left panel',
                    'User login page',
                    'Email header (when template uses company logo)',
                ],
                'display' => 'Screen height 42–48px (header), login up to 110px height / 480px width',
                'upload' => 'PNG, transparent background. Width 1600–2000px, height 320–400px (wide). Max 5MB.',
                'ratio' => 'About 5 : 1 (wide text logo, not round)',
                'file' => (string) ($company->company_logo ?? ''),
            ],
            [
                'key' => 'company_icon',
                'title' => 'Company Icon (Round)',
                'shape' => 'square',
                'used_for' => [
                    'Browser favicon (32×32)',
                    'Apple touch icon (180×180)',
                    'Company list thumbnail',
                    'WhatsApp company logo On ({LOGO})',
                ],
                'display' => 'Favicon 32×32, app icon 180×180, list 40×40',
                'upload' => 'PNG, square, transparent. 1024×1024px (minimum 512×512). Max 5MB.',
                'ratio' => '1 : 1 (square / round)',
                'file' => (string) ($company->company_icon ?? ''),
            ],
            [
                'key' => 'invoice_logo',
                'title' => 'Invoice Logo',
                'shape' => 'square',
                'used_for' => [
                    'Invoice / receipt print',
                    'PDF documents that use invoice_logo',
                ],
                'display' => 'Print about 80–120px height',
                'upload' => 'PNG, transparent. Square 800×800px, or wide 1000×250px. Max 5MB.',
                'ratio' => '1 : 1 or wide 4 : 1',
                'file' => (string) ($company->invoice_logo ?? ''),
            ],
        ];
    }

    private function pixelSize(string $filename): string
    {
        $filename = basename($filename);
        if ($filename === '') {
            return 'No file';
        }
        $path = public_path('company_logo/'.$filename);
        if (! is_file($path)) {
            return 'File missing';
        }
        $info = @getimagesize($path);
        if (! $info) {
            return 'Unknown';
        }

        return $info[0].' × '.$info[1].' px';
    }

    private function storeLogo(\Illuminate\Http\UploadedFile $file, string $name): void
    {
        $bytes = (string) file_get_contents($file->getRealPath());
        foreach ($this->logoDirectories() as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($dir.DIRECTORY_SEPARATOR.$name, $bytes);
        }
    }

    private function deleteLogo(string $name): void
    {
        $name = basename($name);
        if ($name === '' || str_contains($name, '..')) {
            return;
        }
        foreach ($this->logoDirectories() as $dir) {
            $path = $dir.DIRECTORY_SEPARATOR.$name;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function logoDirectories(): array
    {
        return [
            public_path('company_logo'),
            dirname(base_path()).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'company_logo',
        ];
    }
}
