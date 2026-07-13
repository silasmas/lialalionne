<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Contrôleur de déconnexion client boutique.
 */
class LogoutController extends Controller
{
  /**
   * Déconnecte le client et invalide la session.
   *
   * @param Request $request Requête HTTP
   * @return RedirectResponse Redirection accueil
   */
  public function __invoke(Request $request): RedirectResponse
  {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home');
  }
}
