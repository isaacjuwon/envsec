<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $type;
    protected string $url;
    protected int $imported;
    protected array $errors;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $type, string $url, int $imported, array $errors = [])
    {
        $this->type = $type;
        $this->url = $url;
        $this->imported = $imported;
        $this->errors = $errors;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Import {$this->type} Completed")
            ->line("Import for {$this->type} has been completed.")
            ->line("Successfully imported: {$this->imported} records.");

        if (count($this->errors) > 0) {
            $message->line("Warning: " . count($this->errors) . " errors occurred during import.");
        }

        return $message->action('View Results', $this->url)
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'url' => $this->url,
            'imported' => $this->imported,
            'errors_count' => count($this->errors),
            'errors' => array_slice($this->errors, 0, 10), // Limit error logs in DB
        ];
    }
}
