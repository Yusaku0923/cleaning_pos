<?php

namespace App\Mail;

use App\Models\DeliveryCustomer;
use App\Models\DeliveryNote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class DeliveryNoteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DeliveryCustomer $customer,
        public DeliveryNote $note,
        public array $entries,
        public array $taxGroups,
        public string $body
    ) {}

    public function build()
    {
        $periodStart = $this->note->period_start->format('n月j日');
        $periodEnd = $this->note->period_end->format('n月j日');

        $pdf = Pdf::loadView('delivery.pdf', [
            'customer' => $this->customer,
            'note' => $this->note,
            'entries' => $this->entries,
            'taxGroups' => $this->taxGroups,
            'periodStart' => $this->note->period_start->toDateString(),
            'periodEnd' => $this->note->period_end->toDateString(),
        ])->setPaper('A4', 'portrait');

        return $this->subject("納品書 No.{$this->note->note_number}（{$periodStart}〜{$periodEnd}分）")
            ->view('delivery.mail')
            ->attachData($pdf->output(), "納品書_{$this->customer->name}_No{$this->note->note_number}.pdf", [
                'mime' => 'application/pdf',
            ]);
    }
}
