<?php

namespace App\Services\BotAi;

use App\Models\BotConversation;
use App\Services\BotCommerceService;
use Illuminate\Validation\ValidationException;

/**
 * Outils que la conseillère IA peut appeler. Chaque outil passe par
 * BotCommerceService : l'IA ne voit que des données réelles (prix, stock,
 * commandes) et n'agit que pour le numéro WhatsApp de la conversation.
 */
class BotAiTools
{
  /**
   * @param BotCommerceService $bot Service métier
   */
  public function __construct(private readonly BotCommerceService $bot)
  {
  }

  /**
   * Définitions au format de l'API Claude (name, description, input_schema).
   *
   * @return list<array<string, mixed>> Outils
   */
  public function definitions(): array
  {
    $items = [
      'type' => 'array',
      'description' => 'Lignes de produits.',
      'items' => [
        'type' => 'object',
        'properties' => [
          'sku' => ['type' => 'string', 'description' => 'SKU exact du produit (ou de la variante).'],
          'quantity' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20],
          'variant' => ['type' => 'string', 'description' => 'Nom de la variante si le produit en a (ex. « Grand format »).'],
        ],
        'required' => ['sku'],
      ],
    ];

    $orderNumber = ['type' => 'string', 'description' => 'Numéro de commande, ex. LL-ABCD1234.'];

    return [
      [
        'name' => 'rechercher_produits',
        'description' => 'Cherche des produits actifs du catalogue (nom, description, SKU) avec prix réels et stock. Sans paramètre, renvoie tout le catalogue.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'recherche' => ['type' => 'string', 'description' => 'Mots-clés (ex. « ventre plat », « maca »).'],
            'categorie' => ['type' => 'string', 'description' => 'Slug de catégorie (ex. soin-fessier, minceur-complements).'],
          ],
        ],
      ],
      [
        'name' => 'fiche_produit',
        'description' => 'Fiche complète d\'un produit : description, ingrédients, mode d\'emploi, photos, produits de la même routine.',
        'input_schema' => [
          'type' => 'object',
          'properties' => ['sku' => ['type' => 'string']],
          'required' => ['sku'],
        ],
      ],
      [
        'name' => 'lister_routines',
        'description' => 'Routines (gammes de produits qui vont ensemble) avec prix séparé et prix kit.',
        'input_schema' => ['type' => 'object', 'properties' => new \stdClass()],
      ],
      [
        'name' => 'infos_boutique',
        'description' => 'Tarifs et délais de livraison (avec leur id), retrait en boutique, moyens de paiement disponibles, devises.',
        'input_schema' => ['type' => 'object', 'properties' => new \stdClass()],
      ],
      [
        'name' => 'calculer_devis',
        'description' => 'Calcule le récapitulatif (sous-total, livraison, remise, total) SANS créer la commande. Obligatoire avant creer_commande.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'items' => $items,
            'fulfillment_type' => ['type' => 'string', 'enum' => ['delivery', 'pickup']],
            'shipping_rate_id' => ['type' => 'integer', 'description' => 'Id du tarif choisi (infos_boutique). Par défaut le moins cher.'],
            'coupon_code' => ['type' => 'string'],
            'currency' => ['type' => 'string', 'enum' => ['CDF', 'USD']],
          ],
          'required' => ['items', 'fulfillment_type'],
        ],
      ],
      [
        'name' => 'creer_commande',
        'description' => 'Crée la commande pour la cliente qui écrit, UNIQUEMENT après un devis et un « oui » clair. Les articles doivent être ceux du dernier devis.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'items' => $items,
            'name' => ['type' => 'string', 'description' => 'Nom complet (obligatoire pour une nouvelle cliente).'],
            'fulfillment_type' => ['type' => 'string', 'enum' => ['delivery', 'pickup']],
            'shipping_rate_id' => ['type' => 'integer'],
            'address' => ['type' => 'string', 'description' => 'Adresse de livraison (obligatoire si delivery).'],
            'commune' => ['type' => 'string'],
            'city' => ['type' => 'string', 'description' => 'Par défaut Kinshasa.'],
            'payment_method' => ['type' => 'string', 'enum' => ['mobile_money', 'card', 'cod']],
            'coupon_code' => ['type' => 'string'],
            'currency' => ['type' => 'string', 'enum' => ['CDF', 'USD']],
            'notes' => ['type' => 'string', 'description' => 'Consigne de livraison éventuelle.'],
          ],
          'required' => ['items', 'fulfillment_type', 'payment_method'],
        ],
      ],
      [
        'name' => 'payer_mobile_money',
        'description' => 'Envoie la demande de paiement Mobile Money sur le téléphone de la cliente (elle valide avec son code secret).',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'order_number' => $orderNumber,
            'payer_phone' => ['type' => 'string', 'description' => 'Numéro à débiter si différent du numéro WhatsApp.'],
            'operator' => ['type' => 'string', 'enum' => ['mpesa', 'airtel', 'orange', 'afrimoney'], 'description' => 'Facultatif : deviné d\'après le numéro.'],
          ],
          'required' => ['order_number'],
        ],
      ],
      [
        'name' => 'verifier_paiement',
        'description' => 'Vérifie si le paiement Mobile Money d\'une commande a été validé.',
        'input_schema' => [
          'type' => 'object',
          'properties' => ['order_number' => $orderNumber],
          'required' => ['order_number'],
        ],
      ],
      [
        'name' => 'lien_paiement_carte',
        'description' => 'Renvoie le lien de paiement par carte bancaire à envoyer à la cliente.',
        'input_schema' => [
          'type' => 'object',
          'properties' => ['order_number' => $orderNumber],
          'required' => ['order_number'],
        ],
      ],
      [
        'name' => 'suivi_commandes',
        'description' => 'Statut d\'une commande de la cliente, ou ses dernières commandes si aucun numéro n\'est donné.',
        'input_schema' => [
          'type' => 'object',
          'properties' => ['order_number' => $orderNumber],
        ],
      ],
      [
        'name' => 'verifier_code_promo',
        'description' => 'Vérifie un code promo pour les produits donnés.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'code' => ['type' => 'string'],
            'items' => $items,
          ],
          'required' => ['code', 'items'],
        ],
      ],
      [
        'name' => 'transferer_humain',
        'description' => 'Transfère la conversation à une conseillère humaine (demande de la cliente, réclamation, santé, problème non résolu, grosse commande).',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'raison' => ['type' => 'string', 'description' => 'Raison courte, visible par l\'équipe.'],
          ],
          'required' => ['raison'],
        ],
      ],
    ];
  }

  /**
   * Exécute un outil pour la conversation donnée.
   *
   * @param string $name Nom de l'outil
   * @param array<string, mixed> $input Paramètres fournis par l'IA
   * @param BotConversation $conversation Conversation (numéro de la cliente)
   * @return array{result: array<string, mixed>, is_error: bool} Résultat
   */
  public function execute(string $name, array $input, BotConversation $conversation): array
  {
    $phone = $conversation->phone;

    try {
      $result = match ($name) {
        'rechercher_produits' => ['produits' => $this->bot->catalogue(
          $input['recherche'] ?? null,
          $input['categorie'] ?? null
        )->values()->all()],
        'fiche_produit' => $this->productDetail((string) ($input['sku'] ?? '')),
        'lister_routines' => ['routines' => $this->bot->routines()->all()],
        'infos_boutique' => $this->bot->shopInfo(),
        'calculer_devis' => $this->quote($input, $conversation),
        'creer_commande' => $this->createOrder($input, $conversation),
        'payer_mobile_money' => $this->bot->payMobileMoney(
          $this->order((string) ($input['order_number'] ?? ''), $phone),
          $input['payer_phone'] ?? null,
          $input['operator'] ?? null
        ),
        'verifier_paiement' => $this->bot->verifyPayment($this->order((string) ($input['order_number'] ?? ''), $phone)),
        'lien_paiement_carte' => ['card_payment_url' => $this->bot->cardPaymentUrl(
          $this->order((string) ($input['order_number'] ?? ''), $phone)
        )],
        'suivi_commandes' => !empty($input['order_number'])
          ? ['commande' => $this->bot->orderPayload($this->order((string) $input['order_number'], $phone))]
          : ['commandes' => $this->bot->ordersForPhone($phone)],
        'verifier_code_promo' => $this->bot->checkCoupon((string) ($input['code'] ?? ''), (array) ($input['items'] ?? []), $phone),
        'transferer_humain' => $this->handoff($conversation, (string) ($input['raison'] ?? '')),
        default => throw new ToolError("Outil inconnu : $name"),
      };

      return ['result' => $result, 'is_error' => false];
    } catch (ValidationException $exception) {
      return ['result' => ['erreur' => collect($exception->errors())->flatten()->implode(' ')], 'is_error' => true];
    } catch (ToolError $exception) {
      return ['result' => ['erreur' => $exception->getMessage()], 'is_error' => true];
    }
  }

  /**
   * @param string $sku SKU ou slug
   * @return array<string, mixed> Fiche
   */
  private function productDetail(string $sku): array
  {
    $product = $this->bot->findProduct($sku);

    if (!$product) {
      throw new ToolError("Produit introuvable : « $sku ». Utilise rechercher_produits.");
    }

    return ['produit' => $this->bot->productDetail($product)];
  }

  /**
   * Calcule le devis et le mémorise (garde-fou de creer_commande).
   *
   * @param array<string, mixed> $input Paramètres
   * @param BotConversation $conversation Conversation
   * @return array<string, mixed> Devis
   */
  private function quote(array $input, BotConversation $conversation): array
  {
    $quote = $this->bot->quote(array_merge($input, ['phone' => $conversation->phone]));

    $meta = $conversation->meta ?? [];
    $meta['last_quote'] = [
      'signature' => $this->signature((array) $input['items']),
      'total' => $quote['total']['label'] ?? null,
      'at' => now()->toIso8601String(),
    ];
    $conversation->meta = $meta;
    $conversation->save();

    return ['devis' => $quote, 'rappel' => 'Envoie ce récapitulatif et attends un « oui » clair avant creer_commande.'];
  }

  /**
   * Crée la commande si elle correspond au dernier devis.
   *
   * @param array<string, mixed> $input Paramètres
   * @param BotConversation $conversation Conversation
   * @return array<string, mixed> Commande
   */
  private function createOrder(array $input, BotConversation $conversation): array
  {
    $lastQuote = $conversation->meta['last_quote'] ?? null;

    if (!$lastQuote || $lastQuote['signature'] !== $this->signature((array) ($input['items'] ?? []))) {
      throw new ToolError('Commande refusée : les articles ne correspondent pas au dernier devis. Appelle calculer_devis, envoie le récapitulatif et attends le « oui » de la cliente.');
    }

    $order = $this->bot->createOrder(array_merge($input, ['phone' => $conversation->phone]));

    $meta = $conversation->meta ?? [];
    unset($meta['last_quote']);
    $meta['last_order'] = $order->order_number;
    $conversation->meta = $meta;
    $conversation->user_id = $order->user_id;
    $conversation->save();

    return [
      'commande' => $this->bot->orderPayload($order),
      'etape_suivante' => match ($input['payment_method'] ?? 'mobile_money') {
        'cod' => 'Commande confirmée, paiement à la livraison : rappelle le montant.',
        'card' => 'Appelle lien_paiement_carte et envoie le lien.',
        default => 'Demande quel numéro débiter (ce numéro WhatsApp ou un autre), puis payer_mobile_money.',
      },
    ];
  }

  /**
   * @param string $orderNumber Numéro de commande
   * @param string $phone Numéro de la conversation
   * @return \App\Models\Order Commande de cette cliente
   */
  private function order(string $orderNumber, string $phone): \App\Models\Order
  {
    $order = $this->bot->findOrderForPhone($orderNumber, $phone);

    if (!$order) {
      throw new ToolError("Commande « $orderNumber » introuvable pour cette cliente.");
    }

    return $order;
  }

  /**
   * @param BotConversation $conversation Conversation
   * @param string $reason Raison du transfert
   * @return array<string, mixed> Confirmation
   */
  private function handoff(BotConversation $conversation, string $reason): array
  {
    $conversation->forceFill([
      'status' => BotConversation::STATUS_HUMAN,
      'handoff_reason' => mb_substr($reason, 0, 250),
      'handed_off_at' => now(),
    ])->save();

    return ['transfere' => true, 'consigne' => 'Écris un court message de transfert à la cliente, rien d\'autre.'];
  }

  /**
   * Empreinte stable des lignes (SKU + quantité), insensible à l'ordre.
   *
   * @param array<int, array<string, mixed>> $items Lignes
   * @return string Empreinte
   */
  private function signature(array $items): string
  {
    $lines = array_map(
      fn ($item) => strtoupper(trim((string) ($item['sku'] ?? ''))) . '|' . trim((string) ($item['variant'] ?? '')) . '|' . max(1, (int) ($item['quantity'] ?? 1)),
      $items
    );
    sort($lines);

    return implode(';', $lines);
  }
}
