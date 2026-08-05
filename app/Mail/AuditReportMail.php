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
        /** Joint le cahier des charges en second fichier, s'il en existe un. */
        public bool $attachSpecification = false,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.audit-report', with: [
            'specification' => $this->specification(),
            'specificationAttached' => $this->attachSpecification && $this->specification() !== null,
        ]);
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        $renderer = app(PdfRenderer::class);
        $report = $renderer->make($this->audit)->output();

        $files = [
            Attachment::fromData(fn () => $report, $this->audit->pdfFilename())
                ->withMime('application/pdf'),
        ];

        $specification = $this->specification();

        if ($this->attachSpecification && $specification) {
            $cdc = $renderer->makeSpecification($specification)->output();

            $files[] = Attachment::fromData(fn () => $cdc, $specification->pdfFilename())
                ->withMime('application/pdf');
        }

        return $files;
    }

    private function specification()
    {
        return $this->audit->specification;
    }
}
