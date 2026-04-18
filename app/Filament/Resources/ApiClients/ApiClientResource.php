<?php

namespace App\Filament\Resources\ApiClients;

use App\Filament\Concerns\QueriesTenantScopedRecords;
use App\Filament\Resources\ApiClients\Pages\ManageApiClients;
use App\Models\ApiClient;
use App\Support\InvoicerpPanelApi;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ApiClientResource extends Resource
{
    use QueriesTenantScopedRecords;

    protected static ?string $model = ApiClient::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Facturación';

    protected static ?string $modelLabel = 'Cliente API';

    protected static ?string $pluralModelLabel = 'Clientes API';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')->required()->maxLength(255),
                        Select::make('status')
                            ->options([
                                'active' => __('Activo'),
                                'inactive' => __('Inactivo'),
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('key_prefix')->label(__('Prefijo de clave'))->copyable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('last_used_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->using(function (array $data, $livewire, Model $record, ?Table $table): void {
                        unset($data['tenant_id'], $data['key_prefix'], $data['api_key'], $data['last_used_at']);
                        $response = InvoicerpPanelApi::client()->putJson(
                            '/api/v1/api-clients/'.$record->getKey(),
                            $data,
                            InvoicerpPanelApi::bearerForCurrentUser(),
                        );
                        InvoicerpPanelApi::haltUnlessSuccessful($response);
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
            'index' => ManageApiClients::route('/'),
        ];
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
