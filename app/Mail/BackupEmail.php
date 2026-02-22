<?php

namespace App\Mail;

use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackupEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $backupDate;
    public string $fileSize;

    public function __construct(
        public string $filePath,
        public string $fileName,
    ) {
        $this->backupDate = date('M j, Y g:i A');
        $bytes = filesize($filePath);
        $this->fileSize = $bytes >= 1048576
            ? round($bytes / 1048576, 1) . ' MB'
            : round($bytes / 1024, 1) . ' KB';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Database Backup - ' . date('M j, Y'),
        );
    }

    public function content(): Content
    {
        $themeSettings = SiteSetting::getTheme();

        return new Content(
            view: 'emails.backup',
            with: ['themeSettings' => $themeSettings],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->filePath)
                ->as($this->fileName)
                ->withMime('application/sql'),
        ];
    }
}
