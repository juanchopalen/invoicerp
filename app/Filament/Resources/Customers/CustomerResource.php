<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Concerns\QueriesTenantScopedRecords;
use App\Filament\Resources\Customers\Pages\ManageCustomers;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Models\State;
use App\Support\InvoicerpPanelApi;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
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

class CustomerResource extends Resource
{
    use QueriesTenantScopedRecords;

    protected static ?string $model = Customer::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Facturación';

    protected static ?string $modelLabel = 'Cliente';

    protected static ?string $pluralModelLabel = 'Clientes';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Datos fiscales'))
                    ->schema([
                        TextInput::make('legal_name')->label(__('Razón social / nombre'))->required()->maxLength(255),
                        TextInput::make('tax_id')->label(__('RIF / identificación fiscal'))->required()->maxLength(32),
                        Textarea::make('address')->label(__('Domicilio fiscal'))->required()->rows(2)->columnSpanFull(),
                        Select::make('country_id')
                            ->label(__('País'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => [$set('state_id', null), $set('city_id', null)])
                            ->options(fn (): array => Country::query()
                                ->active()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()),
                        Select::make('state_id')
                            ->label(__('Estado / provincia'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('city_id', null))
                            ->options(fn (callable $get): array => State::query()
                                ->where('country_id', $get('country_id'))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()),
                        Select::make('city_id')
                            ->label(__('Ciudad'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->options(fn (callable $get): array => City::query()
                                ->where('country_id', $get('country_id'))
                                ->where('state_id', $get('state_id'))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()),
                        TextInput::make('municipality')->label(__('Municipio'))->required()->maxLength(128),
                        TextInput::make('phone')->label(__('Teléfono'))->tel()->maxLength(64),
                        TextInput::make('email')->label(__('Correo'))->email()->maxLength(255),
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
                TextColumn::make('legal_name')->label(__('Nombre'))->searchable()->sortable(),
                TextColumn::make('tax_id')->label(__('RIF'))->searchable(),
                TextColumn::make('municipality')->label(__('Municipio'))->toggleable(),
                TextColumn::make('state.name')->label(__('Estado'))->toggleable(),
                TextColumn::make('city.name')->label(__('Ciudad'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('country.iso2')->label(__('País'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->using(function (array $data, $livewire, Model $record, ?Table $table): void {
                        unset($data['tenant_id']);
                        $response = InvoicerpPanelApi::client()->putJson(
                            '/api/v1/customers/'.$record->getKey(),
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
            'index' => ManageCustomers::route('/'),
        ];
    }
}
