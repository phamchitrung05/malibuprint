<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('uuid')
                ->default(fn (): string => (string) Str::uuid()),
            Grid::make([
                'default' => 1,
                'xl' => 12,
            ])
                ->columnSpanFull()
                ->schema([
                    Grid::make(1)
                        ->columnSpan([
                            'default' => 'full',
                            'xl' => 7,
                        ])
                        ->schema([
                            Section::make('Thông tin khách hàng')
                                ->description('Nhập thông tin liên hệ của khách hàng')
                                ->icon(Heroicon::OutlinedUser)
                                ->columns([
                                    'default' => 1,
                                    'md' => 2,
                                ])
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Tên khách hàng')
                                        ->placeholder('Nguyễn Văn An')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('phone')
                                        ->label('Số điện thoại')
                                        ->placeholder('0901 234 567')
                                        ->tel()
                                        ->required()
                                        ->maxLength(30),
                                    Textarea::make('address')
                                        ->label('Địa chỉ')
                                        ->placeholder('Nhập địa chỉ giao hàng của khách hàng')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),
                            Section::make('Ghi chú')
                                ->description('Thông tin bổ sung về khách hàng')
                                ->icon(Heroicon::OutlinedDocumentText)
                                ->schema([
                                    Textarea::make('note')
                                        ->hiddenLabel()
                                        ->placeholder('Nhập ghi chú về khách hàng...')
                                        ->rows(5),
                                ]),
                        ]),
                    Grid::make(1)
                        ->columnSpan([
                            'default' => 'full',
                            'xl' => 5,
                        ])
                        ->schema([
                            Section::make('Trạng thái khách hàng')
                                ->description('Quản lý khả năng hoạt động của khách hàng')
                                ->icon(Heroicon::OutlinedCheckCircle)
                                ->schema([
                                    Toggle::make('is_active')
                                        ->label('Đang hoạt động')
                                        ->helperText('Khách hàng có thể được chọn khi tạo đơn hàng')
                                        ->default(true),
                                ]),
                            Section::make('Lịch sử đơn hàng')
                                ->description('Thông tin đơn hàng gần nhất')
                                ->icon(Heroicon::OutlinedShoppingBag)
                                ->schema([
                                    TextInput::make('last_order')
                                        ->label('Đơn hàng gần nhất')
                                        ->formatStateUsing(fn ($state): string => $state?->format('d/m/Y H:i') ?? 'Chưa có đơn hàng')
                                        ->disabled()
                                        ->dehydrated(false),
                                ]),
                        ]),
                ]),
        ]);
    }
}
