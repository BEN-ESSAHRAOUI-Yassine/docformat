<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DocumentExportFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $reason = '',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'export.failed',
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'message' => "Document \"{$this->document->name}\" export failed.",
            'reason' => $this->reason,
        ];
    }
}
