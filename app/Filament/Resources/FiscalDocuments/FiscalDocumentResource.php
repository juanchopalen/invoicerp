<?php

namespace App\Filament\Resources\FiscalDocuments;

use App\Core\Domain\DocumentStatus;
use App\Filament\Concerns\QueriesTenantScopedRecords;
use App\Filament\Resources\FiscalDocuments\Pages\ManageFiscalDocuments;
use App\Filament\Resources\FiscalDocuments\Pages\ViewFiscalDocument;
use App\Models\FiscalDocument;
use App\Services\InvoiErpApiClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FiscalDocumentResource extends Resource
{
    use QueriesTenantScopedRecords;

    protected static ?string $model = FiscalDocument::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Facturación';

    protected static ?string $modelLabel = 'Documento fiscal';

    protected static ?string $pluralModelLabel = 'Documentos fiscales';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'document_number';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Cliente (datos de facturación)'))
                    ->schema([
                        TextInput::make('customer_legal_name')->label(__('Razón social / nombre'))->disabled(),
                        TextInput::make('customer_tax_id')->label(__('RIF / identificación fiscal'))->disabled(),
                        TextInput::make('customer_email')->label(__('Correo'))->disabled(),
                        TextInput::make('customer_phone')->label(__('Teléfono'))->disabled(),
                        TextInput::make('customer_address')->label(__('Domicilio fiscal'))->disabled()->columnSpanFull(),
                        TextInput::make('customer_municipality')->label(__('Municipio'))->disabled(),
                        TextInput::make('customer_state')->label(__('Estado / provincia'))->disabled(),
                        TextInput::make('customer_city')->label(__('Ciudad'))->disabled(),
                        TextInput::make('customer_country')->label(__('País'))->disabled(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(__('Documento'))
                    ->schema([
                        TextInput::make('document_number')->disabled(),
                        TextInput::make('source_system')->disabled(),
                        TextInput::make('external_reference')->disabled(),
                        TextInput::make('document_type')->disabled(),
                        TextInput::make('status')->disabled(),
                        TextInput::make('currency')->disabled(),
                        TextInput::make('schema_version')->disabled(),
                        TextInput::make('subtotal')->disabled(),
                        TextInput::make('tax_total')->disabled(),
                        TextInput::make('total')->disabled(),
                        TextInput::make('hash')->disabled()->columnSpanFull(),
                        TextInput::make('issued_at')->disabled(),
                        TextInput::make('cancelled_at')->disabled(),
                        TextInput::make('cancellation_reason')->disabled()->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(__('Líneas'))
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->disabled()
                            ->schema([
                                Select::make('product_id')
                                    ->label(__('Producto catálogo'))
                                    ->relationship('product', 'name')
                                    ->disabled()
                                    ->placeholder('—'),
                                TextInput::make('line_number')->numeric()->disabled(),
                                TextInput::make('description')->disabled(),
                                TextInput::make('qty')->disabled(),
                                TextInput::make('unit_price')->disabled(),
                                TextInput::make('tax_rate')->disabled(),
                                TextInput::make('line_subtotal')->disabled(),
                                TextInput::make('line_tax')->disabled(),
                                TextInput::make('line_total')->disabled(),
                            ])
                            ->columns(4),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')->searchable()->sortable(),
                TextColumn::make('customer_legal_name')->label(__('Cliente'))->searchable()->toggleable(),
                TextColumn::make('source_system')->toggleable(),
                TextColumn::make('external_reference')->limit(24)->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('total')
                    ->alignEnd()
                    ->sortable()
                    ->money(fn (FiscalDocument $record): string => $record->currency),
                TextColumn::make('issued_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Estado'))
                    ->options([
                        DocumentStatus::Draft->value => __('Borrador'),
                        DocumentStatus::Issued->value => __('Emitido'),
                        DocumentStatus::Cancelled->value => __('Anulado'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('cancel_document')
                    ->label(__('Anular'))
                    ->color('danger')
                    ->visible(fn (FiscalDocument $record): bool => $record->status === 'issued')
                    ->schema([
                        TextInput::make('reason')->label(__('Motivo'))->required()->maxLength(2000),
                    ])
                    ->action(function (FiscalDocument $record, array $data): void {
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

                            return;
                        }
                        Notification::make()
                            ->title(__('API error'))
                            ->body($response->body())
                            ->danger()
                            ->send();
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return static::scopeQueryToCurrentTenant(parent::getEloquentQuery());
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFiscalDocuments::route('/'),
            'view' => ViewFiscalDocument::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
