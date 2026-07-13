<?php

namespace App\Livewire\Account;

use App\Models\User;
use App\Services\CartService;
use App\Services\OtpService;
use App\Services\SiteSettingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Page d'inscription client (mot de passe ou OTP selon paramètres admin).
 */
class RegisterPage extends Component
{
  public string $name = '';

  public string $email = '';

  public string $phone = '';

  public string $password = '';

  public string $passwordConfirmation = '';

  public string $otpCode = '';

  public string $step = 'credentials';

  public bool $otpSent = false;

  public bool $acceptTerms = false;

  /**
   * Envoie un OTP pour finaliser l'inscription.
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

    $rules = [
      'name' => ['required', 'string', 'max:255'],
      'acceptTerms' => ['accepted'],
    ];

    if ($authMode->otpChannel() === 'email') {
      $rules['email'] = ['required', 'email', 'max:255', 'unique:users,email'];
    } else {
      $rules['phone'] = ['required', 'string', 'min:9', 'max:20', 'unique:users,phone'];
      $rules['email'] = ['nullable', 'email', 'max:255', 'unique:users,email'];
    }

    $this->validate($rules, [
      'acceptTerms.accepted' => 'Vous devez accepter les Conditions Générales de Vente.',
    ], [
      'name' => 'nom',
      'email' => 'email',
      'phone' => 'téléphone',
      'acceptTerms' => 'conditions générales',
    ]);

    $identifier = $authMode->otpChannel() === 'email' ? $this->email : $this->phone;

    $otpService->send($identifier, $authMode->otpChannel(), 'register', [
      'name' => $this->name,
      'email' => $this->email,
      'phone' => $this->phone,
    ]);

    $this->otpSent = true;
    $this->step = 'otp';
  }

  /**
   * Vérifie l'OTP et crée le compte client.
   *
   * @param OtpService $otpService Service OTP
   * @param CartService $cartService Service panier
   * @param SiteSettingsService $settings Paramètres boutique
   * @return mixed Redirection dashboard
   */
  public function verifyOtpAndRegister(
    OtpService $otpService,
    CartService $cartService,
    SiteSettingsService $settings
  ) {
    $authMode = $settings->authMode();
    $identifier = $authMode->otpChannel() === 'email' ? $this->email : $this->phone;

    $this->validate(['otpCode' => ['required', 'string', 'size:6']], [], ['otpCode' => 'code']);

    try {
      $otpService->verify($identifier, 'register', $this->otpCode);
    } catch (ValidationException $exception) {
      $this->addError('otpCode', $exception->errors()['otp'][0] ?? 'Code invalide.');

      return null;
    }

    $user = User::query()->create([
      'name' => $this->name,
      'email' => $this->email ?: ($identifier . '@otp.lialalionne.local'),
      'phone' => $authMode->otpChannel() === 'sms' ? $this->phone : ($this->phone ?: null),
      'password' => Str::password(32),
      'is_admin' => false,
      'email_verified_at' => now(),
    ]);

    Auth::login($user);
    session()->regenerate();
    $cartService->mergeGuestCartIntoUser($user);

    return $this->redirect(route('account.dashboard'), navigate: true);
  }

  /**
   * Crée un compte avec email et mot de passe.
   *
   * @param CartService $cartService Service panier
   * @return mixed Redirection dashboard
   */
  public function registerWithPassword(CartService $cartService)
  {
    $this->validate([
      'name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'email', 'max:255', 'unique:users,email'],
      'phone' => ['nullable', 'string', 'max:20'],
      'password' => ['required', 'string', 'min:8', 'same:passwordConfirmation'],
    ], [], [
      'name' => 'nom',
      'email' => 'email',
      'phone' => 'téléphone',
      'password' => 'mot de passe',
      'passwordConfirmation' => 'confirmation',
    ]);

    $user = User::query()->create([
      'name' => $this->name,
      'email' => $this->email,
      'phone' => $this->phone ?: null,
      'password' => $this->password,
      'is_admin' => false,
      'email_verified_at' => now(),
    ]);

    Auth::login($user);
    session()->regenerate();
    $cartService->mergeGuestCartIntoUser($user);

    return $this->redirect(route('account.dashboard'), navigate: true);
  }

  /**
   * Rendu de la page inscription selon le mode auth configuré.
   *
   * @param SiteSettingsService $settings Paramètres boutique
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render(SiteSettingsService $settings)
  {
    return view('livewire.account.register-page', [
      'authMode' => $settings->authMode(),
    ])->layout('layouts.shopwise', [
      'title' => 'Inscription — Lialalionne',
    ]);
  }
}
