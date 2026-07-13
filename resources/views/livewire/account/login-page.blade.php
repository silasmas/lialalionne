<div>
  <x-shopwise-breadcrumb
    title="Connexion"
    :items="[['label' => 'Connexion', 'url' => route('account.login')]]"
  />

  <div class="main_content">
    <div class="login_register_wrap section">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-xl-6 col-md-10">
            <div class="login_wrap">
              <div class="padding_eight_all bg-white">
                <div class="heading_s1">
                  <h3>Connexion</h3>
                </div>

                @if ($authMode->usesOtp())
                  @if ($step === 'credentials')
                    <form wire:submit="sendOtp">
                      @if ($authMode->otpChannel() === 'email')
                        <div class="form-group mb-3">
                          <input
                            type="email"
                            wire:model="email"
                            required
                            class="form-control @error('email') is-invalid @enderror"
                            placeholder="Votre adresse e-mail"
                          >
                          @error('email')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                          @enderror
                        </div>
                      @else
                        <div class="form-group mb-3">
                          <input
                            type="tel"
                            wire:model="phone"
                            required
                            class="form-control @error('phone') is-invalid @enderror"
                            placeholder="Votre numéro de téléphone"
                          >
                          @error('phone')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                          @enderror
                        </div>
                      @endif

                      <div class="login_footer form-group mb-3">
                        <div class="chek-form">
                          <div class="custome-checkbox">
                            <input class="form-check-input" type="checkbox" wire:model="remember" id="loginRemember">
                            <label class="form-check-label" for="loginRemember"><span>Se souvenir de moi</span></label>
                          </div>
                        </div>
                      </div>

                      <div class="form-group mb-3">
                        <button type="submit" class="btn btn-fill-out btn-block" wire:loading.attr="disabled">
                          <span wire:loading.remove wire:target="sendOtp">Recevoir mon code</span>
                          <span wire:loading wire:target="sendOtp">Envoi en cours...</span>
                        </button>
                      </div>
                    </form>
                  @else
                    <p class="text-muted mb-3">
                      Saisissez le code à 6 chiffres envoyé par {{ $authMode->otpChannel() === 'email' ? 'e-mail' : 'SMS' }}.
                    </p>
                    <form wire:submit="verifyOtpAndLogin">
                      <div class="form-group mb-3">
                        <input
                          type="text"
                          wire:model="otpCode"
                          maxlength="6"
                          inputmode="numeric"
                          required
                          class="form-control text-center @error('otpCode') is-invalid @enderror"
                          placeholder="Code OTP"
                          style="letter-spacing: 0.35em;"
                        >
                        @error('otpCode')
                          <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                      </div>
                      <div class="form-group mb-3">
                        <button type="submit" class="btn btn-fill-out btn-block" wire:loading.attr="disabled">
                          <span wire:loading.remove wire:target="verifyOtpAndLogin">Valider et se connecter</span>
                          <span wire:loading wire:target="verifyOtpAndLogin">Vérification...</span>
                        </button>
                      </div>
                      <div class="d-flex justify-content-between">
                        <button type="button" wire:click="$set('step', 'credentials')" class="btn btn-link p-0">
                          Changer d'identifiant
                        </button>
                        <button type="button" wire:click="sendOtp" class="btn btn-link p-0" wire:loading.attr="disabled">
                          Renvoyer le code
                        </button>
                      </div>
                    </form>
                  @endif
                @else
                  <div class="alert alert-warning">
                    La connexion par mot de passe est désactivée. Contactez l'administrateur pour activer l'OTP.
                  </div>
                @endif

                <div class="form-note text-center">
                  Pas encore de compte ?
                  <a href="{{ route('account.register') }}">Créer un compte</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
