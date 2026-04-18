<?php

namespace App\Filament\Resources\Products;

use App\Filament\Concerns\QueriesTenantScopedRecords;
use App\Filament\Resources\Products\Pages\ManageProducts;
use App\Models\Product;
use App\Support\InvoicerpPanelApi;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductResource extends Resource
{
    use QueriesTenantScopedRecords;

    protected static ?string $model = Product::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Facturación';

    protected static ?string $modelLabel = 'Producto';

    protected static ?string $pluralModelLabel = 'Productos';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('sku')->label(__('SKU'))->maxLength(64),
                        TextInput::make('serial')->label(__('Serial'))->maxLength(128),
                        TextInput::make('name')->label(__('Nombre'))->required()->maxLength(255),
                        Textarea::make('description')->label(__('Descripción'))->rows(2)->columnSpanFull(),
                        TextInput::make('unit_price')->label(__('Precio unitario'))->numeric()->required(),
                        TextInput::make('tax_rate')->label(__('Tasa impuesto (%)'))->numeric()->default(0)->required(),
                        TextInput::make('currency')->label(__('Moneda'))->default('VES')->maxLength(3)->minLength(3)->required(),
                        TextInput::make('unit')->label(__('Unidad'))->placeholder('und, kg, …')->maxLength(32),
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
                TextColumn::make('sku')->label(__('SKU'))->searchable()->sortable(),
                TextColumn::make('serial')->label(__('Serial'))->searchable()->toggleable(),
                TextColumn::make('name')->label(__('Nombre'))->searchable()->sortable(),
                TextColumn::make('unit_price')->label(__('Precio'))->alignEnd()->sortable(),
                TextColumn::make('tax_rate')->label(__('IVA %'))->alignEnd(),
                TextColumn::make('currency')->badge(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->using(function (array $data, $livewire, Model $record, ?Table $table): void {
                        unset($data['tenant_id']);
                        $response = InvoicerpPanelApi::client()->putJson(
                            '/api/v1/products/'.$record->getKey(),
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
            'index' => ManageProducts::route('/'),
        ];
    }
}
