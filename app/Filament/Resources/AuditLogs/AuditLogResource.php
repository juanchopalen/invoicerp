<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Concerns\QueriesTenantScopedRecords;
use App\Filament\Resources\AuditLogs\Pages\ManageAuditLogs;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogResource extends Resource
{
    use QueriesTenantScopedRecords;

    protected static ?string $model = AuditLog::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Facturación';

    protected static ?string $modelLabel = 'Entrada de auditoría';

    protected static ?string $pluralModelLabel = 'Auditoría';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Textarea::make('action')->disabled(),
                        Textarea::make('payload')->disabled()->columnSpanFull(),
                        Textarea::make('response')->disabled()->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('actor_type')->badge(),
                TextColumn::make('actor_id')->label(__('ID de actor')),
                TextColumn::make('action')->limit(40)->searchable(),
                TextColumn::make('correlation_id')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return static::scopeQueryToCurrentTenant(parent::getEloquentQuery());
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAuditLogs::route('/'),
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
