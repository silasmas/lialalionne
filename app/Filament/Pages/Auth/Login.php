<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

/**
 * Page de connexion admin : message d'échec visible sous le champ e-mail.
 */
class Login extends BaseLogin
{
  /**
   * Affiche une erreur de formulaire claire quand les identifiants sont faux.
   *
   * @return never
   */
  protected function throwFailureValidationException(): never
  {
    throw ValidationException::withMessages([
      'data.email' => 'E-mail ou mot de passe incorrect. Vérifiez vos identifiants admin.',
    ]);
  }
}
