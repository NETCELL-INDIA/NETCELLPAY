<?php

namespace App\Http\Controllers\Admin;

use App\Common;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WhatsappTemplateController extends Controller
{
    public function index()
    {
        $this->ensureTable();
        $this->seedDefaults();
        $data['categories'] = Schema::hasTable('message_categories')
            ? DB::table('message_categories')->get()
            : collect();

        return view('admin.company.whatsapp-template', $data);
    }

    public function fetchAll()
    {
        $this->ensureTable();
        $list = DB::table('whatsapp_templates')->orderBy('id', 'DESC')->get();
        if ($list->count() === 0) {
            echo '<h4 class="text-center text-secondary my-3">No record found</h4>';
            return;
        }

        $output = '<table id="scroll-vertical" class="table table-bordered dt-responsive nowrap align-middle" style="width:100%">
            <thead>
              <tr>
                <th>ID</th>
                <th>Category</th>
                <th>Template ID</th>
                <th>Content</th>
                <th>Company Logo</th>
                <th>Manual Image</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>';
        $i = 1;
        foreach ($list as $row) {
            $status = ((string) $row->status === '1')
                ? '<span class="badge rounded-pill text-bg-success">Active</span>'
                : '<span class="badge rounded-pill text-bg-danger">Deactive</span>';
            $logo = !empty($row->attach_logo)
                ? '<span class="badge rounded-pill text-bg-info">On</span>'
                : '<span class="badge rounded-pill text-bg-secondary">Off</span>';
            $imgFile = (string) ($row->image ?? '');
            $imgOn = !empty($row->attach_image);
            if ($imgFile !== '') {
                $imgUrl = e(Common::whatsappTemplateImageUrl($imgFile));
                $manual = '<img src="'.$imgUrl.'" alt="" style="width:36px;height:36px;object-fit:contain;border-radius:6px;background:#f3f6f9;"> '
                    .($imgOn ? '<span class="badge rounded-pill text-bg-info">On</span>' : '<span class="badge rounded-pill text-bg-secondary">Off</span>');
            } else {
                $manual = '<span class="badge rounded-pill text-bg-secondary">No image</span>';
            }
            $preview = e(\Illuminate\Support\Str::limit((string) $row->content, 80));
            $output .= '<tr>
                <td>'.$i.'</td>
                <td>'.e(ucwords(str_replace('_', ' ', (string) $row->slug))).'</td>
                <td>'.e((string) $row->template_id).'</td>
                <td>'.$preview.'</td>
                <td>'.$logo.'</td>
                <td>'.$manual.'</td>
                <td>'.$status.'</td>
                <td>
                    <a id="'.$row->id.'" class="badge text-bg-success sendTemplate"><i class="ri-whatsapp-line align-bottom"></i> Send</a>
                    <a id="'.$row->id.'" class="badge text-bg-secondary editDetails"><i class="ri-pencil-fill align-bottom"></i> Edit</a>
                    <a id="'.$row->id.'" class="badge text-bg-danger deleteData"><i class="ri-delete-bin-fill align-bottom"></i> Delete</a>
                </td>
              </tr>';
            $i++;
        }
        $output .= '</tbody></table>';
        echo $output;
    }

    public function deleteData(Request $post)
    {
        $this->ensureTable();
        $row = DB::table('whatsapp_templates')->where('id', $post->id)->first();
        if ($row && !empty($row->image)) {
            $this->deleteTemplateImage((string) $row->image);
        }
        $delete = DB::table('whatsapp_templates')->where('id', $post->id)->delete();

        return [
            'type' => $delete ? 'success' : 'error',
            'message' => $delete ? 'Deleted successfully' : 'Something went wrong!',
        ];
    }

    public function getData(Request $post)
    {
        $this->ensureTable();
        $get = DB::table('whatsapp_templates')->where('id', $post->id)->first();
        if (!$get) {
            return ['type' => 'error', 'message' => 'Template not found'];
        }
        $get->image_url = !empty($get->image) ? Common::whatsappTemplateImageUrl((string) $get->image) : '';

        return ['type' => 'success', 'message' => 'OK', 'data' => $get];
    }

    public function updateData(Request $post)
    {
        $this->ensureTable();
        $validator = \Validator::make($post->all(), [
            'slug' => 'required',
            'template_id' => 'required',
            'content' => 'required',
            'status' => 'required|numeric|digits:1',
            'image' => 'nullable|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ]);
        if ($validator->fails()) {
            return response()->json(['type' => 'error', 'message' => $validator->errors()->first()]);
        }

        $existing = ((int) $post->edit_id > 0)
            ? DB::table('whatsapp_templates')->where('id', $post->edit_id)->first()
            : null;
        $imageName = (string) ($existing->image ?? '');

        if ($post->boolean('remove_image')) {
            $this->deleteTemplateImage($imageName);
            $imageName = '';
        }

        if ($post->hasFile('image')) {
            $this->deleteTemplateImage($imageName);
            $file = $post->file('image');
            $ext = strtolower((string) ($file->getClientOriginalExtension() ?: 'png'));
            $imageName = 'wa_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
            $this->storeTemplateImage($file, $imageName);
        }

        $payload = [
            'slug' => $post->slug,
            'template_id' => $post->template_id,
            'content' => $post->content,
            'attach_logo' => $post->boolean('attach_logo') ? 1 : 0,
            'attach_image' => $post->boolean('attach_image') ? 1 : 0,
            'image' => $imageName !== '' ? $imageName : null,
            'status' => $post->status,
            'updated_at' => Carbon::now(),
        ];

        if ((int) $post->edit_id === 0) {
            $payload['created_at'] = Carbon::now();
            $ok = DB::table('whatsapp_templates')->insert($payload);
            $message = 'Created successfully';
        } else {
            $ok = DB::table('whatsapp_templates')->where('id', $post->edit_id)->update($payload);
            $message = 'Updated successfully';
        }

        return [
            'type' => $ok ? 'success' : 'error',
            'message' => $ok ? $message : 'Something went wrong!',
        ];
    }

    public function send(Request $post)
    {
        $this->ensureTable();
        $mobile = preg_replace('/\D+/', '', (string) $post->mobile);
        if (strlen($mobile) === 12 && str_starts_with($mobile, '91')) {
            $mobile = substr($mobile, 2);
        }
        if (strlen($mobile) !== 10) {
            return response()->json(['type' => 'error', 'message' => 'Enter a valid 10-digit mobile number']);
        }

        $tpl = DB::table('whatsapp_templates')->where('id', $post->id)->first();
        if (!$tpl) {
            return response()->json(['type' => 'error', 'message' => 'Template not found']);
        }
        if ((int) $tpl->status !== 1) {
            return response()->json(['type' => 'error', 'message' => 'Template is deactive']);
        }

        $attach = $post->has('attach_logo')
            ? $post->boolean('attach_logo')
            : (int) $tpl->attach_logo === 1;
        $attachImage = $post->has('attach_image')
            ? $post->boolean('attach_image')
            : (int) ($tpl->attach_image ?? 0) === 1;

        $result = Common::sendWhatasappMsg([
            'mobile_number' => $mobile,
            'content' => (string) $tpl->content,
            'template_id' => (string) $tpl->template_id,
            'slug' => (string) $tpl->slug,
            'attach_logo' => $attach,
            'attach_image' => $attachImage,
            'image' => (string) ($tpl->image ?? ''),
        ]);

        if ($result === 1) {
            return response()->json(['type' => 'error', 'message' => 'WhatsApp API URL is not configured']);
        }

        return response()->json([
            'type' => 'success',
            'message' => 'WhatsApp sent to '.$mobile,
        ]);
    }

    private function ensureTable(): void
    {
        if (! Schema::hasTable('whatsapp_templates')) {
            Schema::create('whatsapp_templates', function ($table) {
                $table->id();
                $table->string('slug', 120)->index();
                $table->string('template_id', 120)->nullable();
                $table->text('content')->nullable();
                $table->string('image', 190)->nullable();
                $table->unsignedTinyInteger('attach_logo')->default(0);
                $table->unsignedTinyInteger('attach_image')->default(0);
                $table->unsignedTinyInteger('status')->default(1);
                $table->timestamps();
            });

            return;
        }
        if (! Schema::hasColumn('whatsapp_templates', 'image')) {
            Schema::table('whatsapp_templates', function ($table) {
                $table->string('image', 190)->nullable();
            });
        }
        if (! Schema::hasColumn('whatsapp_templates', 'attach_image')) {
            Schema::table('whatsapp_templates', function ($table) {
                $table->unsignedTinyInteger('attach_image')->default(0);
            });
        }
    }

    private function templateImageDirectories(): array
    {
        return [
            public_path('whatsapp_template'),
            dirname(base_path()).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'whatsapp_template',
        ];
    }

    private function storeTemplateImage(\Illuminate\Http\UploadedFile $file, string $name): void
    {
        $bytes = (string) file_get_contents($file->getRealPath());
        foreach ($this->templateImageDirectories() as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($dir.DIRECTORY_SEPARATOR.$name, $bytes);
        }
    }

    private function deleteTemplateImage(string $name): void
    {
        $name = basename($name);
        if ($name === '') {
            return;
        }
        foreach ($this->templateImageDirectories() as $dir) {
            $path = $dir.DIRECTORY_SEPARATOR.$name;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function seedDefaults(): void
    {
        if (DB::table('whatsapp_templates')->count() > 0) {
            return;
        }

        $now = Carbon::now();
        $defaults = [
            ['slug' => 'otp', 'template_id' => 'OTP_VERIFY', 'content' => 'Dear {NAME}, Your OTP is {OTP}. Do not share it with anyone. - NETCELL PAY', 'attach_logo' => 0],
            ['slug' => 'create_user', 'template_id' => 'CREATE_USER', 'content' => "NETCELL PAY\n{LOGO}\n\nDear {NAME}, Welcome! Login Mobile: {MOBILE}, Password: {PASSWORD}, PIN: {PIN}.", 'attach_logo' => 1],
            ['slug' => 'fund_receive', 'template_id' => 'FUND_RECEIVE', 'content' => "NETCELL PAY\n{LOGO}\n\nDear {NAME}, Your wallet has been {TYPE} with Rs.{AMOUNT} by {BY}. Balance: Rs.{CURRENT_BALANCE}.", 'attach_logo' => 1],
            ['slug' => 'fund_reverse', 'template_id' => 'FUND_REVERSE', 'content' => "NETCELL PAY\n{LOGO}\n\nDear {NAME}, Your wallet has been {TYPE} with Rs.{AMOUNT} by {BY}. Balance: Rs.{CURRENT_BALANCE}.", 'attach_logo' => 1],
            ['slug' => 'forgot_password', 'template_id' => 'FORGOT_PASSWORD', 'content' => "NETCELL PAY\n{LOGO}\n\nDear {NAME}, Your new password is {PASSWORD}. Mobile: {MOBILE}, PIN: {PIN}.", 'attach_logo' => 1],
        ];

        if (Schema::hasTable('sms_templates')) {
            $sms = DB::table('sms_templates')->get();
            if ($sms->count() > 0) {
                $defaults = [];
                foreach ($sms as $row) {
                    $defaults[] = [
                        'slug' => $row->slug,
                        'template_id' => $row->template_id,
                        'content' => $row->content,
                        'attach_logo' => in_array($row->slug, ['otp'], true) ? 0 : 1,
                    ];
                }
            }
        }

        foreach ($defaults as $row) {
            DB::table('whatsapp_templates')->insert([
                'slug' => $row['slug'],
                'template_id' => $row['template_id'],
                'content' => $row['content'],
                'attach_logo' => $row['attach_logo'],
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
