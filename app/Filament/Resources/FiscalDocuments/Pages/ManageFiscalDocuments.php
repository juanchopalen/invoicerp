<?php

namespace App\Filament\Resources\FiscalDocuments\Pages;

use App\Filament\Resources\FiscalDocuments\FiscalDocumentResource;
use App\Models\Customer;
use App\Models\Product;
use App\Services\InvoiErpApiClient;
use App\Support\FiscalLineMath;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Str;

class ManageFiscalDocuments extends ManageRecords
{
    protected static string $resource = FiscalDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('emit_document')
                ->label(__('Emitir documento'))
                ->icon('heroicon-o-plus')
                ->schema([
                    Select::make('customer_id')
                        ->label(__('Cliente'))
                        ->options(fn (): array => Customer::query()
                            ->where('tenant_id', auth()->user()->tenant_id)
                            ->orderBy('legal_name')
                            ->pluck('legal_name', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('scan_serial')
                        ->label(__('Escanear serial'))
                        ->helperText(__('Escanea o escribe el serial y espera un instante para agregar.'))
                        ->dehydrated(false)
                        ->live(debounce: 450)
                        ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                            if (! is_string($state) || trim($state) === '') {
                                return;
                            }

                            $this->addOrIncrementItemBySerial($state, $set, $get);
                            $set('scan_serial', null);
                        }),
                    Hidden::make('source_system')->default('filament'),
                    Hidden::make('external_reference')->default(fn (): string => (string) Str::uuid()),
                    TextInput::make('document_type')->label(__('Tipo'))->default('invoice')->required(),
                    TextInput::make('currency')->label(__('Moneda'))->default('VES')->required()->maxLength(3),
                    Repeater::make('items')
                        ->label(__('Líneas'))
                        ->minItems(1)
                        ->schema([
                            Select::make('product_id')
                                ->label(__('Producto'))
                                ->options(fn (): array => Product::query()
                                    ->where('tenant_id', auth()->user()->tenant_id)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set): void {
                                    if ($state === null || $state === '') {
                                        return;
                                    }
                                    $tenantId = auth()->user()->tenant_id;
                                    if ($tenantId === null) {
                                        return;
                                    }
                                    $product = Product::query()
                                        ->where('tenant_id', $tenantId)
                                        ->find($state);
                                    if ($product === null) {
                                        return;
                                    }
                                    $set('description', $product->name);
                                    $set('unit_price', (string) $product->unit_price);
                                    $set('tax_rate', (string) $product->tax_rate);
                                }),
                            TextInput::make('description')->label(__('Descripción'))->required(),
                            TextInput::make('qty')->label(__('Cantidad'))->default('1')->required(),
                            TextInput::make('unit_price')->label(__('Precio unit.'))->required(),
                            TextInput::make('tax_rate')->label(__('Tasa imp. %'))->default('0')->required(),
                        ])
                        ->columns(2)
                        ->addActionLabel(__('Añadir línea')),
                ])
                ->action(function (array $data): void {
                    $user = auth()->user();
                    if ($user === null || $user->tenant_id === null) {
                        Notification::make()->title(__('Tu usuario no tiene un tenant asignado.'))->danger()->send();

                        return;
                    }
                    $itemsPayload = [];
                    foreach ($data['items'] as $index => $row) {
                        $lineNo = $index + 1;
                        $amounts = FiscalLineMath::lineTotals(
                            (string) $row['qty'],
                            (string) $row['unit_price'],
                            (string) $row['tax_rate'],
                        );
                        $line = [
                            'line_number' => $lineNo,
                            'description' => (string) $row['description'],
                            'qty' => (string) $row['qty'],
                            'unit_price' => (string) $row['unit_price'],
                            'tax_rate' => (string) $row['tax_rate'],
                            'line_subtotal' => $amounts['line_subtotal'],
                            'line_tax' => $amounts['line_tax'],
                            'line_total' => $amounts['line_total'],
                        ];
                        if (! empty($row['product_id'])) {
                            $line['product_id'] = (int) $row['product_id'];
                        }
                        $itemsPayload[] = $line;
                    }

                    $payload = [
                        'source_system' => $data['source_system'],
                        'external_reference' => $data['external_reference'],
                        'document_type' => $data['document_type'],
                        'currency' => strtoupper((string) $data['currency']),
                        'schema_version' => config('invoicerp.default_schema_version', 1),
                        'customer_id' => (int) $data['customer_id'],
                        'items' => $itemsPayload,
                    ];
                    $bearer = InvoiErpApiClient::freshBearerFor($user);
                    $response = InvoiErpApiClient::forAppUrl()->emit($payload, $bearer);
                    if ($response->successful()) {
                        Notification::make()->title(__('Documento emitido'))->success()->send();

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

    private function addOrIncrementItemBySerial(string $serial, callable $set, callable $get): void
    {
        $tenantId = auth()->user()?->tenant_id;
        if ($tenantId === null) {
            return;
        }

        $product = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('serial', trim($serial))
            ->first();

        if ($product === null) {
            Notification::make()
                ->title(__('Serial no encontrado'))
                ->body(__('No existe un producto con ese serial en esta empresa.'))
                ->warning()
                ->send();

            return;
        }

        $items = $get('items');
        if (! is_array($items)) {
            $items = [];
        }

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            if ((int) ($item['product_id'] ?? 0) !== (int) $product->getKey()) {
                continue;
            }

            $currentQty = (string) ($item['qty'] ?? '0');
            $newQty = $this->normalizeDecimal(bcadd($currentQty, '1', 6));
            $unitPrice = (string) ($item['unit_price'] ?? $product->unit_price);
            $taxRate = (string) ($item['tax_rate'] ?? $product->tax_rate);
            $totals = FiscalLineMath::lineTotals($newQty, $unitPrice, $taxRate);

            $items[$index]['qty'] = $newQty;
            $items[$index]['line_subtotal'] = $totals['line_subtotal'];
            $items[$index]['line_tax'] = $totals['line_tax'];
            $items[$index]['line_total'] = $totals['line_total'];

            $set('items', array_values($items));

            return;
        }

        $qty = '1';
        $unitPrice = (string) $product->unit_price;
        $taxRate = (string) $product->tax_rate;
        $totals = FiscalLineMath::lineTotals($qty, $unitPrice, $taxRate);

        $items[] = [
            'product_id' => (int) $product->getKey(),
            'description' => (string) $product->name,
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
            'line_subtotal' => $totals['line_subtotal'],
            'line_tax' => $totals['line_tax'],
            'line_total' => $totals['line_total'],
        ];

        $set('items', array_values($items));
    }

    private function normalizeDecimal(string $value): string
    {
        $normalized = rtrim(rtrim($value, '0'), '.');

        return $normalized === '' ? '0' : $normalized;
    }
}
