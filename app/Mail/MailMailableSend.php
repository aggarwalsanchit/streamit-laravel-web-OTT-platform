<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailMailableSend extends Mailable
{
    use Queueable, SerializesModels;

    public $mailable;

    public $data;

    public $templateData;

    public $type;

    /**
     * Create a new message instance.
     */
    public function __construct($mailable, $data, $type = '')
    {
        $this->mailable = $mailable ?? '';
        $this->data = $data;
        $this->type = $type;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->templateData = $this->mailable->defaultNotificationTemplateMap->template_detail;

        foreach ($this->data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $this->templateData = str_replace('[[ '.$key.' ]]', (string) $value, $this->templateData);
        }

      

        $message = $this->markdown('mail.markdown');

        $files = isset($this->data['attachments']) ? json_decode($this->data['attachments']) : [];

        foreach ($files as $file) {
            $message->attach($file); // attach each file
        }

        return $message; //Send mail
    }
}
