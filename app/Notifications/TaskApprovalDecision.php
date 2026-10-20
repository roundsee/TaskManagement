<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskApprovalDecision extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task,
        public string $decision,
        public ?string $note = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $message = $this->decision === 'approved'
            ? "Tiket \"{$this->task->title}\" di-approve BA dan siap di-deploy."
            : "Tiket \"{$this->task->title}\" di-reject BA dan perlu revisi.";

        if ($this->note) {
            $message .= " Catatan: {$this->note}";
        }

        return [
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'decision' => $this->decision,
            'message' => $message,
            'url' => route('it.tasks.show', $this->task),
        ];
    }
}
