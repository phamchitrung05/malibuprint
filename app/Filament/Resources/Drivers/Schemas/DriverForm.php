<?php

namespace App\Filament\Resources\Drivers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class DriverForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Thông tin tài xế')
                ->icon(Heroicon::OutlinedIdentification)
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Họ và tên')
                        ->required()
                        ->maxLength(150),
                    TextInput::make('phone')
                        ->label('Số điện thoại')
                        ->tel()
                        ->required()
                        ->maxLength(30),
                    TextInput::make('license_plate')
                        ->label('Biển số xe')
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
