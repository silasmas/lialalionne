<?php

namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use App\Services\BotAi\BotAiAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Point d'entrée unique du bot WhatsApp, appelé par le webhook Callbell à
 * chaque message de la cliente (même principe que le bot Rachoux Traiteur).
 */
class BotDialogueController extends Controller
{
  /**
   * POST /api/bot/v1/dialogue?bot_token=…  {phone, reponse}
   *
   * Répond toujours 200 : {ok, etat, text}. etat = « ia » ou « rx_humain »
   * (Callbell assigne alors la conversation à l'équipe).
   *
   * @param Request $request Requête
   * @param BotAiAgent $agent Conseillère IA
   * @return JsonResponse Réponse à afficher sur WhatsApp
   */
  public function __invoke(Request $request, BotAiAgent $agent): JsonResponse
  {
    $data = $request->validate([
      'phone' => ['required', 'string', 'max:30'],
      'reponse' => ['nullable', 'string', 'max:4000'],
    ]);

    $result = $agent->handle($data['phone'], (string) ($data['reponse'] ?? ''));

    return response()->json(['ok' => true] + $result);
  }
}
