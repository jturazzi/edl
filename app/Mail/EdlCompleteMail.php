<?php

namespace App\Mail;

use App\Models\Edl;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class EdlCompleteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Edl $edl)
    {
    }

    public function envelope(): Envelope
    {
        $type = $this->edl->type_label;

        $appName = config('app.name');

        return new Envelope(
            subject: "{$appName} - {$this->edl->adresse}, {$this->edl->ville}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.edl-complete',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->edl->pdf_path || ! Storage::disk('local')->exists($this->edl->pdf_path)) {
            return [];
        }

        $filename = "EDL-{$this->edl->type}-{$this->edl->adresse}.pdf";

        return [
            Attachment::fromStorageDisk('local', $this->edl->pdf_path)
                ->as($filename)
                ->withMime('application/pdf'),
        ];
    }
}
