<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AccountDeletionController extends Controller
{
    public const SUPPORT_EMAIL = 'support@netcellpay.in';

    public const LEGAL_COMPANY = 'NETCELL RETAIL INDIA PRIVATE LIMITED';

    public function show()
    {
        return view('users.website.delete-account', $this->pageData());
    }

    public function submit(Request $request)
    {
        if (trim((string) $request->input('website_url', '')) !== '') {
            return redirect()->route('website.delete-account')->with('deletion_notice', $this->genericSentMessage());
        }

        $validated = $request->validate([
            'country_code' => ['required', 'regex:/^\+?[0-9]{1,4}$/'],
            'mobile_number' => ['required', 'regex:/^[0-9]{10}$/'],
            'email' => ['nullable', 'email:filter', 'max:190'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'confirm_delete' => ['accepted'],
        ], [
            'mobile_number.regex' => 'Enter the 10-digit mobile number registered on NETCELL PAY.',
            'confirm_delete.accepted' => 'Please confirm that you want to request account deletion.',
        ]);

        $mobile = $validated['mobile_number'];
        $email = isset($validated['email']) ? strtolower(trim($validated['email'])) : null;
        $reason = isset($validated['reason']) ? strip_tags(trim($validated['reason'])) : null;

        $this->ensureTable();

        $user = DB::table('users')
            ->where('mobile_number', $mobile)
            ->where('deleted_at', 0)
            ->whereNotIn('role_id', [1, 2])
            ->first();

        if ($user) {
            $open = DB::table('account_deletion_requests')
                ->where('user_id', $user->id)
                ->whereIn('status', ['Pending Verification', 'Pending Review', 'Approved', 'Processing'])
                ->orderByDesc('id')
                ->first();

            if ($open && $open->status !== 'Pending Verification') {
                $request->session()->put('account_deletion_ref', $open->public_id);
                return redirect()->route('website.delete-account')->with('deletion_notice', $this->alreadyPendingMessage($open->public_id));
            }

            $publicId = $open->public_id ?? $this->makePublicId();
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $payload = [
                'user_id' => $user->id,
                'public_id' => $publicId,
                'mobile_number' => $mobile,
                'country_code' => ltrim($validated['country_code'], '+'),
                'email' => $email,
                'reason' => $reason,
                'status' => 'Pending Verification',
                'otp_hash' => Hash::make($otp),
                'otp_expires_at' => Carbon::now()->addMinutes(10),
                'ip_address' => (string) $request->ip(),
                'updated_at' => Carbon::now(),
            ];

            if ($open) {
                DB::table('account_deletion_requests')->where('id', $open->id)->update($payload);
            } else {
                $payload['created_at'] = Carbon::now();
                DB::table('account_deletion_requests')->insert($payload);
            }

            $this->deliverOtp($user, $otp);
            $request->session()->put('account_deletion_ref', $publicId);
        } else {
            $request->session()->forget('account_deletion_ref');
        }

        return redirect()->route('website.delete-account')->with('deletion_notice', $this->genericSentMessage());
    }

    public function verify(Request $request)
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $publicId = (string) $request->session()->get('account_deletion_ref', '');
        $fail = 'The verification code is incorrect or has expired. Request a new code and try again.';

        if ($publicId === '') {
            return redirect()->route('website.delete-account')->with('deletion_error', $fail);
        }

        $this->ensureTable();
        $row = DB::table('account_deletion_requests')->where('public_id', $publicId)->first();
        if (!$row || $row->status !== 'Pending Verification' || empty($row->otp_hash) || empty($row->otp_expires_at)) {
            return redirect()->route('website.delete-account')->with('deletion_error', $fail);
        }

        if (Carbon::parse($row->otp_expires_at)->lt(Carbon::now()) || !Hash::check($validated['otp'], $row->otp_hash)) {
            return redirect()->route('website.delete-account')->with('deletion_error', $fail);
        }

        DB::table('account_deletion_requests')->where('id', $row->id)->update([
            'status' => 'Pending Review',
            'otp_hash' => null,
            'verified_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->notifySupport($row);
        $request->session()->forget('account_deletion_ref');

        return redirect()->route('website.delete-account')->with(
            'deletion_success',
            'Your request '.$row->public_id.' is verified and pending review. An administrator will process it. Account deletion is not complete until you are notified that the status is Completed. For questions email '.self::SUPPORT_EMAIL.'.'
        );
    }

    private function deliverOtp(object $user, string $otp): void
    {
        try {
            $slug = 'otp';
            if (Schema::hasTable('sms_templates')) {
                $sms = DB::table('sms_templates')->where('slug', $slug)->first(['template_id', 'content', 'status']);
                if ($sms && \helpers::whatsappEnabled($slug, $sms)) {
                    $content = str_replace(
                        ['{NAME}', '{MIDDLE_NAME}', '{LAST_NAME}', '{OUTLET_NAME}', '{OTP}'],
                        [$user->first_name, $user->middle_name, $user->last_name, $user->outlet_name, $otp],
                        (string) $sms->content
                    );
                    \helpers::sendWhatasappMsg([
                        'mobile_number' => $user->mobile_number,
                        'content' => $content,
                        'template_id' => $sms->template_id,
                        'slug' => $slug,
                    ]);
                }
            }

            $email = strtolower(trim((string) ($user->email_address ?? '')));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Mail::raw(
                    "Your NETCELL PAY account deletion code is {$otp}. It expires in 10 minutes. If you did not request this, ignore this message.",
                    function ($message) use ($email) {
                        $message->to($email)->subject('NETCELL PAY account deletion code');
                    }
                );
            }
        } catch (\Throwable $e) {
            // Delivery failure must not reveal whether the account exists.
        }
    }

    private function notifySupport(object $row): void
    {
        try {
            $body = "Verified NETCELL PAY account deletion request\n\n"
                .'Reference: '.$row->public_id."\n"
                .'User ID: '.$row->user_id."\n"
                .'Mobile: '.$row->mobile_number."\n"
                .'Status: Pending Review'."\n"
                .'Submitted: '.$row->created_at."\n\n"
                ."Review it in Admin → Users → Account Deletion Requests.\n"
                .'Do not treat the account as deleted until the request is marked Completed.';

            Mail::raw($body, function ($message) use ($row) {
                $message->to(self::SUPPORT_EMAIL)
                    ->subject('[NETCELL PAY] Account deletion '.$row->public_id);
            });
        } catch (\Throwable $e) {
            // The verified request remains in the database for admin review.
        }
    }

    private function makePublicId(): string
    {
        return 'DEL-'.Carbon::now()->format('Ymd').'-'.strtoupper(Str::random(6));
    }

    private function genericSentMessage(): string
    {
        return 'If a NETCELL PAY account is registered with this mobile number, a 6-digit verification code has been sent to the registered WhatsApp number and email. Enter that code below. The code expires in 10 minutes.';
    }

    private function alreadyPendingMessage(string $publicId): string
    {
        return 'A verified deletion request is already pending review. Reference: '.$publicId.'. Email '.self::SUPPORT_EMAIL.' if you need an update. The account is not deleted until the request is completed.';
    }

    private function pageData(): array
    {
        $company = DB::table('companies')->where('status', '1')->where('domain', request()->getHost())->first()
            ?: DB::table('companies')->where('status', '1')->first();

        abort_unless($company, 503, 'Website configuration is not available.');

        return [
            'company' => $company,
            'pageTitle' => 'Request Account Deletion | NETCELL PAY',
            'pageDescription' => 'Request deletion of your NETCELL PAY account and associated personal data.',
            'legalCompany' => self::LEGAL_COMPANY,
            'supportEmail' => $company->support_email ?: self::SUPPORT_EMAIL,
            'awaitingOtp' => session()->has('account_deletion_ref') || session()->has('deletion_notice'),
        ];
    }

    private function ensureTable(): void
    {
        if (Schema::hasTable('account_deletion_requests')) {
            return;
        }

        Schema::create('account_deletion_requests', function ($table) {
            $table->id();
            $table->string('public_id', 32)->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('country_code', 8)->nullable();
            $table->string('mobile_number', 20)->index();
            $table->string('email', 190)->nullable();
            $table->string('reason', 1000)->nullable();
            $table->string('status', 40)->index();
            $table->string('otp_hash')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('admin_note', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }
}
