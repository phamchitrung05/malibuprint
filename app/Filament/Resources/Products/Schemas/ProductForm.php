<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Support\StatusApp;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
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
                            Section::make('Thông tin cơ bản')
                                ->description('Nhập các thông tin cơ bản của sản phẩm')
                                ->icon(Heroicon::OutlinedCube)
                                ->columns([
                                    'default' => 1,
                                    'md' => 2,
                                ])
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Tên sản phẩm')
                                        ->placeholder('Card visit cao cấp')
                                        ->required(),
                                    Select::make('product_type')
                                        ->label('Danh mục')
                                        ->options(config('product.product_type'))
                                        ->default(config('product.default_product_type'))
                                        ->selectablePlaceholder(false)
                                        ->searchable()
                                        ->required(),
                                    TextInput::make('unit')
                                        ->label('Đơn vị tính')
                                        ->required(),
                                ]),
                        ]),
                    Grid::make(1)
                        ->columnSpan([
                            'default' => 'full',
                            'xl' => 5,
                        ])
                        ->schema([
                            Section::make('Trạng thái')
                                ->icon(Heroicon::OutlinedCheckCircle)
                                ->schema([
                                    Toggle::make('is_active')
                                        ->label('Đang kinh doanh')
                                        ->helperText('Sản phẩm sẽ hiển thị trên hệ thống')
                                        ->default(true),
                                ]),
                        ]),
                    Section::make('SKU')
                        ->description('Thêm các mã SKU, giá bán và số lượng tồn kho của sản phẩm')
                        ->icon(Heroicon::OutlinedQueueList)
                        ->columnSpanFull()
                        ->schema([
                            Repeater::make('skus')
                                ->relationship()
                                ->hiddenLabel()
                                ->schema([
                                    TextInput::make('sku_code')
                                        ->label('Mã SKU')
                                        ->placeholder('LY-500ML')
                                        ->required()
                                        ->distinct()
                                        ->maxLength(100),
                                    TextInput::make('price')
                                        ->label('Giá')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->suffix('đ')
                                        ->required(),
                                    TextInput::make('stock')
                                        ->label('Tồn đầu kỳ')
                                        ->helperText('SKU đã lưu phải điều chỉnh tồn từ màn hình Tồn kho SKU.')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->required()
                                        ->disabledOn('edit')
                                        ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                                    Select::make('status')
                                        ->label('Trạng thái SKU')
                                        ->options(StatusApp::options('product_sku.status'))
                                        ->default(StatusApp::default('product_sku.status'))
                                        ->required(),
                                ])
                                ->columns([
                                    'default' => 1,
                                    'md' => 4,
                                ])
                                ->defaultItems(1)
                                ->minItems(1)
                                ->addActionLabel('Thêm SKU')
                                ->itemLabel(fn (array $state): ?string => filled($state['sku_code'] ?? null) ? $state['sku_code'] : 'SKU mới')
                                ->collapsible()
                                // SKU đã có movement phải được ngừng bán thay vì xóa để giữ lịch sử tồn kho.
                                ->deletable(fn (string $operation): bool => $operation === 'create')
                                ->reorderable(false),
                        ]),
                ]),
        ]);
    }
}
