<?php

namespace App\Filament\Resources\Tenants;

use App\Filament\Resources\Tenants\Pages\ManageTenants;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\Tenant;
use App\Support\InvoicerpPanelApi;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $modelLabel = 'Empresa';

    protected static ?string $pluralModelLabel = 'Empresas';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Empresa'))
                    ->schema([
                        TextInput::make('name')->label(__('Nombre interno'))->required()->maxLength(255),
                        TextInput::make('slug')->label(__('Slug'))->required()->maxLength(255)->unique(ignoreRecord: true),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(__('Datos fiscales del emisor'))
                    ->schema([
                        TextInput::make('legal_name')->label(__('Razón social'))->required()->maxLength(255),
                        TextInput::make('rif')->label(__('RIF'))->required()->maxLength(32)->unique(ignoreRecord: true),
                        TextInput::make('trade_name')->label(__('Nombre comercial'))->maxLength(255),
                        Textarea::make('fiscal_address')->label(__('Domicilio fiscal'))->required()->rows(2)->columnSpanFull(),
                        Select::make('country_id')
                            ->label(__('País'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (callable $set): array => [$set('state_id', null), $set('city_id', null)])
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
                            ->afterStateUpdated(fn (callable $set): null => $set('city_id', null))
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
                        Select::make('is_special_taxpayer')
                            ->label(__('Contribuyente especial'))
                            ->options([
                                0 => __('No'),
                                1 => __('Sí'),
                            ])
                            ->default(0)
                            ->required(),
                        TextInput::make('special_taxpayer_resolution')->label(__('Providencia contribuyente especial'))->maxLength(255),
                        TextInput::make('withholding_agent_number')->label(__('Número agente de retención'))->maxLength(255),
                        TextInput::make('economic_activity')->label(__('Actividad económica'))->maxLength(255),
                        TextInput::make('establishment_code')->label(__('Código de establecimiento'))->maxLength(10),
                        TextInput::make('emission_point')->label(__('Punto de emisión'))->maxLength(10),
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
                TextColumn::make('name')->label(__('Nombre interno'))->searchable()->sortable(),
                TextColumn::make('legal_name')->label(__('Razón social'))->searchable()->sortable(),
                TextColumn::make('rif')->label(__('RIF'))->searchable(),
                TextColumn::make('state.name')->label(__('Estado'))->toggleable(),
                TextColumn::make('city.name')->label(__('Ciudad'))->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_special_taxpayer')->label(__('Especial'))->boolean(),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(function (array $data, $livewire, Model $record, ?Table $table): void {
                        $response = InvoicerpPanelApi::client()->putJson(
                            '/api/v1/tenants/'.$record->getKey(),
                            $data,
                            InvoicerpPanelApi::bearerForCurrentUser(),
                        );
                        InvoicerpPanelApi::haltUnlessSuccessful($response);
                    }),
                DeleteAction::make()
                    ->using(function (Model $record): bool {
                        $response = InvoicerpPanelApi::client()->deleteJson(
                            '/api/v1/tenants/'.$record->getKey(),
                            InvoicerpPanelApi::bearerForCurrentUser(),
                        );
                        InvoicerpPanelApi::haltUnlessSuccessful($response);

                        return true;
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTenants::route('/'),
        ];
    }
}
