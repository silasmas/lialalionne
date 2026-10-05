<?php

namespace Tests\Unit;

use App\Support\HostingerDocumentRoot;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Vérifie la normalisation des URI Hostinger (préfixe /public).
 */
class HostingerDocumentRootTest extends TestCase
{
  /**
   * @return array<string, array{0: string, 1: string}>
   */
  public static function uriProvider(): array
  {
    return [
      'livewire js' => ['/public/livewire-414ba8eb/livewire.min.js', '/livewire-414ba8eb/livewire.min.js'],
      'livewire update' => ['/public/livewire-414ba8eb/update', '/livewire-414ba8eb/update'],
      'query string' => ['/public/admin/login?foo=1', '/admin/login?foo=1'],
      'root public' => ['/public', '/'],
      'already clean' => ['/admin/login', '/admin/login'],
    ];
  }

  #[DataProvider('uriProvider')]
  public function testStripPublicPrefix(string $input, string $expected): void
  {
    $this->assertSame($expected, HostingerDocumentRoot::stripPublicPrefix($input));
  }

  public function testNormalizeServerRewritesScriptName(): void
  {
    $normalized = HostingerDocumentRoot::normalizeServer([
      'REQUEST_URI' => '/public/livewire-abc/update',
      'SCRIPT_NAME' => '/public/index.php',
      'PHP_SELF' => '/public/index.php',
    ]);

    $this->assertSame('/livewire-abc/update', $normalized['REQUEST_URI']);
    $this->assertSame('/index.php', $normalized['SCRIPT_NAME']);
    $this->assertSame('/index.php', $normalized['PHP_SELF']);
  }
}
