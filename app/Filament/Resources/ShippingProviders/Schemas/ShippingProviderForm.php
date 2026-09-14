<?php

namespace App\Filament\Resources\ShippingProviders\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ShippingProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Thông tin đơn vị vận chuyển')
                ->icon(Heroicon::OutlinedBuildingOffice2)
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Tên đơn vị')
                        ->required()
                        ->maxLength(150),
                    TextInput::make('phone')
                        ->label('Số điện thoại')
                        ->tel()
                        ->maxLength(30),
                    Toggle::make('is_active')
                        ->label('Đang hoạt động')
                        ->default(true),
                    Textarea::make('note')
                        ->label('Ghi chú')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
