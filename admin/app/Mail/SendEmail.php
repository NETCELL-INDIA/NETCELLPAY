<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendEmail extends Mailable implements ShouldQueue
{
            
    use Queueable, SerializesModels;
    public $subject;
    public $message;
    public $brand;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject,$message)
    {
       $this->subject = $subject;
       $this->message = $message;
       $this->brand = function_exists('email_brand') ? email_brand() : [
           'name' => 'NETCELL PAY',
           'logo' => '',
           'support_email' => '',
           'support_phone' => '',
           'website' => 'https://netcellpay.in',
           'year' => date('Y'),
       ];
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'admin.emails.email',
            with: [
                'subject' => $this->subject,
                'msg' => $this->message,
                'bodyHtml' => function_exists('email_body_html') ? email_body_html($this->message) : e((string) $this->message),
                'brand' => $this->brand,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
