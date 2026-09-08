<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\FulfillmentMode;
use App\Filament\Resources\CustomerStocks\CustomerStockResource;
use App\Models\Order;
use App\Support\StatusApp;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_code')->label('Mã đơn')->searchable()->sortable(),
                TextColumn::make('customer.name')->label('Khách hàng')->searchable(),
                TextColumn::make('delivery_date')
                    ->label('Ngày dự kiến giao')
                    ->date('d/m/Y')
                    ->description(fn (Order $record): ?string => $record->status === StatusApp::value('order.status', 'completed')
                        ? null
                        : self::deliveryDateDistance($record->delivery_date))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => StatusApp::label('order.status', $state))
                    ->color(fn (string $state): string => StatusApp::color('order.status', $state)),
                TextColumn::make('is_paid')
                    ->label('Thanh toán')
                    ->badge()
                    // Cột tổng hợp dùng cờ trên Order để đồng nhất với tab thanh toán và bộ lọc.
                    ->formatStateUsing(fn (bool $state): string => StatusApp::label('order.payment_summary', $state))
                    ->color(fn (bool $state): string => StatusApp::color('order.payment_summary', $state)),
                TextColumn::make('is_delivered')
                    ->label('Giao hàng')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => StatusApp::label('order.delivery_summary', $state))
                    ->color(fn (bool $state): string => StatusApp::color('order.delivery_summary', $state)),
                TextColumn::make('total_amount')->label('Tổng tiền')->money('VND'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(StatusApp::options('order.status')),
                SelectFilter::make('customer')
                    ->label('Khách hàng')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_delivered')
                    ->label('Giao hàng')
                    ->trueLabel(StatusApp::label('order.delivery_summary', true))
                    ->falseLabel(StatusApp::label('order.delivery_summary', false)),
                TernaryFilter::make('is_paid')
                    ->label('Thanh toán')
                    ->trueLabel(StatusApp::label('order.payment_summary', true))
                    ->falseLabel(StatusApp::label('order.payment_summary', false)),
                Filter::make('delivery_date')
                    ->label('Ngày dự kiến giao')
                    ->form([
                        DatePicker::make('from')->label('Từ ngày'),
                        DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('delivery_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('delivery_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators[] = Indicator::make('Từ '.Carbon::parse($data['from'])->format('d/m/Y'));
                        }

                        if ($data['until'] ?? null) {
                            $indicators[] = Indicator::make('Đến '.Carbon::parse($data['until'])->format('d/m/Y'));
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->icon(Heroicon::OutlinedEye)
                    ->schema([])
                    ->modalHeading('')
                    ->modalWidth('7xl')
                    // Khóa chiều cao cửa sổ modal để nội dung tự cuộn bên trong, không làm trang nền cuộn.
                    ->extraModalWindowAttributes(['class' => 'order-view-modal-window lg'])
                    ->modalContent(fn (Order $record) => view('filament.resources.orders.actions.view-order', [
                        // Nạp dữ liệu của tất cả tab một lần để việc chuyển tab không phát sinh query mới.
                        'order' => $record->loadMissing(['customer', 'items.productSku.product', 'items.services.service', 'payments', 'shipping', 'activities.causer', 'attachments.managedFile']),
                    ])),
                Action::make('updateStatus')
                    ->label('Cập nhật trạng thái')
                    ->tooltip('Cập nhật trạng thái đơn hàng')
                    ->iconButton()
                    ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                    ->color('warning')
                    ->modalHeading('Cập nhật trạng thái đơn hàng')
                    // View riêng hiển thị ngữ cảnh và tiến trình hiện tại phía trên form cập nhật.
                    ->modalContent(fn (Order $record) => view('filament.resources.orders.actions.update-status', [
                        'order' => $record,
                    ]))
                    ->modalWidth('2xl')
                    ->extraModalWindowAttributes(['class' => 'order-view-modal-window s700'])
                     // Component Livewire có nút lưu riêng nên ẩn submit mặc định của Filament action.
                    ->modalSubmitAction(false),
                Action::make('customerStock')
                    ->label('Xem Customer Stock')
                    ->tooltip('Mở tồn kho của Order')
                    ->iconButton()
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->color('info')
                    ->visible(fn (Order $record): bool => $record->fulfillment_mode === FulfillmentMode::CustomerStock
                        && $record->status === StatusApp::value('order.status', 'completed'))
                    ->url(fn (Order $record): string => CustomerStockResource::getUrl('index', [
                        'order_id' => $record->id,
                    ])),
                EditAction::make()
                    ->iconButton()
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->visible(fn (Order $record): bool => ! in_array($record->status, [
                        StatusApp::value('order.status', 'completed'),
                        StatusApp::value('order.status', 'cancelled'),
                    ], true)),
            ])
            // Order còn hoạt động dùng chung một nhóm; ngày giao gần hôm nay nhất đứng trước.
            ->defaultSort(fn (Builder $query): Builder => self::applyOperationalSort($query));
    }

    private static function applyOperationalSort(Builder $query): Builder
    {
        $statusColumn = $query->qualifyColumn('status');
        $deliveryDateColumn = $query->qualifyColumn('delivery_date');
        $activeStatuses = [
            StatusApp::value('order.status', 'pending'),
            StatusApp::value('order.status', 'processing'),
        ];
        $today = today()->toDateString();

        // Cú pháp tính chênh lệch ngày khác nhau theo database; kết quả đều là số ngày tuyệt đối.
        $dateDistanceExpression = match ($query->getConnection()->getDriverName()) {
            'mysql', 'mariadb' => "ABS(DATEDIFF({$deliveryDateColumn}, ?))",
            'pgsql' => "ABS({$deliveryDateColumn}::date - ?::date)",
            'sqlsrv' => "ABS(DATEDIFF(day, {$deliveryDateColumn}, ?))",
            default => "ABS(julianday({$deliveryDateColumn}) - julianday(?))",
        };

        return $query
            ->orderByRaw("CASE {$statusColumn} WHEN ? THEN 0 WHEN ? THEN 0 WHEN ? THEN 1 WHEN ? THEN 2 ELSE 3 END", [
                StatusApp::value('order.status', 'pending'),
                StatusApp::value('order.status', 'processing'),
                StatusApp::value('order.status', 'completed'),
                StatusApp::value('order.status', 'cancelled'),
            ])
            ->orderByRaw("CASE WHEN {$statusColumn} IN (?, ?) AND {$deliveryDateColumn} IS NULL THEN 1 ELSE 0 END", $activeStatuses)
            ->orderByRaw("CASE WHEN {$statusColumn} IN (?, ?) THEN {$dateDistanceExpression} ELSE 0 END", [
                ...$activeStatuses,
                $today,
            ])
            // Nếu cùng ngày dự kiến, đơn mới tạo được ưu tiên trước đơn đang xử lý.
            ->orderByRaw("CASE {$statusColumn} WHEN ? THEN 0 WHEN ? THEN 1 ELSE 0 END", $activeStatuses)
            ->latest($query->qualifyColumn('created_at'));
    }

    private static function deliveryDateDistance(?Carbon $deliveryDate): ?string
    {
        if ($deliveryDate === null) {
            return null;
        }

        $days = (int) today()->diffInDays($deliveryDate->copy()->startOfDay(), false);

        return match (true) {
            $days === 0 => 'Hôm nay',
            $days > 0 => "Còn {$days} ngày",
            default => 'Trễ '.abs($days).' ngày',
        };
    }
}
