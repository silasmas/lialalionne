<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sert les fichiers du disque public sans dépendre du symlink public/storage.
 *
 * Contourne les 403 fréquents sur mutualisé (FollowSymLinks désactivé, permissions).
 */
class PublicMediaController extends Controller
{
  /**
   * Diffuse un fichier stocké dans storage/app/public.
   *
   * @param Request $request Requête HTTP
   * @param string $path Chemin relatif (ex. products/xxx.webp)
   * @return Response Fichier binaire ou 404
   */
  public function show(Request $request, string $path): Response
  {
    $path = str_replace('\\', '/', $path);
    $path = ltrim($path, '/');

    if ($path === '' || str_contains($path, '..')) {
      abort(404);
    }

    $disk = Storage::disk('public');

    if (!$disk->exists($path)) {
      abort(404);
    }

    return $disk->response($path, headers: [
      'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
  }
}
