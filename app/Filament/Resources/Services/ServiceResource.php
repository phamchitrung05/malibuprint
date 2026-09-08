<?php

namespace App\Filament\Resources\Services;

use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Filament\Resources\Services\Schemas\ServiceForm;
use App\Filament\Resources\Services\Tables\ServicesTable;
use App\Models\Service;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationLabel = 'Dịch vụ';

    protected static ?string $modelLabel = 'dịch vụ';

    protected static ?string $pluralModelLabel = 'dịch vụ';

    // Dịch vụ là một thành phần cấu thành giá bán nên được quản lý trong nhóm Shop.
    protected static string|UnitEnum|null $navigationGroup = 'Shop';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    public static function form(Schema $schema): Schema
    {
        return ServiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServices::route('/'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}
