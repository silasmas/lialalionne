<?php

namespace App\Filament\Resources\NewsletterSubscribers\Pages;

use App\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource;
use App\Models\NewsletterSubscriber;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListNewsletterSubscribers extends ListRecords
{
  protected static string $resource = NewsletterSubscriberResource::class;

  /**
   * @return array<int, Action>
   */
  protected function getHeaderActions(): array
  {
    return [
      Action::make('exportCsv')
        ->label('Exporter en CSV')
        ->icon('heroicon-o-arrow-down-tray')
        ->action(function () {
          $subscribers = NewsletterSubscriber::query()
            ->whereNull('unsubscribed_at')
            ->orderByDesc('subscribed_at')
            ->get(['email', 'subscribed_at']);

          return response()->streamDownload(function () use ($subscribers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['email', 'inscrit_le']);

            foreach ($subscribers as $subscriber) {
              fputcsv($handle, [
                $subscriber->email,
                $subscriber->subscribed_at?->toDateTimeString(),
              ]);
            }

            fclose($handle);
          }, 'newsletter-lialalionne-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
          ]);
        }),
    ];
  }
}
