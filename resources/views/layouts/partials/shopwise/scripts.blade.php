<script src="{{ $sw('js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ $sw('js/popper.min.js') }}"></script>
<script src="{{ $sw('bootstrap/js/bootstrap.min.js') }}"></script>
<script src="{{ $sw('owlcarousel/js/owl.carousel.min.js') }}"></script>
<script src="{{ $sw('js/magnific-popup.min.js') }}"></script>
<script src="{{ $sw('js/waypoints.min.js') }}"></script>
<script src="{{ $sw('js/parallax.js') }}"></script>
<script src="{{ $sw('js/jquery.countdown.min.js') }}"></script>
<script src="{{ $sw('js/imagesloaded.pkgd.min.js') }}"></script>
<script src="{{ $sw('js/isotope.min.js') }}"></script>
<script src="{{ $sw('js/jquery.dd.min.js') }}"></script>
<script src="{{ $sw('js/slick.min.js') }}"></script>
<script src="{{ $sw('js/jquery.elevatezoom.js') }}"></script>
<script src="{{ $sw('js/scripts.js') }}"></script>
<script>
  (function () {
    /**
     * Masque le preloader Shopwise même si window.load a déjà eu lieu.
     *
     * @return void
     */
    function hideShopwisePreloader() {
      var preloader = document.querySelector('.preloader');

      if (!preloader || preloader.classList.contains('is-hidden')) {
        return;
      }

      preloader.classList.add('loaded', 'is-hidden');

      if (window.jQuery) {
        window.jQuery(preloader).stop(true, true).fadeOut(0);
      }
    }

    /**
     * Initialise les carrousels Owl après rendu Livewire.
     *
     * @return void
     */
    function initShopwiseCarousels() {
      if (!window.jQuery || !window.jQuery.fn.owlCarousel) {
        return;
      }

      window.jQuery('.carousel_slider.owl-carousel').each(function () {
        var carousel = window.jQuery(this);

        if (carousel.hasClass('owl-loaded')) {
          return;
        }

        carousel.owlCarousel({
          loop: carousel.data('loop') !== false,
          margin: carousel.data('margin') || 20,
          nav: carousel.data('nav') !== false,
          dots: carousel.data('dots') === true,
          autoplay: carousel.data('autoplay') === true,
          center: carousel.data('center') === true,
          navText: ['<i class="ion-ios-arrow-left"></i>', '<i class="ion-ios-arrow-right"></i>'],
          responsive: carousel.data('responsive') || {
            0: { items: 1 },
            481: { items: 2 },
            768: { items: 3 },
            1199: { items: 4 }
          }
        });
      });
    }

    /**
     * Réinitialise la galerie produit (slick + zoom) sur la fiche produit.
     *
     * @return void
     */
    function initShopwiseProductGallery() {
      if (!window.jQuery) {
        return;
      }

      var gallery = window.jQuery('#pr_item_gallery');

      if (!gallery.length) {
        return;
      }

      if (typeof window.jQuery.fn.slick !== 'undefined' && !gallery.hasClass('slick-initialized')) {
        gallery.slick({
          rtl: false,
          arrows: false,
          dots: false,
          infinite: false,
          vertical: gallery.data('vertical') === true,
          verticalSwiping: gallery.data('vertical-swiping') === true,
          slidesToShow: gallery.data('slides-to-show') || 5,
          slidesToScroll: gallery.data('slides-to-scroll') || 1
        });
      }

      var image = window.jQuery('#product_img');

      if (image.length && typeof window.jQuery.fn.elevateZoom !== 'undefined') {
        var zoomImage = image.data('elevateZoom');

        if (zoomImage) {
          window.jQuery.removeData(image, 'elevateZoom');
          window.jQuery('.zoomContainer').remove();
        }

        image.elevateZoom({
          cursor: 'crosshair',
          easing: true,
          gallery: 'pr_item_gallery',
          zoomType: 'inner',
          galleryActiveClass: 'active'
        });
      }
    }

    if (document.readyState === 'complete') {
      hideShopwisePreloader();
    } else {
      document.addEventListener('DOMContentLoaded', function () {
        setTimeout(hideShopwisePreloader, 400);
      });
      window.addEventListener('load', hideShopwisePreloader);
    }

    setTimeout(hideShopwisePreloader, 2500);
    document.addEventListener('livewire:navigated', hideShopwisePreloader);

    document.addEventListener('DOMContentLoaded', function () {
      setTimeout(initShopwiseCarousels, 500);
      setTimeout(initShopwiseProductGallery, 700);
    });

    document.addEventListener('livewire:navigated', function () {
      setTimeout(initShopwiseCarousels, 300);
      setTimeout(initShopwiseProductGallery, 500);
    });
  })();
</script>
@livewireScripts
