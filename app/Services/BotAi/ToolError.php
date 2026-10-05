<?php

namespace App\Services\BotAi;

use RuntimeException;

/**
 * Erreur métier renvoyée à l'IA comme résultat d'outil (is_error).
 */
class ToolError extends RuntimeException
{
}
