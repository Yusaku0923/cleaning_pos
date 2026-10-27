<?php

namespace App\Mail;

use App\Models\DeliveryCustomer;
use App\Models\DeliveryNote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** 納品書メール (旧 DeliveryNoteMail)。本文は編集した文字列、納品書 PDF を添付する。 */
class DeliveryNoteMail extends Mailable
{
    public function __construct(
        public DeliveryCustomer $customer,
        public DeliveryNote $note,
        public array $entries,
        public array $taxGroups,
        public string $body,
    ) {}

    public function envelope(): Envelope
    {
        $start = $this->note->period_start->format('n月j日');
        $end = $this->note->period_end->format('n月j日');

        return new Envelope(subject: "納品書 No.{$this->note->note_number}（{$start}〜{$end}分）");
    }

    public function content(): Content
    {
        return new Content(view: 'delivery.mail');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        $pdf = Pdf::loadView('delivery.pdf', [
            'customer' => $this->customer,
            'note' => $this->note,
            'entries' => $this->entries,
            'taxGroups' => $this->taxGroups,
            'periodStart' => $this->note->period_start->toDateString(),
            'periodEnd' => $this->note->period_end->toDateString(),
            'cutoffDate' => $this->note->period_end->toDateString(),
        ])->setPaper('A4', 'portrait');

        return [
            Attachment::fromData(fn () => $pdf->output(), "納品書_{$this->customer->name}_No{$this->note->note_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
