<?php

namespace App\Filament\Resources\ShippingProviders;

use App\Filament\Resources\ShippingProviders\Pages\CreateShippingProvider;
use App\Filament\Resources\ShippingProviders\Pages\EditShippingProvider;
use App\Filament\Resources\ShippingProviders\Pages\ListShippingProviders;
use App\Filament\Resources\ShippingProviders\Schemas\ShippingProviderForm;
use App\Filament\Resources\ShippingProviders\Tables\ShippingProvidersTable;
use App\Models\ShippingProvider;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ShippingProviderResource extends Resource
{
    protected static ?string $model = ShippingProvider::class;

    protected static ?string $navigationLabel = 'Đơn vị vận chuyển';

    protected static ?string $modelLabel = 'đơn vị vận chuyển';

    protected static ?string $pluralModelLabel = 'đơn vị vận chuyển';

    protected static string|UnitEnum|null $navigationGroup = 'Shipping';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    public static function form(Schema $schema): Schema
    {
        return ShippingProviderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShippingProvidersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShippingProviders::route('/'),
            'create' => CreateShippingProvider::route('/create'),
            'edit' => EditShippingProvider::route('/{record}/edit'),
        ];
    }
}
