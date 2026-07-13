@php
  $teamMembers = [
    ['name' => 'Mme Eliane', 'role' => 'Fondatrice', 'image' => $sw('images/team_img1.jpg')],
    ['name' => 'Marie Kabila', 'role' => 'Conseillère beauté', 'image' => $sw('images/team_img2.jpg')],
    ['name' => 'Grace Mbuyi', 'role' => 'Responsable boutique', 'image' => $sw('images/team_img3.jpg')],
    ['name' => 'Amina N\'senga', 'role' => 'Esthéticienne', 'image' => $sw('images/team_img4.jpg')],
  ];

  $testimonials = [
    [
      'quote' => 'Les produits Lialalionne ont transformé ma routine de soins. Ma peau est plus douce et éclatante.',
      'name' => 'Marie Kabila',
      'role' => 'Cliente fidèle',
      'image' => $sw('images/user_img1.jpg'),
    ],
    [
      'quote' => 'Livraison rapide à Kinshasa et paiement Mobile Money très pratique. Je recommande vivement.',
      'name' => 'Grace Mbuyi',
      'role' => 'Kinshasa',
      'image' => $sw('images/user_img2.jpg'),
    ],
    [
      'quote' => 'Qualité premium à prix accessibles. Le gel nettoyant est devenu mon indispensable quotidien.',
      'name' => 'Amina N\'senga',
      'role' => 'Esthéticienne',
      'image' => $sw('images/user_img3.jpg'),
    ],
  ];
@endphp

<div>
  <x-shopwise-breadcrumb
    title="À propos"
    :items="[['label' => 'Pages', 'url' => route('shop.about')], ['label' => 'À propos', 'url' => route('shop.about')]]"
  />

  <div class="main_content">
    <div class="section">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <div class="about_img scene mb-4 mb-lg-0">
              <img src="{{ $sw('images/about_img.jpg') }}" alt="Lialalionne">
            </div>
          </div>
          <div class="col-lg-6">
            <div class="heading_s1">
              <h2>Qui sommes-nous ?</h2>
            </div>
            <p>
              Lialalionne est une marque congolaise dédiée aux soins corporels premium. Nous sélectionnons
              des formules efficaces pour nourrir, protéger et sublimer votre peau au quotidien.
            </p>
            <p>
              Basée à Kinshasa, notre boutique en ligne vous propose gels, laits, huiles et soins ciblés
              avec livraison locale et paiement Mobile Money ou carte bancaire.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="section bg_light_blue2 pb_70">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-6 col-md-8">
            <div class="heading_s1 text-center">
              <h2>Pourquoi nous choisir ?</h2>
            </div>
            <p class="text-center leads">
              Des soins de qualité, un service attentionné et une expérience d'achat simple en RDC.
            </p>
          </div>
        </div>
        <div class="row justify-content-center">
          <div class="col-lg-4 col-sm-6">
            <div class="icon_box icon_box_style4 box_shadow1">
              <div class="icon"><i class="ti-pencil-alt"></i></div>
              <div class="icon_box_content">
                <h5>Formules soignées</h5>
                <p>Des ingrédients sélectionnés pour des résultats visibles sur peau, corps et visage.</p>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-sm-6">
            <div class="icon_box icon_box_style4 box_shadow1">
              <div class="icon"><i class="ti-layers"></i></div>
              <div class="icon_box_content">
                <h5>Large gamme</h5>
                <p>Gels nettoyants, laits corporels, huiles et soins ciblés pour toutes les peaux.</p>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-sm-6">
            <div class="icon_box icon_box_style4 box_shadow1">
              <div class="icon"><i class="ti-email"></i></div>
              <div class="icon_box_content">
                <h5>Support réactif</h5>
                <p>Une équipe à votre écoute pour vos commandes, conseils et suivi de livraison.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="section pb_70">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-6">
            <div class="heading_s1 text-center">
              <h2>Notre équipe</h2>
            </div>
            <p class="text-center leads">
              Des passionnées de beauté et du bien-être au service de votre peau.
            </p>
          </div>
        </div>
        <div class="row justify-content-center">
          @foreach ($teamMembers as $member)
            <div class="col-lg-3 col-sm-6">
              <div class="team_box team_style1">
                <div class="team_img">
                  <img src="{{ $member['image'] }}" alt="{{ $member['name'] }}">
                  <ul class="social_icons social_style4">
                    <li><a href="#" aria-label="Facebook"><i class="ion-social-facebook"></i></a></li>
                    <li><a href="#" aria-label="Instagram"><i class="ion-social-instagram-outline"></i></a></li>
                  </ul>
                </div>
                <div class="team_content">
                  <div class="team_title">
                    <h5>{{ $member['name'] }}</h5>
                    <span>{{ $member['role'] }}</span>
                  </div>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    <div class="section bg_redon">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-md-6">
            <div class="heading_s1 text-center">
              <h2>Nos clients témoignent</h2>
            </div>
          </div>
        </div>
        <div class="row justify-content-center">
          <div class="col-lg-9">
            <div
              class="testimonial_wrap testimonial_style1 carousel_slider owl-carousel owl-theme nav_style2"
              wire:ignore
              data-nav="true"
              data-dots="false"
              data-center="true"
              data-loop="true"
              data-autoplay="true"
              data-items="1"
            >
              @foreach ($testimonials as $testimonial)
                <div class="testimonial_box">
                  <div class="testimonial_desc">
                    <p>{{ $testimonial['quote'] }}</p>
                  </div>
                  <div class="author_wrap">
                    <div class="author_img">
                      <img src="{{ $testimonial['image'] }}" alt="{{ $testimonial['name'] }}">
                    </div>
                    <div class="author_name">
                      <h6>{{ $testimonial['name'] }}</h6>
                      <span>{{ $testimonial['role'] }}</span>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
