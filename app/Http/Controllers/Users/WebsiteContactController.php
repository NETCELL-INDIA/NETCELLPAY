<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WebsiteContactController extends Controller
{
    public const SUPPORT_EMAIL = 'support@netcellpay.in';

    public const LEGAL_COMPANY = 'NETCELL RETAIL INDIA PRIVATE LIMITED';

    public const PRODUCT_NAME = 'NETCELL Multi Recharge';

    public static function supportCategories(): array
    {
        return [
            'mobile_recharge' => 'Mobile Recharge',
            'dth_recharge' => 'DTH Recharge',
            'retailer_distributor' => 'Retailer and Distributor Account',
            'login_registration' => 'Login and Registration',
            'payment_transaction' => 'Payment and Transaction Issues',
            'technical_support' => 'Technical Support',
            'multi_recharge_app' => 'NETCELL Multi Recharge App',
        ];
    }

    public function show(Request $request)
    {
        $company = $this->resolveCompany();

        return view('users.website.contact', [
            'company' => $company,
            'pageTitle' => 'Contact & Support | NETCELL PAY',
            'pageDescription' => 'Contact NETCELL PAY support for NETCELL Multi Recharge, mobile and DTH recharge, retailer accounts, payments, and app help. Email support@netcellpay.in.',
            'categories' => self::supportCategories(),
            'supportEmail' => self::SUPPORT_EMAIL,
            'legalCompany' => self::LEGAL_COMPANY,
            'productName' => self::PRODUCT_NAME,
            'mailConfigured' => $this->isMailConfigured(),
        ]);
    }

    public function submit(Request $request)
    {
        $company = $this->resolveCompany();

        // Honeypot — bots fill this; humans never see it.
        if (trim((string) $request->input('website_url', '')) !== '') {
            return redirect()
                ->route('website.contact')
                ->with('contact_success', 'Thank you. Your message has been received.');
        }

        $categories = self::supportCategories();
        $validator = Validator::make($request->all(), [
            'full_name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:filter', 'max:190'],
            'subject' => ['required', 'string', 'min:3', 'max:180'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys($categories))],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ], [
            'full_name.required' => 'Please enter your full name.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'subject.required' => 'Please enter a subject.',
            'category.required' => 'Please select a support category.',
            'category.in' => 'Please select a valid support category.',
            'message.required' => 'Please enter your message.',
            'message.min' => 'Please provide a bit more detail (at least 10 characters).',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('website.contact')
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $fullName = strip_tags(trim($data['full_name']));
        $email = strtolower(trim($data['email']));
        $subject = strip_tags(trim($data['subject']));
        $categoryKey = $data['category'];
        $categoryLabel = $categories[$categoryKey] ?? $categoryKey;
        $message = strip_tags(trim($data['message']));

        $this->ensureContactTable();

        $mailConfigured = $this->isMailConfigured();
        $mailSent = false;
        $mailError = null;

        if ($mailConfigured) {
            try {
                $body = "New contact form submission from netcellpay.in\n\n"
                    ."Company: ".self::LEGAL_COMPANY."\n"
                    .'Product: '.self::PRODUCT_NAME."\n"
                    .'Name: '.$fullName."\n"
                    .'Email: '.$email."\n"
                    .'Category: '.$categoryLabel."\n"
                    .'Subject: '.$subject."\n\n"
                    ."Message:\n".$message."\n\n"
                    .'IP: '.(string) $request->ip()."\n"
                    .'Submitted: '.Carbon::now()->toDateTimeString();

                // Sync send (not queued) so delivery status is known without a queue worker.
                Mail::raw($body, function ($mailMessage) use ($subject, $email, $fullName) {
                    $mailMessage->to(self::SUPPORT_EMAIL)
                        ->replyTo($email, $fullName)
                        ->subject('[NETCELL PAY Contact] '.$subject);
                });
                $mailSent = true;
            } catch (\Throwable $e) {
                $mailError = Str::limit($e->getMessage(), 500);
            }
        }

        try {
            DB::table('website_contact_messages')->insert([
                'full_name' => $fullName,
                'email' => $email,
                'subject' => $subject,
                'category' => $categoryKey,
                'message' => $message,
                'ip_address' => (string) $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500),
                'mail_sent' => $mailSent ? 1 : 0,
                'mail_error' => $mailError,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            return redirect()
                ->route('website.contact')
                ->with('contact_error', 'We could not save your message right now. Please email '.self::SUPPORT_EMAIL.' directly.')
                ->withInput();
        }

        if ($mailSent) {
            $flash = 'Thank you. Your message has been sent to our support team. We will reply to '.$email.' soon.';
        } elseif ($mailConfigured) {
            $flash = 'Thank you. Your message was received and saved. If you do not hear back, please email '.self::SUPPORT_EMAIL.' directly.';
        } else {
            $flash = 'Thank you. Your message has been received and saved for our support team. For faster help, email '.self::SUPPORT_EMAIL.'.';
        }

        return redirect()
            ->route('website.contact')
            ->with('contact_success', $flash);
    }

    private function resolveCompany()
    {
        $company = DB::table('companies')
            ->where('status', '1')
            ->where('domain', request()->getHost())
            ->first();

        $company = $company ?: DB::table('companies')
            ->where('status', '1')
            ->first();

        abort_unless($company, 503, 'Website configuration is not available.');

        return $company;
    }

    private function isMailConfigured(): bool
    {
        $mailer = (string) config('mail.default', '');
        if ($mailer === '' || $mailer === 'array' || $mailer === 'log') {
            // log/array are intentionally non-delivery for production support inbox
            if ($mailer === 'log' || $mailer === 'array') {
                return false;
            }
        }

        $host = trim((string) config('mail.mailers.smtp.host', ''));
        $username = trim((string) config('mail.mailers.smtp.username', ''));

        return $host !== '' && $username !== '';
    }

    private function ensureContactTable(): void
    {
        if (Schema::hasTable('website_contact_messages')) {
            return;
        }

        Schema::create('website_contact_messages', function ($table) {
            $table->id();
            $table->string('full_name', 120);
            $table->string('email', 190);
            $table->string('subject', 180);
            $table->string('category', 60);
            $table->text('message');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->unsignedTinyInteger('mail_sent')->default(0);
            $table->string('mail_error', 500)->nullable();
            $table->timestamps();
            $table->index('created_at');
            $table->index('email');
        });
    }
}
