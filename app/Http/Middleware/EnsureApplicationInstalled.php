<?php

namespace App\Http\Middleware;

use App\Services\InstallationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirige vers l'assistant d'installation si l'application n'est pas prête.
 */
class EnsureApplicationInstalled
{
  /**
   * Routes exemptées de la vérification d'installation.
   *
   * @var list<string>
   */
  private const EXEMPT_PATTERNS = [
    'install',
    'install/*',
    'up',
    'livewire/*',
  ];

  /**
   * @param InstallationService $installation Service état installation
   */
  public function __construct(
    private readonly InstallationService $installation
  ) {
  }

  /**
   * Redirige vers /install tant que l'installation n'est pas terminée.
   *
   * @param Request $request Requête HTTP
   * @param Closure $next Suite du pipeline
   * @return Response Réponse HTTP
   */
  public function handle(Request $request, Closure $next): Response
  {
    if ($this->isExempt($request)) {
      return $next($request);
    }

    if ($this->installation->requiresInstallation()) {
      if ($request->routeIs('install.*') || $request->is('install', 'install/*')) {
        return $next($request);
      }

      return redirect()->route('install.setup');
    }

    if ($request->routeIs('install.*') || $request->is('install', 'install/*')) {
      return redirect()->route('filament.admin.pages.system-setup');
    }

    return $next($request);
  }

  /**
   * Indique si la route courante est exemptée.
   *
   * @param Request $request Requête HTTP
   * @return bool True si exemptée
   */
  private function isExempt(Request $request): bool
  {
    foreach (self::EXEMPT_PATTERNS as $pattern) {
      if ($request->is($pattern)) {
        return true;
      }
    }

    if ($request->is('admin', 'admin/*')) {
      return true;
    }

    if ($request->is('paiement/webhook*')) {
      return true;
    }

    return false;
  }
}
