<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAudit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Session;

class KycController extends Controller
{
    public function __construct()
    {
        $this->ensure();
    }

    private function ensure(): void
    {
        try {
            if (Schema::hasTable('users')) {
                if (! Schema::hasColumn('users', 'kyc_remark')) {
                    Schema::table('users', function ($table) {
                        $table->string('kyc_remark', 255)->nullable();
                    });
                }
                if (! Schema::hasColumn('users', 'kyc_reviewed_at')) {
                    Schema::table('users', function ($table) {
                        $table->timestamp('kyc_reviewed_at')->nullable();
                    });
                }
                if (! Schema::hasColumn('users', 'kyc_reviewed_by')) {
                    Schema::table('users', function ($table) {
                        $table->unsignedBigInteger('kyc_reviewed_by')->nullable();
                    });
                }
            }
            if (! Schema::hasTable('user_kyc_documents')) {
                Schema::create('user_kyc_documents', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->index();
                    $table->string('doc_type', 40);
                    $table->string('file_name', 191);
                    $table->string('original_name', 191)->nullable();
                    $table->timestamps();
                });
            }
        } catch (\Throwable $e) {
        }
    }

    public function index()
    {
        return view('admin.users.kyc');
    }

    public function list(Request $request)
    {
        $status = $request->kyc_status ?: 'Pending';
        $term = trim((string) $request->q);
        $limit = in_array((int) $request->show, [10, 25, 50], true) ? (int) $request->show : 10;
        $page = max(1, (int) ($request->page ?: 1));
        $offset = ($page - 1) * $limit;

        $q = DB::table('users')->where(function ($w) {
            $w->whereNull('deleted_at')->orWhere('deleted_at', '!=', 1);
        })->where('role_id', '!=', 1);

        if ($status !== 'All') {
            if ($status === 'Pending') {
                $q->where(function ($w) {
                    $w->whereNull('kyc_status')
                        ->orWhereIn('kyc_status', ['', '0', 'Pending', 'pending', 'Not Verified', 'not_verified', 'Under Process']);
                });
            } else {
                $q->where('kyc_status', $status);
            }
        }
        if ($term !== '') {
            $q->where(function ($w) use ($term) {
                $w->where('mobile_number', 'like', "%{$term}%")
                    ->orWhere('outlet_name', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('email_address', 'like', "%{$term}%")
                    ->orWhere('id', $term);
            });
        }

        $total = (clone $q)->count();
        $rows = (clone $q)->orderByDesc('id')->offset($offset)->limit($limit)->get();
        $html = '';
        if ($rows->count()) {
            foreach ($rows as $u) {
                $name = trim(($u->first_name ?? '').' '.($u->last_name ?? ''));
                $kyc = $u->kyc_status ?: 'Pending';
                $badge = 'warning';
                if ($kyc === 'Approved') {
                    $badge = 'success';
                } elseif ($kyc === 'Rejected') {
                    $badge = 'danger';
                }
                $html .= '<tr>
                    <td>'.e($u->id).'</td>
                    <td>'.e($name !== '' ? $name : ($u->outlet_name ?: '-')).'<br><small class="text-muted">'.e($u->outlet_name).'</small></td>
                    <td>'.e($u->mobile_number).'</td>
                    <td>'.e($u->city).' '.e($u->state).'</td>
                    <td><span class="badge bg-'.$badge.'">'.e($kyc).'</span></td>
                    <td>'.e($u->kyc_remark ?: '-').'</td>
                    <td><button type="button" class="btn btn-sm btn-outline-primary btn-kyc-view" data-id="'.$u->id.'">Review</button></td>
                </tr>';
            }
        } else {
            $html = '<tr><td colspan="7" class="text-center text-muted py-4">No KYC records</td></tr>';
        }

        return response()->json([
            'type' => 'success',
            'rows' => $html,
            'pagination' => [
                'page' => $page,
                'show' => $limit,
                'total' => $total,
                'from' => $total ? $offset + 1 : 0,
                'to' => min($offset + $limit, $total),
                'last_page' => max(1, (int) ceil($total / max($limit, 1))),
            ],
        ]);
    }

    public function detail(Request $request)
    {
        $id = (int) $request->id;
        $u = DB::table('users')->where('id', $id)->first();
        if (! $u) {
            return response()->json(['type' => 'error', 'message' => 'User not found']);
        }
        $docs = [];
        if (Schema::hasTable('user_kyc_documents')) {
            foreach (DB::table('user_kyc_documents')->where('user_id', $id)->orderBy('id')->get() as $d) {
                $docs[] = [
                    'id' => $d->id,
                    'doc_type' => $d->doc_type,
                    'url' => asset('kyc_docs/'.$d->file_name),
                    'original_name' => $d->original_name,
                ];
            }
        }
        $pic = $u->profile_pic ? asset('profile_pic/'.$u->profile_pic) : '';

        return response()->json([
            'type' => 'success',
            'data' => [
                'id' => $u->id,
                'name' => trim(($u->first_name ?? '').' '.($u->middle_name ?? '').' '.($u->last_name ?? '')),
                'outlet' => $u->outlet_name,
                'mobile' => $u->mobile_number,
                'email' => $u->email_address,
                'dob' => $u->date_of_birth,
                'address' => trim(($u->flat_door_no ?? '').' '.($u->road_street ?? '').' '.($u->area_locality ?? '')),
                'city' => $u->city,
                'state' => $u->state,
                'district' => $u->district,
                'bank' => $u->bank_account_number,
                'ifsc' => $u->ifsc_code,
                'branch' => $u->branch_name,
                'kyc_status' => $u->kyc_status ?: 'Pending',
                'kyc_remark' => $u->kyc_remark,
                'profile_pic' => $pic,
                'docs' => $docs,
            ],
        ]);
    }

    public function decide(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric',
            'kyc_status' => 'required|in:Approved,Rejected,Pending',
            'kyc_remark' => 'nullable|max:255',
        ]);
        if ($request->kyc_status === 'Rejected' && trim((string) $request->kyc_remark) === '') {
            return response()->json(['type' => 'error', 'message' => 'Reject reason is required.']);
        }
        $user = DB::table('users')->where('id', (int) $request->id)->first();
        if (! $user) {
            return response()->json(['type' => 'error', 'message' => 'User not found']);
        }
        $old = $user->kyc_status;
        $payload = [
            'kyc_status' => $request->kyc_status,
            'updated_at' => Carbon::now(),
        ];
        if (Schema::hasColumn('users', 'kyc_remark')) {
            $payload['kyc_remark'] = $request->kyc_remark;
        }
        if (Schema::hasColumn('users', 'kyc_reviewed_at')) {
            $payload['kyc_reviewed_at'] = Carbon::now();
        }
        if (Schema::hasColumn('users', 'kyc_reviewed_by')) {
            $payload['kyc_reviewed_by'] = Session::get('user_id');
        }
        DB::table('users')->where('id', $user->id)->update($payload);
        AdminAudit::log('kyc', 'kyc_'.$request->kyc_status, [
            'ref_type' => 'user',
            'ref_id' => $user->id,
            'old' => $old,
            'new' => $request->kyc_status,
            'remark' => $request->kyc_remark,
        ]);

        return response()->json(['type' => 'success', 'message' => 'KYC '.$request->kyc_status]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric',
            'doc_type' => 'required|in:pan,aadhaar_front,aadhaar_back,shop,other',
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
        ]);
        $user = DB::table('users')->where('id', (int) $request->id)->first();
        if (! $user) {
            return response()->json(['type' => 'error', 'message' => 'User not found']);
        }
        $dir = public_path('kyc_docs');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file = $request->file('file');
        $original = $file->getClientOriginalName();
        $name = 'u'.$user->id.'_'.$request->doc_type.'_'.time().'.'.$file->extension();
        $file->move($dir, $name);
        DB::table('user_kyc_documents')->insert([
            'user_id' => $user->id,
            'doc_type' => $request->doc_type,
            'file_name' => $name,
            'original_name' => $original,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return response()->json(['type' => 'success', 'message' => 'Document uploaded']);
    }
}
