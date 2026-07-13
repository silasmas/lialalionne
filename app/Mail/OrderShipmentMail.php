<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email envoyé au client lors de l'expédition de sa commande.
 */
class OrderShipmentMail extends Mailable
{
  use Queueable, SerializesModels;

  /**
   * @param Order $order Commande expédiée
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
      subject: 'Votre commande ' . $this->order->order_number . ' est expédiée — Lialalionne',
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
      markdown: 'emails.orders.shipment',
    );
  }
}
