<?php

namespace App\Mail;

use App\Models\League;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyResultsEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public League $league,
        public int $weekNumber,
        public $standings,
        public $weeklyResults,
        public $par3Winners,
        public $playerStandings,
        public int $nextWeekNumber,
        public $nextWeekMatches,
        public $nextWeekTeamNames,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->league->name} - Week {$this->weekNumber} Results",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-results',
        );
    }
}
