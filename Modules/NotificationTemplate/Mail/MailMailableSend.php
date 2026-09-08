<?php

namespace Modules\NotificationTemplate\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

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
    public function __construct($mailable, $data, $type = '', $templateData = null)
    {
        $this->mailable = $mailable ?? '';
        $this->data = $data;
        $this->type = $type;
        $this->templateData = $templateData;
    }

    /**
     * Build the message.
     */
     public function build()
    {
        // Use pre-processed template data if provided, otherwise fallback to default mapping
        if (empty($this->templateData)) {
            $this->templateData = $this->mailable->defaultNotificationTemplateMap->template_detail ?? '';

            foreach ($this->data as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                $this->templateData = str_replace(['[[ ' . $key . ' ]]', '[[' . $key . ']]'], (string) $value, $this->templateData);
            }
        }

      

        $message = $this->markdown('notificationtemplate::backend.mail.markdown');


        $files = isset($this->data['attachments']) ? json_decode($this->data['attachments']) : [];

        foreach ($files as $file) {
            $message->attach($file); // attach each file
        }

        return $message; //Send mail
    }
}
