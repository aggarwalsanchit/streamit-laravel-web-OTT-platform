<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class sendOtp extends Mailable
{
    use Queueable, SerializesModels;
    public $bodyData;
    /**
     * Create a new message instance.
     */
    public function __construct($bodyData)
    {
        $this->bodyData = $bodyData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.otp_mail_title'),
        );
    }

    public function build()
    {
        return $this->subject(__('messages.otp_mail_title'))->view('mail.send-otp',['data' => $this->bodyData['body']]);
    }
}
