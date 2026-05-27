<?php

namespace App\Mail;

use App\Models\League;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubRequestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public League $league,
        public string $playerName,
        public int $weekNumber,
        public ?string $weekDateLabel,
        public string $requestMessage,
    ) {}

    public function envelope(): Envelope
    {
        $weekText = $this->weekDateLabel
            ? "Week {$this->weekNumber} - {$this->weekDateLabel}"
            : "Week {$this->weekNumber}";

        return new Envelope(
            subject: "{$this->league->name} - Sub Request: {$this->playerName} ({$weekText})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sub-request',
        );
    }
}
