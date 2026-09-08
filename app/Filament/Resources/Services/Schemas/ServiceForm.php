<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Thông tin dịch vụ')
                ->description('Giá mới chỉ áp dụng khi dịch vụ được thêm mới vào Order Item.')
                ->icon(Heroicon::OutlinedWrenchScrewdriver)
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ])
                ->schema([
                    TextInput::make('name')
                        ->label('Tên dịch vụ')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('unit_price')
                        ->label('Đơn giá trên một sản phẩm')
                        ->numeric()
                        ->minValue(1)
                        ->suffix('đ')
                        ->required(),
                    TextInput::make('code')
                        ->label('Mã dịch vụ')
                        ->helperText('Mã được khóa vì đang được dùng để nhận diện dịch vụ trong nghiệp vụ Order.')
                        ->disabled()
                        ->dehydrated(false),
                    Select::make('product_type')
                        ->label('Áp dụng cho danh mục')
                        ->options(config('product.product_type'))
                        ->disabled()
                        ->dehydrated(false),
                    Toggle::make('is_active')
                        ->label('Đang hoạt động')
                        ->helperText('Khi tắt, dịch vụ không còn xuất hiện trên Order Item mới.')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
