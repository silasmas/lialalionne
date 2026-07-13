@props([
  'title',
  'items' => [],
])

<div class="breadcrumb_section bg_gray page-title-mini">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6">
        <div class="page-title">
          <h1>{{ $title }}</h1>
        </div>
      </div>
      <div class="col-md-6">
        <ol class="breadcrumb justify-content-md-end">
          <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
          @foreach ($items as $item)
            @if ($loop->last)
              <li class="breadcrumb-item active">{{ $item['label'] }}</li>
            @else
              <li class="breadcrumb-item"><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>
            @endif
          @endforeach
        </ol>
      </div>
    </div>
  </div>
</div>
