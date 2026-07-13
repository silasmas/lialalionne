<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email contenant un code OTP pour connexion ou inscription client.
 */
class OtpMail extends Mailable
{
  use Queueable, SerializesModels;

  /**
   * @param string $code Code OTP à 6 chiffres
   * @param string $purpose Libellé du contexte (connexion, inscription)
   */
  public function __construct(
    public readonly string $code,
    public readonly string $purpose
  ) {
  }

  /**
   * Enveloppe de l'email OTP.
   *
   * @return Envelope Enveloppe configurée
   */
  public function envelope(): Envelope
  {
    return new Envelope(
      subject: 'Votre code de vérification — Lialalionne',
    );
  }

  /**
   * Contenu Markdown de l'email OTP.
   *
   * @return Content Contenu configuré
   */
  public function content(): Content
  {
    return new Content(
      markdown: 'emails.auth.otp',
    );
  }
}
