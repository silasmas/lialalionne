<?php

namespace App\Enums;

/**
 * Modes d'authentification client configurables depuis l'admin.
 */
enum AuthMode: string
{
  case EmailPassword = 'email_password';
  case EmailOtp = 'email_otp';
  case SmsOtp = 'sms_otp';

  /**
   * Retourne le libellé affichable du mode.
   *
   * @return string Libellé en français
   */
  public function label(): string
  {
    return match ($this) {
      self::EmailPassword => 'Email + mot de passe',
      self::EmailOtp => 'Email + code OTP',
      self::SmsOtp => 'SMS + code OTP',
    };
  }

  /**
   * Indique si le mode utilise un code OTP.
   *
   * @return bool True si OTP requis
   */
  public function usesOtp(): bool
  {
    return match ($this) {
      self::EmailPassword => false,
      self::EmailOtp, self::SmsOtp => true,
    };
  }

  /**
   * Canal d'envoi OTP associé au mode.
   *
   * @return string Canal email ou sms
   */
  public function otpChannel(): string
  {
    return match ($this) {
      self::EmailOtp => 'email',
      self::SmsOtp => 'sms',
      self::EmailPassword => 'email',
    };
  }
}
