<div>
  <x-shopwise-breadcrumb
    title="Inscription"
    :items="[['label' => 'Inscription', 'url' => route('account.register')]]"
  />

  <div class="main_content">
    <div class="login_register_wrap section">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-xl-6 col-md-10">
            <div class="login_wrap">
              <div class="padding_eight_all bg-white">
                <div class="heading_s1">
                  <h3>Créer un compte</h3>
                </div>

                @if ($authMode->usesOtp())
                  @if ($step === 'credentials')
                    <form wire:submit="sendOtp">
                      <div class="form-group mb-3">
                        <input
                          type="text"
                          wire:model="name"
                          required
                          class="form-control @error('name') is-invalid @enderror"
                          placeholder="Votre nom complet"
                        >
                        @error('name')
                          <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                      </div>

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
                        <div class="form-group mb-3">
                          <input
                            type="tel"
                            wire:model="phone"
                            class="form-control"
                            placeholder="Téléphone (optionnel)"
                          >
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
                        <div class="form-group mb-3">
                          <input
                            type="email"
                            wire:model="email"
                            class="form-control @error('email') is-invalid @enderror"
                            placeholder="E-mail (optionnel)"
                          >
                          @error('email')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                          @enderror
                        </div>
                      @endif

                      <div class="login_footer form-group mb-3">
                        <div class="chek-form">
                          <div class="custome-checkbox">
                            <input class="form-check-input" type="checkbox" wire:model="acceptTerms" id="registerTerms" value="1">
                            <label class="form-check-label" for="registerTerms">
                              <span>J'accepte les <a href="{{ route('legal.show', 'cgv') }}">Conditions Générales de Vente</a>.</span>
                            </label>
                          </div>
                        </div>
                        @error('acceptTerms')
                          <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                      </div>

                      <div class="form-group mb-3">
                        <button type="submit" class="btn btn-fill-out btn-block" wire:loading.attr="disabled">
                          <span wire:loading.remove wire:target="sendOtp">Recevoir mon code</span>
                          <span wire:loading wire:target="sendOtp">Envoi en cours...</span>
                        </button>
                      </div>
                    </form>
                  @else
                    <p class="text-muted mb-3">Code envoyé — saisissez-le pour activer votre compte.</p>
                    <form wire:submit="verifyOtpAndRegister">
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
                          <span wire:loading.remove wire:target="verifyOtpAndRegister">Valider mon inscription</span>
                          <span wire:loading wire:target="verifyOtpAndRegister">Création du compte...</span>
                        </button>
                      </div>
                      <div class="d-flex justify-content-between">
                        <button type="button" wire:click="$set('step', 'credentials')" class="btn btn-link p-0">
                          Modifier mes informations
                        </button>
                        <button type="button" wire:click="sendOtp" class="btn btn-link p-0" wire:loading.attr="disabled">
                          Renvoyer le code
                        </button>
                      </div>
                    </form>
                  @endif
                @else
                  <div class="alert alert-warning">
                    L'inscription par mot de passe est désactivée. Contactez l'administrateur pour activer l'OTP.
                  </div>
                @endif

                <div class="form-note text-center">
                  Déjà inscrit ?
                  <a href="{{ route('account.login') }}">Se connecter</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
