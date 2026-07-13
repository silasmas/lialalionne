<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use RuntimeException;

/**
 * Lecture et mise à jour sélective du fichier .env.
 */
class EnvironmentFileService
{
  /**
   * Clés .env modifiables depuis l'assistant d'installation.
   *
   * @return array<string, array{label: string, type: string, placeholder?: string}>
   */
  public function editableKeys(): array
  {
    return [
      'APP_NAME' => ['label' => 'Nom du site', 'type' => 'text', 'placeholder' => 'Lialalionne'],
      'APP_URL' => ['label' => 'URL du site', 'type' => 'url', 'placeholder' => 'http://127.0.0.1:4000'],
      'APP_ENV' => ['label' => 'Environnement', 'type' => 'text', 'placeholder' => 'local'],
      'APP_DEBUG' => ['label' => 'Mode debug', 'type' => 'boolean'],
      'DB_CONNECTION' => ['label' => 'Driver BDD', 'type' => 'text', 'placeholder' => 'mysql'],
      'DB_HOST' => ['label' => 'Hôte BDD', 'type' => 'text', 'placeholder' => '127.0.0.1'],
      'DB_PORT' => ['label' => 'Port BDD', 'type' => 'text', 'placeholder' => '3306'],
      'DB_DATABASE' => ['label' => 'Base de données', 'type' => 'text'],
      'DB_USERNAME' => ['label' => 'Utilisateur BDD', 'type' => 'text'],
      'DB_PASSWORD' => ['label' => 'Mot de passe BDD', 'type' => 'password'],
      'MAIL_MAILER' => ['label' => 'Mailer', 'type' => 'text', 'placeholder' => 'smtp'],
      'MAIL_HOST' => ['label' => 'Hôte SMTP', 'type' => 'text'],
      'MAIL_PORT' => ['label' => 'Port SMTP', 'type' => 'text'],
      'MAIL_USERNAME' => ['label' => 'Utilisateur SMTP', 'type' => 'text'],
      'MAIL_PASSWORD' => ['label' => 'Mot de passe SMTP', 'type' => 'password'],
      'MAIL_FROM_ADDRESS' => ['label' => 'E-mail expéditeur', 'type' => 'email'],
      'MAIL_FROM_NAME' => ['label' => 'Nom expéditeur', 'type' => 'text'],
    ];
  }

  /**
   * Indique si le fichier .env existe.
   *
   * @return bool True si présent
   */
  public function exists(): bool
  {
    return is_file($this->path());
  }

  /**
   * Lit les clés éditables depuis le .env.
   *
   * @return array<string, string|null> Valeurs par clé
   */
  public function readEditableValues(): array
  {
    $parsed = $this->parseFile();
    $values = [];

    foreach (array_keys($this->editableKeys()) as $key) {
      $values[$key] = $parsed[$key] ?? null;
    }

    return $values;
  }

  /**
   * Met à jour les clés fournies dans le .env.
   *
   * @param array<string, string|null> $values Paires clé/valeur
   * @return void
   */
  public function update(array $values): void
  {
    if (!$this->exists()) {
      throw new RuntimeException('Le fichier .env est introuvable.');
    }

    $lines = file($this->path(), FILE_IGNORE_NEW_LINES);

    if ($lines === false) {
      throw new RuntimeException('Impossible de lire le fichier .env.');
    }

    $allowedKeys = array_keys($this->editableKeys());

    foreach ($values as $key => $value) {
      if (!in_array($key, $allowedKeys, true)) {
        continue;
      }

      $formatted = $this->formatValue((string) ($value ?? ''));
      $pattern = '/^' . preg_quote($key, '/') . '=.*/';
      $replacement = $key . '=' . $formatted;
      $found = false;

      foreach ($lines as $index => $line) {
        if (preg_match($pattern, $line) === 1) {
          $lines[$index] = $replacement;
          $found = true;
          break;
        }
      }

      if (!$found) {
        $lines[] = $replacement;
      }
    }

    file_put_contents($this->path(), implode(PHP_EOL, $lines) . PHP_EOL);
  }

  /**
   * Génère APP_KEY via Artisan si absent.
   *
   * @return string Clé générée ou existante
   */
  public function ensureAppKey(): string
  {
    $current = env('APP_KEY');

    if (!empty($current)) {
      return $current;
    }

    Artisan::call('key:generate', ['--force' => true, '--show' => true]);

    return trim(Artisan::output());
  }

  /**
   * Indique si APP_KEY est renseignée.
   *
   * @return bool True si configurée
   */
  public function hasAppKey(): bool
  {
    return !empty(env('APP_KEY'));
  }

  /**
   * Indique si la configuration base de données minimale est présente.
   *
   * @return bool True si DB_DATABASE est renseigné
   */
  public function hasDatabaseConfig(): bool
  {
    return !empty(env('DB_DATABASE')) && !empty(env('DB_CONNECTION'));
  }

  /**
   * @return string Chemin absolu du .env
   */
  private function path(): string
  {
    return base_path('.env');
  }

  /**
   * Parse le fichier .env en tableau associatif.
   *
   * @return array<string, string> Variables d'environnement
   */
  private function parseFile(): array
  {
    if (!$this->exists()) {
      return [];
    }

    $parsed = [];
    $lines = file($this->path(), FILE_IGNORE_NEW_LINES);

    if ($lines === false) {
      return [];
    }

    foreach ($lines as $line) {
      $line = trim($line);

      if ($line === '' || str_starts_with($line, '#')) {
        continue;
      }

      if (!str_contains($line, '=')) {
        continue;
      }

      [$key, $value] = explode('=', $line, 2);
      $parsed[trim($key)] = $this->unquote(trim($value));
    }

    return $parsed;
  }

  /**
   * Formate une valeur pour écriture dans le .env.
   *
   * @param string $value Valeur brute
   * @return string Valeur échappée
   */
  private function formatValue(string $value): string
  {
    if ($value === '') {
      return '';
    }

    if (preg_match('/[\s#="\']/', $value) === 1) {
      return '"' . str_replace('"', '\"', $value) . '"';
    }

    return $value;
  }

  /**
   * Retire les guillemets d'une valeur .env.
   *
   * @param string $value Valeur lue
   * @return string Valeur nettoyée
   */
  private function unquote(string $value): string
  {
    if (
      (str_starts_with($value, '"') && str_ends_with($value, '"'))
      || (str_starts_with($value, "'") && str_ends_with($value, "'"))
    ) {
      return substr($value, 1, -1);
    }

    return $value;
  }
}
