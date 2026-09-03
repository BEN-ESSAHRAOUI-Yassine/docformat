<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DocumentExportCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Document $document,
        public array $export = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'export.completed',
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'message' => "Document \"{$this->document->name}\" export completed.",
            'integrity_valid' => $this->export['integrity']['valid'] ?? true,
        ];
    }
}
