<?php

namespace App\Livewire\Account;

use App\Models\User;
use App\Services\CartService;
use App\Services\OtpService;
use App\Services\SiteSettingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Page de connexion client (mot de passe ou OTP selon paramètres admin).
 */
class LoginPage extends Component
{
  public string $email = '';

  public string $phone = '';

  public string $password = '';

  public string $otpCode = '';

  public bool $remember = false;

  public string $step = 'credentials';

  public bool $otpSent = false;

  /**
   * Envoie un code OTP pour connexion.
   *
   * @param OtpService $otpService Service OTP
   * @param SiteSettingsService $settings Paramètres boutique
   * @return void
   */
  public function sendOtp(OtpService $otpService, SiteSettingsService $settings): void
  {
    $authMode = $settings->authMode();

    if (!$authMode->usesOtp()) {
      return;
    }

    if ($authMode->otpChannel() === 'email') {
      $this->validate(['email' => ['required', 'email']], [], ['email' => 'email']);
      $identifier = $this->email;
    } else {
      $this->validate(['phone' => ['required', 'string', 'min:9', 'max:20']], [], ['phone' => 'téléphone']);
      $identifier = $this->phone;
    }

    if (!User::query()->where($authMode->otpChannel() === 'email' ? 'email' : 'phone', $identifier)->exists()) {
      $this->addError('email', 'Aucun compte trouvé. Créez un compte d\'abord.');

      return;
    }

    $otpService->send($identifier, $authMode->otpChannel(), 'login');
    $this->otpSent = true;
    $this->step = 'otp';
  }

  /**
   * Vérifie l'OTP et connecte le client.
   *
   * @param OtpService $otpService Service OTP
   * @param CartService $cartService Service panier
   * @param SiteSettingsService $settings Paramètres boutique
   * @return mixed Redirection après connexion
   */
  public function verifyOtpAndLogin(
    OtpService $otpService,
    CartService $cartService,
    SiteSettingsService $settings
  ) {
    $authMode = $settings->authMode();
    $identifier = $authMode->otpChannel() === 'email' ? $this->email : $this->phone;

    $this->validate(['otpCode' => ['required', 'string', 'size:6']], [], ['otpCode' => 'code']);

    try {
      $otpService->verify($identifier, 'login', $this->otpCode);
    } catch (ValidationException $exception) {
      $this->addError('otpCode', $exception->errors()['otp'][0] ?? 'Code invalide.');

      return null;
    }

    $user = User::query()
      ->where($authMode->otpChannel() === 'email' ? 'email' : 'phone', $identifier)
      ->first();

    if (!$user || $user->is_admin) {
      $this->addError('otpCode', 'Compte introuvable ou réservé à l\'admin.');

      return null;
    }

    Auth::login($user, $this->remember);
    session()->regenerate();
    $cartService->mergeGuestCartIntoUser($user);

    return $this->redirect(session()->pull('url.intended', route('account.dashboard')), navigate: true);
  }

  /**
   * Authentifie le client par email et mot de passe.
   *
   * @param CartService $cartService Service panier
   * @return mixed Redirection après connexion
   */
  public function loginWithPassword(CartService $cartService)
  {
    $this->validate([
      'email' => ['required', 'email'],
      'password' => ['required', 'string'],
    ], [], [
      'email' => 'email',
      'password' => 'mot de passe',
    ]);

    if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
      $this->addError('email', 'Identifiants incorrects.');

      return null;
    }

    session()->regenerate();
    $user = Auth::user();

    if ($user->is_admin) {
      Auth::logout();
      $this->addError('email', 'Utilisez le panel admin pour ce compte.');

      return null;
    }

    $cartService->mergeGuestCartIntoUser($user);

    return $this->redirect(session()->pull('url.intended', route('account.dashboard')), navigate: true);
  }

  /**
   * Rendu de la page connexion selon le mode auth configuré.
   *
   * @param SiteSettingsService $settings Paramètres boutique
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render(SiteSettingsService $settings)
  {
    return view('livewire.account.login-page', [
      'authMode' => $settings->authMode(),
    ])->layout('layouts.shopwise', [
      'title' => 'Connexion — Lialalionne',
    ]);
  }
}
