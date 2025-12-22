<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InformasiTransaksiOmsMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public $transaksi,
        public $details
    ) {}
    
    public function build()
    {
        return $this
            ->from('quarkneuralpartikel@gmail.com', 'OMS System')
            ->subject('Mohon Proses Transaksi ' . ($this->transaksi->invoice ?? ''))
            ->view('oms.emails.informasi_transaksi')
            ->with([
                'transaksi' => $this->transaksi,
                'details'   => $this->details,
            ]);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
