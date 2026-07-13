@props([
  'title',
  'breadcrumbItems' => [],
])

<div>
  <x-shopwise-breadcrumb :title="$title" :items="$breadcrumbItems" />

  <div class="main_content">
    <div class="section">
      <div class="container">
        <div class="row">
          <div class="col-lg-3 col-md-4">
            <x-shopwise-account-nav />
          </div>
          <div class="col-lg-9 col-md-8">
            {{ $slot }}
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
