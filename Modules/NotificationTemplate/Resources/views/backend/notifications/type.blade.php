<div class="d-flex gap-3 align-items-center">
    @php
        $decodedData = is_string($data->data) ? json_decode($data->data, true) : $data->data;
        $notificationGroup = $decodedData['subject'] ?? '-';
        $subject = $decodedData['subject'] ?? '';
        $type = $decodedData['data']['notification_type'] ?? '';
        $user_type = auth()->user()->user_type ?? 'admin';

        $notificationtemplate = \Modules\NotificationTemplate\Models\NotificationTemplate::where('type', $type)->first();
        // Only query mapping if template exists
        $notificationtemplatemapping = null;
        if ($notificationtemplate) {
            $notificationtemplatemapping = \Modules\NotificationTemplate\Models\NotificationTemplateContentMapping::where('template_id', $notificationtemplate->id)
                ->where('user_type', $user_type)
                ->first();
        }

        $notificationMessage = $decodedData['data']['message'] ?? $notificationtemplatemapping->notification_message ?? 'Message not found';
    @endphp

    <div class="text-start">
        <h6 class="m-0">{{ $notificationGroup }}</h6>
        <span>{!! $notificationMessage  !!}</span>
    </div>
</div>

