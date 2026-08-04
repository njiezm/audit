<?php

namespace App\Mail;

use App\Models\Audit;
use App\Services\PdfRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuditReportMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Audit $audit,
        public string $subjectLine,
        public ?string $body = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.audit-report');
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        $pdf = app(PdfRenderer::class)->make($this->audit)->output();

        return [
            Attachment::fromData(fn () => $pdf, $this->audit->pdfFilename())
                ->withMime('application/pdf'),
        ];
    }
}
