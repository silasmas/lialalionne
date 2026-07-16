<?php

namespace App\Http\Middleware;

use App\Services\InstallationService;
use App\Services\SiteSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Affiche la page Coming Soon lorsque le mode est activé côté admin.
 */
class RedirectIfComingSoon
{
  public const BYPASS_SESSION_KEY = 'coming_soon_bypass';

  /**
   * @param InstallationService $installation Service installation
   * @param SiteSettingsService $settings Service paramètres
   */
  public function __construct(
    private readonly InstallationService $installation,
    private readonly SiteSettingsService $settings
  ) {
  }

  /**
   * Redirige vers la page Coming Soon si le mode est actif.
   *
   * @param Request $request Requête HTTP
   * @param Closure $next Suite du pipeline
   * @return Response Réponse HTTP
   */
  public function handle(Request $request, Closure $next): Response
  {
    if ($this->installation->requiresInstallation()) {
      return $next($request);
    }

    try {
      if (!$this->settings->isComingSoonEnabled()) {
        return $next($request);
      }
    } catch (\Throwable) {
      return $next($request);
    }

    if ($this->isExempt($request) || $this->hasBypass($request)) {
      return $next($request);
    }

    if ($request->routeIs('home', 'coming-soon')) {
      return $next($request);
    }

    return redirect()->route('home');
  }

  /**
   * Indique si l'utilisateur a un accès manuel (session bypass).
   *
   * @param Request $request Requête HTTP
   * @return bool True si bypass actif
   */
  private function hasBypass(Request $request): bool
  {
    return (bool) $request->session()->get(self::BYPASS_SESSION_KEY, false);
  }

  /**
   * Indique si la route est toujours accessible en mode Coming Soon.
   *
   * @param Request $request Requête HTTP
   * @return bool True si exemptée
   */
  private function isExempt(Request $request): bool
  {
    if ($request->is('admin', 'admin/*', 'install', 'install/*', 'up', 'livewire/*', 'media', 'media/*')) {
      return true;
    }

    if ($request->is('paiement/webhook*')) {
      return true;
    }

    if ($request->routeIs('coming-soon', 'coming-soon.unlock')) {
      return true;
    }

    return false;
  }
}
