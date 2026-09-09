<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\FulfillmentMode;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Service;
use App\Support\StatusApp;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
                            Section::make('Tệp đính kèm')
                                ->description(fn (string $operation): string => $operation === 'edit'
                                    ? 'Chọn file bổ sung; file sẽ được liên kết sau khi lưu thay đổi'
                                    : 'File được lưu tạm và tự động chuyển lên Google Drive sau khi lưu đơn')
                                ->icon(Heroicon::OutlinedPaperClip)
                                ->schema([
                                    FileUpload::make('new_attachments')
                                        ->label('File và hình ảnh')
                                        // Form chỉ ghi vào staging local; queue job chịu trách nhiệm với Google Drive.
                                        ->disk(config('attachments.staging_disk'))
                                        ->directory(fn (): string => 'orders/'.now()->format('Y/m'))
                                        ->storeFileNamesIn('new_attachment_names')
                                        ->visibility('private')
                                        ->multiple()
                                        ->live()
                                        ->partiallyRenderComponentsAfterStateUpdated(['staged-attachment-list'])
                                        // FilePond chỉ nhận file; danh sách card đồng nhất với Edit được render bên dưới.
                                        ->previewable(false)
                                        ->panelLayout('compact')
                                        ->extraAttributes(['class' => 'order-attachment-uploader'])
                                        ->maxFiles(10)
                                        ->maxSize(51200)
                                        ->acceptedFileTypes([
                                            'image/jpeg',
                                            'image/png',
                                            'image/webp',
                                            'application/pdf',
                                            'application/zip',
                                        ])
                                        ->helperText(fn (string $operation): string => $operation === 'edit'
                                            ? 'Các file đã chọn sẽ được liên kết khi bấm lưu thay đổi.'
                                            : 'Các file đã chọn được hiển thị tại đây và tự liên kết sau khi tạo Order.'),
                                    View::make('filament.resources.orders.forms.staged-attachments')
                                        ->key('staged-attachment-list')
                                        // Đọc raw Livewire state vì getState() của FileUpload chỉ trả file đã lưu chính thức.
                                        ->viewData(fn ($livewire): array => [
                                            'files' => self::stagedAttachmentData(
                                                data_get($livewire, 'data.new_attachments'),
                                                data_get($livewire, 'data.new_attachment_names'),
                                            ),
                                        ]),
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
                                    Select::make('fulfillment_mode')
                                        ->label('Hình thức giao hàng')
                                        ->options(collect(FulfillmentMode::cases())
                                            ->mapWithKeys(fn (FulfillmentMode $mode): array => [$mode->value => $mode->label()])
                                            ->all())
                                        ->default(StatusApp::default('order.fulfillment_mode'))
                                        ->required()
                                        // Hình thức giao được chốt lúc tạo để không đổi nguồn tồn khi Order đang sản xuất.
                                        ->disabled(fn (string $operation): bool => $operation === 'edit')
                                        ->columnSpanFull(),
                                    DatePicker::make('delivery_date')
                                        ->label('Ngày dự kiến giao')
                                        ->default(now())
                                        // Dữ liệu cũ có thể chưa có ngày dự kiến; chỉ bắt buộc với Order tạo mới.
                                        ->required(fn (string $operation): bool => $operation === 'create')
                                        ->columnSpanFull(),
                                    Textarea::make('note')
                                        ->label('Ghi chú đơn hàng')
                                        ->placeholder('Nhập ghi chú đơn hàng...')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),
                            Section::make('Tệp đã liên kết')
                                ->description('Danh sách file thuộc đơn hàng và trạng thái lưu trữ hiện tại')
                                ->icon(Heroicon::OutlinedPaperClip)
                                ->visible(fn (string $operation): bool => $operation === 'edit')
                                ->schema([
                                    View::make('filament.resources.orders.forms.manage-attachments')
                                        // Order đã tồn tại nên danh sách được đọc từ Attachment thay vì uploader staging.
                                        ->viewData(fn (?Order $record): array => ['order' => $record]),
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
                                        ->width('22%'),
                                    TableColumn::make('SKU')
                                        ->markAsRequired()
                                        ->width('17%'),
                                    TableColumn::make('Đơn giá')
                                        ->markAsRequired()
                                        ->width('16%'),
                                    TableColumn::make('Số lượng')
                                        ->markAsRequired()
                                        ->width('12%'),
                                    TableColumn::make('Dịch vụ in ly')
                                        ->width('16%'),
                                    TableColumn::make('Tiền sản phẩm')
                                        ->markAsRequired()
                                        ->width('17%'),
                                ])
                                ->schema([
                                    Select::make('product_id')
                                        ->label('Sản phẩm')
                                        ->options(fn (Get $get): array => self::availableProductOptions($get))
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
                                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                            $currentSkuId = filled($get('product_sku_id')) ? (int) $get('product_sku_id') : null;
                                            $sku = filled($state)
                                                ? self::availableSkuQuery((int) $state, self::selectedSkuIds($get, $currentSkuId))->first()
                                                : null;
                                            $price = (float) ($sku?->price ?? 0);

                                            // Chọn ngay SKU còn hàng đầu tiên để dòng sản phẩm sẵn sàng nhập số lượng.
                                            $set('include_cup_printing_service', false);
                                            $set('cup_printing_service_unit_price', self::cupPrintingService()?->unit_price ?? 0);
                                            $set('product_sku_id', $sku?->getKey());
                                            $set('unit_price', $price);
                                            $set('subtotal', $price * max(1, (int) $get('quantity')));
                                            self::updateTotals($get, $set);
                                        }),
                                    Select::make('product_sku_id')
                                        ->label('SKU')
                                        ->options(fn (Get $get): array => self::availableSkuOptions($get))
                                        ->disableOptionWhen(fn ($value, $label, Get $get): bool => str_contains((string) $label, '(Hết hàng)')
                                            && (int) $value !== (int) $get('product_sku_id'))
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
                                    Hidden::make('cup_printing_service_unit_price')
                                        ->default(fn (): float => (float) (self::cupPrintingService()?->unit_price ?? 0))
                                        // Giá này chỉ phục vụ tính realtime; manager luôn tự lấy giá snapshot hoặc catalog.
                                        ->dehydrated(false),
                                    Toggle::make('include_cup_printing_service')
                                        ->label('Kèm dịch vụ')
                                        ->default(false)
                                        ->live()
                                        // Toggle chỉ điều khiển bảng snapshot, không phải cột của order_item.
                                        ->dehydrated(false)
                                        ->visible(fn (Get $get): bool => self::supportsCupPrintingService($get))
                                        ->afterStateHydrated(function (?OrderItem $record, Set $set): void {
                                            if ($record === null) {
                                                return;
                                            }

                                            $snapshot = $record->services()
                                                ->whereHas('service', fn (Builder $query): Builder => $query
                                                    ->where('code', Service::CUP_PRINTING_CODE))
                                                ->first();

                                            $set('include_cup_printing_service', $snapshot !== null);
                                            $set(
                                                'cup_printing_service_unit_price',
                                                $snapshot?->unit_price ?? self::cupPrintingService()?->unit_price ?? 0,
                                            );
                                        })
                                        ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set)),
                                    TextInput::make('subtotal')
                                        ->label('Tiền sản phẩm')
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
                                    '<div class="flex items-center justify-between gap-4 text-sm"><span class="text-gray-500 dark:text-gray-400">Tiền sản phẩm</span><span class="font-medium text-gray-950 dark:text-white">%sđ</span></div>',
                                    number_format(self::calculateProductSubtotal($get('items') ?? []), 0, ',', '.'),
                                ))),
                            Placeholder::make('static_service_subtotal')
                                ->hiddenLabel()
                                ->content(fn (Get $get): HtmlString => new HtmlString(sprintf(
                                    '<div class="flex items-center justify-between gap-4 text-sm"><span class="text-gray-500 dark:text-gray-400">%s</span><span class="font-medium text-gray-950 dark:text-white">%sđ</span></div>',
                                    e(self::cupPrintingServiceSummary($get('items') ?? [])),
                                    number_format(self::calculateServiceSubtotal($get('items') ?? []), 0, ',', '.'),
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
                            Grid::make([
                                'default' => 1,
                                'sm' => 12,
                            ])->schema([
                                Placeholder::make('shipping_fee_label')
                                    ->hiddenLabel()
                                    ->content('Phí giao hàng')
                                    ->extraAttributes(['class' => 'flex h-full items-center text-sm text-gray-500 dark:text-gray-400'])
                                    ->columnSpan([
                                        'default' => 'full',
                                        'sm' => 6,
                                    ]),
                                TextInput::make('shipping_fee')
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
                                    number_format(max(
                                        0,
                                        self::calculateSubtotal($get('items') ?? [])
                                            - (float) ($get('discount') ?? 0)
                                            + (float) ($get('shipping_fee') ?? 0),
                                    ), 0, ',', '.'),
                                ))),
                        ]),
                ]),
        ]);
    }

    private static function updateTotals(Get $get, Set $set): void
    {
        // Callback có thể chạy từ field lồng trong Repeater nên dùng state path tuyệt đối của Resource form.
        $items = $get('data.items', isAbsolute: true) ?? [];
        $subtotal = self::calculateSubtotal($items);
        $discount = max(0, (float) ($get('data.discount', isAbsolute: true) ?? 0));
        $shippingFee = max(0, (float) ($get('data.shipping_fee', isAbsolute: true) ?? 0));
        $total = max(0, $subtotal - $discount + $shippingFee);

        $set('data.subtotal', round($subtotal, 2), isAbsolute: true);
        $set('data.total_amount', round($total, 2), isAbsolute: true);
    }

    /** @return array<int, string> */
    private static function availableProductOptions(Get $get): array
    {
        $currentSkuId = filled($get('product_sku_id')) ? (int) $get('product_sku_id') : null;
        $selectedSkuIds = self::selectedSkuIds($get, $currentSkuId);

        return Product::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($currentSkuId, $selectedSkuIds): void {
                $query->whereHas('skus', fn (Builder $query): Builder => self::availableSkuConstraints($query, $selectedSkuIds));

                // Khi sửa Order, giữ Product của SKU hiện tại dù phần tồn khả dụng đã được cấp hết.
                if ($currentSkuId !== null) {
                    $query->orWhereHas('skus', fn (Builder $query): Builder => $query->whereKey($currentSkuId));
                }
            })
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** @return array<int, string> */
    private static function availableSkuOptions(Get $get): array
    {
        if (blank($get('product_id'))) {
            return [];
        }

        $currentSkuId = filled($get('product_sku_id')) ? (int) $get('product_sku_id') : null;
        $selectedSkuIds = self::selectedSkuIds($get, $currentSkuId);
        $query = ProductSku::query()
            ->where('product_id', $get('product_id'))
            ->where(function (Builder $query) use ($currentSkuId): void {
                $query->where('status', StatusApp::value('product_sku.status', 'active'));

                // SKU hiện tại của Order sửa được giữ lại kể cả khi SKU đã ngừng hoạt động.
                if ($currentSkuId !== null) {
                    $query->orWhere('id', $currentSkuId);
                }
            })
            ->when($selectedSkuIds !== [], fn (Builder $query): Builder => $query->whereNotIn('id', $selectedSkuIds));

        return $query
            ->orderBy('sku_code')
            ->get(['id', 'sku_code', 'stock'])
            ->mapWithKeys(fn (ProductSku $sku): array => [
                $sku->id => $sku->stock > 0
                    ? sprintf('%s (Khả dụng thêm: %s)', $sku->sku_code, number_format($sku->stock))
                    : sprintf('%s (Hết hàng)', $sku->sku_code),
            ])
            ->all();
    }

    /** @param list<int> $selectedSkuIds */
    private static function availableSkuQuery(int $productId, array $selectedSkuIds): Builder
    {
        return self::availableSkuConstraints(ProductSku::query(), $selectedSkuIds)
            ->where('product_id', $productId)
            ->orderBy('sku_code');
    }

    /** @param list<int> $selectedSkuIds */
    private static function availableSkuConstraints(Builder $query, array $selectedSkuIds): Builder
    {
        return $query
            ->where('status', StatusApp::value('product_sku.status', 'active'))
            ->where('stock', '>', 0)
            ->when($selectedSkuIds !== [], fn (Builder $query): Builder => $query->whereNotIn('id', $selectedSkuIds));
    }

    /** @return list<int> */
    private static function selectedSkuIds(Get $get, ?int $currentSkuId): array
    {
        // Từ field trong một dòng repeater, đi lên state của form rồi đọc toàn bộ items.
        return collect($get('../../items') ?? [])
            ->pluck('product_sku_id')
            ->filter()
            ->map(fn ($skuId): int => (int) $skuId)
            ->reject(fn (int $skuId): bool => $skuId === $currentSkuId)
            ->unique()
            ->values()
            ->all();
    }

    /** @param array<int|string, array<string, mixed>> $items */
    private static function calculateSubtotal(array $items): float
    {
        return self::calculateProductSubtotal($items) + self::calculateServiceSubtotal($items);
    }

    /** @param array<int|string, array<string, mixed>> $items */
    private static function calculateProductSubtotal(array $items): float
    {
        return collect($items)->sum(
            fn (array $item): float => (float) ($item['unit_price'] ?? 0) * max(1, (int) ($item['quantity'] ?? 1)),
        );
    }

    /** @param array<int|string, array<string, mixed>> $items */
    private static function calculateServiceSubtotal(array $items): float
    {
        $defaultServicePrice = (float) (self::cupPrintingService()?->unit_price ?? 0);

        return collect($items)->sum(fn (array $item): float => ($item['include_cup_printing_service'] ?? false)
            ? (float) ($item['cup_printing_service_unit_price'] ?? $defaultServicePrice)
                * max(1, (int) ($item['quantity'] ?? 1))
            : 0);
    }

    private static function supportsCupPrintingService(Get $get): bool
    {
        if (blank($get('product_id'))) {
            return false;
        }

        $service = self::cupPrintingService();

        return $service !== null
            && Product::query()->whereKey($get('product_id'))->value('product_type') === $service->product_type;
    }

    /** @param array<int|string, array<string, mixed>> $items */
    private static function cupPrintingServiceSummary(array $items): string
    {
        $service = self::cupPrintingService();
        $selectedItems = collect($items)
            ->filter(fn (array $item): bool => (bool) ($item['include_cup_printing_service'] ?? false))
            ->values();
        $selectedCount = $selectedItems->count();
        $quantities = $selectedItems
            ->map(fn (array $item): int => max(1, (int) ($item['quantity'] ?? 1)))
            ->values();

        if ($service === null) {
            return 'Tiền dịch vụ';
        }

        $prices = $selectedItems
            ->map(fn (array $item): float => (float) ($item['cup_printing_service_unit_price'] ?? $service->unit_price))
            ->unique()
            ->values();

        if ($prices->isEmpty()) {
            $prices->push((float) $service->unit_price);
        }

        if ($prices->count() !== 1) {
            return sprintf(
                'Tiền dịch vụ (%s lượt, tổng SL %s)',
                number_format($selectedCount, 0, ',', '.'),
                number_format($quantities->sum(), 0, ',', '.'),
            );
        }

        return sprintf(
            'Tiền dịch vụ (%sđ × (%s))',
            number_format((float) $prices->first(), 0, ',', '.'),
            $quantities->isEmpty() ? '0' : $quantities->implode(' + '),
        );
    }

    private static function cupPrintingService(): ?Service
    {
        return Service::query()
            ->where('code', Service::CUP_PRINTING_CODE)
            ->where('is_active', true)
            ->first();
    }

    /** @param array<string, mixed> $data */
    private static function normalizeItem(array $data): array
    {
        $data['quantity'] = max(1, (int) ($data['quantity'] ?? 1));
        $data['unit_price'] = max(0, (float) ($data['unit_price'] ?? 0));
        $data['subtotal'] = round($data['quantity'] * $data['unit_price'], 2);

        return $data;
    }

    /**
     * Chuẩn hóa state FileUpload thành dữ liệu chỉ dùng để hiển thị trước khi Order tồn tại.
     *
     * @return list<array{key: string, name: string, size: string}>
     */
    private static function stagedAttachmentData(mixed $state, mixed $storedNames): array
    {
        $files = is_array($state) ? $state : array_filter([$state]);
        $names = is_array($storedNames) ? $storedNames : [];

        return collect($files)
            ->map(function (mixed $file, int|string $key) use ($names): ?array {
                if ($file instanceof UploadedFile) {
                    return [
                        'key' => (string) $key,
                        'name' => $file->getClientOriginalName(),
                        'size' => self::formatBytes((int) $file->getSize()),
                    ];
                }

                if (! is_string($file)) {
                    return null;
                }

                $disk = Storage::disk(config('attachments.staging_disk'));

                return [
                    'key' => (string) $key,
                    'name' => basename(str_replace('\\', '/', $names[$file] ?? $file)),
                    'size' => self::formatBytes($disk->exists($file) ? $disk->size($file) : 0),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / 1024 / 1024, 1).' MB';
    }

    private static function customerDetails(mixed $customerId): HtmlString
    {
        $customer = Customer::query()->find($customerId);

        if (! $customer) {
            return new HtmlString('<p class="text-sm text-gray-500 dark:text-gray-400">Không tìm thấy thông tin khách hàng.</p>');
        }

        $status = StatusApp::label('activation.customer', $customer->is_active);
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
