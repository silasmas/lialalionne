<footer class="footer_dark">
  <div class="footer_top">
    <div class="container">
      <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-12">
          <div class="widget">
            <div class="footer_logo">
              <a href="{{ route('home') }}"><img src="{{ asset('assets/logo.jpeg') }}" alt="Lialalionne"></a>
            </div>
            <p>Soins corporels premium pour prendre soin de votre peau, naturellement.</p>
          </div>
          <div class="widget">
            <ul class="social_icons social_white">
              <li><a href="#" aria-label="Facebook"><i class="ion-social-facebook"></i></a></li>
              <li><a href="#" aria-label="Twitter"><i class="ion-social-twitter"></i></a></li>
              <li><a href="#" aria-label="Instagram"><i class="ion-social-instagram-outline"></i></a></li>
              <li><a href="#" aria-label="YouTube"><i class="ion-social-youtube-outline"></i></a></li>
            </ul>
          </div>
        </div>
        <div class="col-lg-2 col-md-3 col-sm-6">
          <div class="widget">
            <h6 class="widget_title">Liens utiles</h6>
            <ul class="widget_links">
              <li><a href="{{ route('shop.about') }}">À propos</a></li>
              <li><a href="{{ route('shop.catalog') }}">Boutique</a></li>
              <li><a href="{{ route('shop.compare') }}">Comparer</a></li>
              <li><a href="{{ route('legal.show', 'cgv') }}">Conditions Générales de Vente</a></li>
              <li><a href="{{ route('legal.show', 'confidentialite') }}">Politique de confidentialité</a></li>
              <li><a href="{{ route('legal.show', 'retours') }}">Retours</a></li>
              <li><a href="#" data-open-cookie-consent>Gérer les cookies</a></li>
            </ul>
          </div>
        </div>
        <div class="col-lg-2 col-md-3 col-sm-6">
          <div class="widget">
            <h6 class="widget_title">Catégories</h6>
            <ul class="widget_links">
              @forelse ($footerCategories as $category)
                <li><a href="{{ route('shop.catalog', ['categorie' => $category->id]) }}">{{ $category->name }}</a></li>
              @empty
                <li><a href="{{ route('shop.catalog') }}">Tous les produits</a></li>
              @endforelse
            </ul>
          </div>
        </div>
        <div class="col-lg-2 col-md-6 col-sm-6">
          <div class="widget">
            <h6 class="widget_title">Mon compte</h6>
            <ul class="widget_links">
              @auth
                <li><a href="{{ route('account.dashboard') }}">Mon compte</a></li>
                <li><a href="{{ route('account.orders') }}">Mes commandes</a></li>
                <li><a href="{{ route('account.favorites') }}">Mes favoris</a></li>
              @else
                <li><a href="{{ route('account.login') }}">Connexion</a></li>
                <li><a href="{{ route('account.register') }}">Inscription</a></li>
              @endauth
              <li><a href="{{ route('shop.cart') }}">Panier</a></li>
              <li><a href="{{ route('shop.checkout') }}">Commander</a></li>
            </ul>
          </div>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6">
          <div class="widget">
            <h6 class="widget_title">Contact</h6>
            <ul class="contact_info contact_info_light">
              <li>
                <i class="ti-location-pin"></i>
                <p>Kinshasa, République Démocratique du Congo</p>
              </li>
              <li>
                <i class="ti-email"></i>
                <a href="mailto:contact@lialalionne.com">contact@lialalionne.com</a>
              </li>
              <li>
                <i class="ti-mobile"></i>
                <p>+243 000 000 000</p>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="bottom_footer border-top-tran">
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <p class="mb-md-0 text-center text-md-start">&copy; {{ date('Y') }} Lialalionne. Tous droits réservés.</p>
        </div>
        <div class="col-md-6">
          <ul class="footer_payment text-center text-lg-end">
            <li><a href="#"><img src="{{ $sw('images/visa.png') }}" alt="Visa"></a></li>
            <li><a href="#"><img src="{{ $sw('images/discover.png') }}" alt="Discover"></a></li>
            <li><a href="#"><img src="{{ $sw('images/master_card.png') }}" alt="Mastercard"></a></li>
            <li><a href="#"><img src="{{ $sw('images/paypal.png') }}" alt="PayPal"></a></li>
            <li><a href="#"><img src="{{ $sw('images/amarican_express.png') }}" alt="American Express"></a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</footer>

<a href="#" class="scrollup" style="display: none;"><i class="ion-ios-arrow-up"></i></a>
