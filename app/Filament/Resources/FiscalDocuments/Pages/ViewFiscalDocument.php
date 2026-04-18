<?php

namespace App\Filament\Resources\FiscalDocuments\Pages;

use App\Filament\Resources\FiscalDocuments\FiscalDocumentResource;
use App\Models\FiscalDocument;
use App\Services\InvoiErpApiClient;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewFiscalDocument extends ViewRecord
{
    protected static string $resource = FiscalDocumentResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->getRecord()->loadMissing(['items.product']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancel_document')
                ->label(__('Anular documento'))
                ->color('danger')
                ->visible(fn (): bool => $this->getRecord() instanceof FiscalDocument && $this->getRecord()->status === 'issued')
                ->schema([
                    TextInput::make('reason')->label(__('Motivo'))->required()->maxLength(2000),
                ])
                ->action(function (array $data): void {
                    /** @var FiscalDocument $record */
                    $record = $this->getRecord();
                    $user = auth()->user();
                    if ($user === null || $user->tenant_id === null) {
                        Notification::make()->title(__('Tu usuario no tiene un tenant asignado.'))->danger()->send();

                        return;
                    }
                    $payload = [
                        'document_id' => $record->getKey(),
                        'reason' => $data['reason'],
                    ];
                    $bearer = InvoiErpApiClient::freshBearerFor($user);
                    $response = InvoiErpApiClient::forAppUrl()->cancel($payload, $bearer);
                    if ($response->successful()) {
                        Notification::make()->title(__('Documento anulado'))->success()->send();
                        $this->redirect(static::getResource()::getUrl('view', ['record' => $record]));

                        return;
                    }
                    Notification::make()
                        ->title(__('API error'))
                        ->body($response->body())
                        ->danger()
                        ->send();
                }),
        ];
    }
}
