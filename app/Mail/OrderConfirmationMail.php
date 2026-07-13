<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\CurrencyService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email de confirmation envoyé au client après paiement d'une commande.
 */
class OrderConfirmationMail extends Mailable
{
  use Queueable, SerializesModels;

  /**
   * @param Order $order Commande confirmée
   */
  public function __construct(
    public readonly Order $order
  ) {
  }

  /**
   * Enveloppe de l'email (expéditeur, sujet).
   *
   * @return Envelope Enveloppe configurée
   */
  public function envelope(): Envelope
  {
    return new Envelope(
      subject: 'Confirmation de commande ' . $this->order->order_number . ' — Lialalionne',
    );
  }

  /**
   * Contenu de l'email (vue Markdown).
   *
   * @return Content Contenu configuré
   */
  public function content(): Content
  {
    return new Content(
      markdown: 'emails.orders.confirmation',
      with: [
        'currencyService' => app(CurrencyService::class),
      ],
    );
  }
}
