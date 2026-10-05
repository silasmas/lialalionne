<?php

namespace App\Support;

/**
 * Normalise les variables serveur quand le vhost pointe sur public_html
 * (réécriture LiteSpeed vers /public/...), afin que Laravel voie les vraies URI.
 */
class HostingerDocumentRoot
{
  /**
   * Retire le préfixe /public ajouté par la réécriture Hostinger.
   *
   * @param array<string, mixed> $server Variables $_SERVER
   * @return array<string, mixed> Variables normalisées
   */
  public static function normalizeServer(array $server): array
  {
    foreach (['REQUEST_URI', 'PATH_INFO', 'PHP_SELF'] as $key) {
      if (!isset($server[$key]) || !is_string($server[$key])) {
        continue;
      }

      $server[$key] = self::stripPublicPrefix($server[$key]);
    }

    if (isset($server['SCRIPT_NAME']) && is_string($server['SCRIPT_NAME']) && str_ends_with($server['SCRIPT_NAME'], '/public/index.php')) {
      $server['SCRIPT_NAME'] = '/index.php';
    }

    return $server;
  }

  /**
   * Enlève un préfixe /public en tête de chemin, en conservant la query string.
   *
   * @param string $value URI ou chemin
   * @return string Valeur sans préfixe /public
   */
  public static function stripPublicPrefix(string $value): string
  {
    if ($value === '/public' || $value === '/public/') {
      return '/';
    }

    if (str_starts_with($value, '/public?')) {
      return '/' . substr($value, strlen('/public'));
    }

    if (str_starts_with($value, '/public/')) {
      $stripped = substr($value, strlen('/public'));

      return $stripped === '' ? '/' : $stripped;
    }

    return $value;
  }
}
