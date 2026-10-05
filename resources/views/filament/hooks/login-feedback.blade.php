<div
  id="admin-login-js-warning"
  hidden
  class="mb-4 rounded-lg border border-danger-300 bg-danger-50 px-4 py-3 text-sm text-danger-700"
  role="alert"
>
  Le formulaire de connexion n'a pas pu démarrer. Rechargez la page, puis réessayez.
</div>
<script>
  window.setTimeout(function () {
    if (typeof window.Livewire === 'undefined') {
      var warning = document.getElementById('admin-login-js-warning');
      if (warning) {
        warning.hidden = false;
      }
    }
  }, 1500);
</script>
