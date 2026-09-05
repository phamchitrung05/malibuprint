<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class OrderForm
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
                            'xl' => 8,
                        ])
                        ->schema([
                            Section::make('Thông tin khách hàng')
                                ->icon(Heroicon::OutlinedUser)
                                ->columns([
                                    'default' => 1,
                                    'md' => 2,
                                ])
                                ->schema([
                                    ToggleButtons::make('customer_mode')
                                        ->label('Loại khách hàng')
                                        ->options([
                                            'new' => 'Khách hàng mới',
                                            'existing' => 'Chọn khách hàng có sẵn',
                                        ])
                                        ->default('existing')
                                        ->inline()
                                        ->grouped()
                                        ->live()
                                        ->visible(fn (string $operation): bool => $operation === 'create')
                                        ->columnSpanFull(),
                                    Select::make('customer_id')
                                        ->label('Khách hàng')
                                        ->relationship('customer', 'name')
                                        ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->name} - {$record->phone}")
                                        ->searchable(['name', 'phone'])
                                        ->preload()
                                        ->live()
                                        ->required(fn (Get $get, string $operation): bool => $operation === 'edit' || $get('customer_mode') === 'existing')
                                        ->visible(fn (Get $get, string $operation): bool => $operation === 'edit' || $get('customer_mode') === 'existing')
                                        ->columnSpanFull(),
                                    Placeholder::make('customer_details')
                                        ->label('Thông tin khách hàng')
                                        ->content(fn (Get $get): HtmlString => self::customerDetails($get('customer_id')))
                                        ->visible(fn (Get $get, string $operation): bool => ($operation === 'edit' || $get('customer_mode') === 'existing') && filled($get('customer_id')))
                                        ->columnSpanFull(),
                                    TextInput::make('customer_name')
                                        ->label('Họ và tên')
                                        ->placeholder('Trần Thị Mai')
                                        ->required(fn (Get $get): bool => $get('customer_mode') === 'new')
                                        ->visible(fn (Get $get, string $operation): bool => $operation === 'create' && $get('customer_mode') === 'new'),
                                    TextInput::make('customer_phone')
                                        ->label('Số điện thoại')
                                        ->placeholder('0901 234 567')
                                        ->tel()
                                        ->maxLength(30)
                                        ->required(fn (Get $get): bool => $get('customer_mode') === 'new')
                                        ->visible(fn (Get $get, string $operation): bool => $operation === 'create' && $get('customer_mode') === 'new'),
                                    TextInput::make('customer_email')
                                        ->label('Email')
                                        ->placeholder('mai.tran@gmail.com')
                                        ->email()
                                        ->visible(fn (Get $get, string $operation): bool => $operation === 'create' && $get('customer_mode') === 'new'),
                                    TextInput::make('customer_company')
                                        ->label('Công ty / Đơn vị')
                                        ->placeholder('Công ty ABC')
                                        ->visible(fn (Get $get, string $operation): bool => $operation === 'create' && $get('customer_mode') === 'new'),
                                    Textarea::make('customer_address')
                                        ->label('Địa chỉ')
                                        ->placeholder('123 Lê Lợi, TP. Quy Nhơn, Bình Định')
                                        ->rows(2)
                                        ->visible(fn (Get $get, string $operation): bool => $operation === 'create' && $get('customer_mode') === 'new')
                                        ->columnSpanFull(),
                                    Textarea::make('customer_note')
                                        ->label('Ghi chú khách hàng')
                                        ->placeholder('Nhập ghi chú về khách hàng...')
                                        ->rows(3)
                                        ->visible(fn (Get $get, string $operation): bool => $operation === 'create' && $get('customer_mode') === 'new')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    Grid::make(1)
                        ->columnSpan([
                            'default' => 'full',
                            'xl' => 4,
                        ])
                        ->schema([
                            Section::make('Thông tin đơn hàng')
                                ->icon(Heroicon::OutlinedCalendarDays)
                                ->columns([
                                    'default' => 1,
                                    'md' => 2,
                                    'xl' => 1,
                                    '2xl' => 2,
                                ])
                                ->schema([
                                    TextInput::make('order_code')
                                        ->label('Mã đơn hàng')
                                        ->default('Tự động sau khi lưu')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpanFull(),
                                    DateTimePicker::make('order_date')
                                        ->label('Ngày tạo')
                                        ->default(now())
                                        ->seconds(false)
                                        ->required()
                                        ->columnSpanFull(),
                                    Select::make('status')
                                        ->label('Trạng thái')
                                        ->options([
                                            'pending' => 'Chờ xử lý',
                                            'processing' => 'Đang xử lý',
                                            'completed' => 'Hoàn thành',
                                            'cancelled' => 'Đã hủy',
                                        ])
                                        ->default('pending')
                                        ->required()
                                        ->columnSpanFull(),
                                    Textarea::make('order_note')
                                        ->label('Ghi chú đơn hàng')
                                        ->placeholder('Nhập ghi chú đơn hàng...')
                                        ->rows(3)
                                        ->dehydrated(false)
                                        ->columnSpanFull(),
                                ]),
                        ]),
                            Section::make('Thêm sản phẩm')
                                ->icon(Heroicon::OutlinedCube)
                                ->description('Chọn sản phẩm, SKU và nhập số lượng')
                                ->columnSpanFull()
                                ->schema([
                                    Repeater::make('items')
                                        ->relationship()
                                        ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => self::normalizeItem($data))
                                        ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => self::normalizeItem($data))
                                        ->hiddenLabel()
                                        ->table([
                                            TableColumn::make('Sản phẩm')
                                                ->markAsRequired()
                                                ->width('25%'),
                                            TableColumn::make('SKU')
                                                ->markAsRequired()
                                                ->width('20%'),
                                            TableColumn::make('Đơn giá')
                                                ->markAsRequired()
                                                ->width('20%'),
                                            TableColumn::make('Số lượng')
                                                ->markAsRequired()
                                                ->width('15%'),
                                            TableColumn::make('Thành tiền')
                                                ->markAsRequired()
                                                ->width('20%'),
                                        ])
                                        ->schema([
                                            Select::make('product_id')
                                                ->label('Sản phẩm')
                                                ->options(fn (): array => Product::query()
                                                    ->where('is_active', true)
                                                    ->orderBy('name')
                                                    ->pluck('name', 'id')
                                                    ->all())
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->live()
                                                // Product chỉ dùng để lọc SKU, order_item vẫn lưu product_sku_id.
                                                ->dehydrated(false)
                                                ->afterStateHydrated(function ($state, Set $set, Get $get): void {
                                                    if (blank($state) && filled($get('product_sku_id'))) {
                                                        $set('product_id', ProductSku::query()->find($get('product_sku_id'))?->product_id);
                                                    }
                                                })
                                                ->afterStateUpdated(function (Set $set, Get $get): void {
                                                    $set('product_sku_id', null);
                                                    $set('unit_price', 0);
                                                    $set('subtotal', 0);
                                                    self::updateTotals($get, $set);
                                                }),
                                            Select::make('product_sku_id')
                                                ->label('SKU')
                                                ->options(fn (Get $get): array => ProductSku::query()
                                                    ->where('product_id', $get('product_id'))
                                                    ->where('status', 'active')
                                                    ->orderBy('sku_code')
                                                    ->pluck('sku_code', 'id')
                                                    ->all())
                                                ->searchable()
                                                ->preload()
                                                ->disabled(fn (Get $get): bool => blank($get('product_id')))
                                                ->required()
                                                ->distinct()
                                                ->live()
                                                ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                                    $price = ProductSku::query()->find($state)?->price ?? 0;

                                                    $set('unit_price', $price);
                                                    $set('subtotal', (float) $price * max(1, (int) $get('quantity')));
                                                    self::updateTotals($get, $set);
                                                }),
                                            TextInput::make('unit_price')
                                                ->label('Đơn giá')
                                                ->numeric()
                                                ->minValue(0)
                                                ->default(0)
                                                ->suffix('đ')
                                                ->required()
                                                ->live(debounce: 300)
                                                ->afterStateUpdated(function (Get $get, Set $set): void {
                                                    $set('subtotal', (float) $get('unit_price') * max(1, (int) $get('quantity')));
                                                    self::updateTotals($get, $set);
                                                }),
                                            TextInput::make('quantity')
                                                ->label('Số lượng')
                                                ->numeric()
                                                ->minValue(1)
                                                ->default(1)
                                                ->required()
                                                ->live(debounce: 300)
                                                ->afterStateUpdated(function (Get $get, Set $set): void {
                                                    $set('subtotal', (float) $get('unit_price') * max(1, (int) $get('quantity')));
                                                    self::updateTotals($get, $set);
                                                }),
                                            TextInput::make('subtotal')
                                                ->label('Thành tiền')
                                                ->numeric()
                                                ->default(0)
                                                ->suffix('đ')
                                                ->readOnly()
                                                ->dehydrated(),
                                        ])
                                        ->defaultItems(1)
                                        ->minItems(1)
                                        ->addActionLabel('Thêm sản phẩm')
                                        ->reorderable(false)
                                        ->live()
                                        ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set)),
                                ]),
                            Section::make('Tổng tiền')
                                ->icon(Heroicon::OutlinedBanknotes)
                                ->columnSpanFull()
                                ->schema([
                                    Hidden::make('subtotal')->default(0),
                                    Hidden::make('total_amount')->default(0),
                                    Placeholder::make('static_subtotal')
                                        ->hiddenLabel()
                                        ->content(fn (Get $get): HtmlString => new HtmlString(sprintf(
                                            '<div class="flex items-center justify-between gap-4 text-sm"><span class="text-gray-500 dark:text-gray-400">Tạm tính</span><span class="font-medium text-gray-950 dark:text-white">%sđ</span></div>',
                                            number_format(self::calculateSubtotal($get('items') ?? []), 0, ',', '.'),
                                        ))),
                                    Grid::make([
                                        'default' => 1,
                                        'sm' => 12,
                                    ])->schema([
                                        Placeholder::make('discount_label')
                                            ->hiddenLabel()
                                            ->content('Giảm giá')
                                            ->extraAttributes(['class' => 'flex h-full items-center text-sm text-gray-500 dark:text-gray-400'])
                                            ->columnSpan([
                                                'default' => 'full',
                                                'sm' => 6,
                                            ]),
                                        TextInput::make('discount')
                                            ->hiddenLabel()
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->suffix('đ')
                                            ->live(debounce: 300)
                                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set))
                                            ->columnSpan([
                                                'default' => 'full',
                                                'sm' => 6,
                                            ]),
                                    ]),
                                    Placeholder::make('static_total')
                                        ->hiddenLabel()
                                        ->content(fn (Get $get): HtmlString => new HtmlString(sprintf(
                                            '<div class="flex items-center justify-between gap-4 border-t border-gray-200 pt-4 dark:border-white/10"><span class="text-base font-bold text-gray-950 dark:text-white">Tổng cộng</span><span class="text-xl font-bold text-primary-600 dark:text-primary-400">%sđ</span></div>',
                                            number_format(max(0, self::calculateSubtotal($get('items') ?? []) - (float) ($get('discount') ?? 0)), 0, ',', '.'),
                                        ))),
                                ]),
                ]),
        ]);
    }

    private static function updateTotals(Get $get, Set $set): void
    {
        $items = $get('items', isAbsolute: true) ?? [];
        $subtotal = self::calculateSubtotal($items);
        $discount = max(0, (float) ($get('discount', isAbsolute: true) ?? 0));
        $shippingFee = max(0, (float) ($get('shipping_fee', isAbsolute: true) ?? 0));
        $vatRate = max(0, (float) ($get('vat_rate', isAbsolute: true) ?? 0));
        $taxableAmount = max(0, $subtotal - $discount);
        $total = $taxableAmount + $shippingFee + ($taxableAmount * $vatRate / 100);

        $set('subtotal', round($subtotal, 2), isAbsolute: true);
        $set('total_amount', round($total, 2), isAbsolute: true);
    }

    /** @param array<int|string, array<string, mixed>> $items */
    private static function calculateSubtotal(array $items): float
    {
        return collect($items)->sum(
            fn (array $item): float => (float) ($item['unit_price'] ?? 0) * max(1, (int) ($item['quantity'] ?? 1)),
        );
    }

    /** @param array<string, mixed> $data */
    private static function normalizeItem(array $data): array
    {
        $data['quantity'] = max(1, (int) ($data['quantity'] ?? 1));
        $data['unit_price'] = max(0, (float) ($data['unit_price'] ?? 0));
        $data['subtotal'] = round($data['quantity'] * $data['unit_price'], 2);

        return $data;
    }

    private static function customerDetails(mixed $customerId): HtmlString
    {
        $customer = Customer::query()->find($customerId);

        if (! $customer) {
            return new HtmlString('<p class="text-sm text-gray-500 dark:text-gray-400">Không tìm thấy thông tin khách hàng.</p>');
        }

        $status = $customer->is_active ? 'Đang hoạt động' : 'Ngừng hoạt động';
        $statusClasses = $customer->is_active
            ? 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400'
            : 'bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400';
        $lastOrder = $customer->last_order?->format('d/m/Y H:i') ?? 'Chưa có đơn hàng';

        return new HtmlString(sprintf(
            '<dl class="grid gap-4 rounded-xl bg-gray-50 p-4 ring-1 ring-gray-950/5 sm:grid-cols-2 dark:bg-white/5 dark:ring-white/10">
                <div><dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Mã khách hàng</dt><dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">%s</dd></div>
                <div><dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Họ và tên</dt><dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">%s</dd></div>
                <div><dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Số điện thoại</dt><dd class="mt-1 text-sm text-gray-950 dark:text-white">%s</dd></div>
                <div><dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Trạng thái</dt><dd class="mt-1"><span class="inline-flex rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset %s">%s</span></dd></div>
                <div><dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Đơn hàng gần nhất</dt><dd class="mt-1 text-sm text-gray-950 dark:text-white">%s</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Địa chỉ</dt><dd class="mt-1 text-sm text-gray-950 dark:text-white">%s</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Ghi chú</dt><dd class="mt-1 text-sm text-gray-950 dark:text-white">%s</dd></div>
            </dl>',
            e($customer->uuid),
            e($customer->name),
            e($customer->phone),
            $statusClasses,
            $status,
            e($lastOrder),
            nl2br(e($customer->address ?: 'Chưa cập nhật')),
            nl2br(e($customer->note ?: 'Không có ghi chú')),
        ));
    }
}
