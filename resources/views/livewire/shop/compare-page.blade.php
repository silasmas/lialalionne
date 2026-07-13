<div>
  <div class="breadcrumb_section bg_gray page-title-mini">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-6">
          <div class="page-title">
            <h1>Comparer</h1>
          </div>
        </div>
        <div class="col-md-6">
          <ol class="breadcrumb justify-content-md-end">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
            <li class="breadcrumb-item active">Comparer</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="main_content">
    <div class="section">
      <div class="container">

        @if ($compareProducts->isEmpty())
          <div class="text-center py-5">
            <p class="text-muted mb-4">Aucun produit à comparer pour le moment.</p>
            <a href="{{ route('shop.catalog') }}" class="btn btn-fill-out rounded-0">Parcourir la boutique</a>
          </div>
        @else
          <div class="d-flex justify-content-end mb-3">
            <x-lw-action action="clearCompare" class="btn btn-fill-line rounded-0" loading-label="...">
              Vider la comparaison
            </x-lw-action>
          </div>

          <div class="compare_box">
            <div class="table-responsive">
              <table class="table table-bordered text-center">
                <tbody>
                  <tr class="pr_image">
                    <td class="row_title">Image</td>
                    @foreach ($compareProducts as $product)
                      <td class="row_img" wire:key="compare-image-{{ $product->id }}">
                        <x-lw-action
                          :action="'removeFromCompare(' . $product->id . ')'"
                          class="btn btn-sm btn-link text-danger float-end"
                          :prevent="false"
                          aria-label="Retirer"
                        >
                          <i class="ion-close"></i>
                        </x-lw-action>
                        <img
                          src="{{ $product->primaryImageUrl() ?? asset('shopwise/assets/images/product_img1.jpg') }}"
                          alt="{{ $product->name }}"
                        >
                      </td>
                    @endforeach
                  </tr>

                  <tr class="pr_title">
                    <td class="row_title">Produit</td>
                    @foreach ($compareProducts as $product)
                      <td class="product_name" wire:key="compare-name-{{ $product->id }}">
                        <a href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
                      </td>
                    @endforeach
                  </tr>

                  <tr class="pr_price">
                    <td class="row_title">Prix</td>
                    @foreach ($compareProducts as $product)
                      <td class="product_price" wire:key="compare-price-{{ $product->id }}">
                        <span class="price">{{ $product->formatPrice() }}</span>
                        @if ($product->hasDiscount())
                          <del>{{ $product->formatPrice($product->compare_at_price) }}</del>
                        @endif
                      </td>
                    @endforeach
                  </tr>

                  <tr>
                    <td class="row_title">Catégorie</td>
                    @foreach ($compareProducts as $product)
                      <td wire:key="compare-category-{{ $product->id }}">
                        {{ $product->category?->name ?? '—' }}
                      </td>
                    @endforeach
                  </tr>

                  <tr class="description">
                    <td class="row_title">Description</td>
                    @foreach ($compareProducts as $product)
                      <td class="row_text" wire:key="compare-desc-{{ $product->id }}">
                        <p>{{ $product->short_description ?: '—' }}</p>
                      </td>
                    @endforeach
                  </tr>

                  <tr class="pr_add_to_cart">
                    <td class="row_title">Panier</td>
                    @foreach ($compareProducts as $product)
                      <td class="row_btn" wire:key="compare-cart-{{ $product->id }}">
                        <x-lw-action
                          :action="'addProductToCart(' . $product->id . ')'"
                          class="btn btn-fill-out"
                          :prevent="false"
                          loading-label="Ajout..."
                        >
                          <i class="icon-basket-loaded"></i> Ajouter
                        </x-lw-action>
                      </td>
                    @endforeach
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
