<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
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
                TextColumn::make('delivery_date')->label('Ngày dự kiến giao')->date('d/m/Y')->sortable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    // Database giữ mã tiếng Anh, bảng chỉ chuyển đổi ở tầng hiển thị.
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Mới tạo',
                        'processing' => 'Đang xử lý',
                        'completed' => 'Hoàn thành',
                        'cancelled' => 'Đã hủy',
                        default => 'Không xác định',
                    })
                    ->color(fn (Order $record): string => match ($record->status) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('is_paid')
                    ->label('Thanh toán')
                    ->badge()
                    // Cột tổng hợp dùng cờ trên Order để đồng nhất với tab thanh toán và bộ lọc.
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Đã thanh toán' : 'Chưa thanh toán')
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
                TextColumn::make('is_delivered')
                    ->label('Giao hàng')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Đã giao' : 'Chưa giao')
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
                TextColumn::make('total_amount')->label('Tổng tiền')->money('VND'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'pending' => 'Mới tạo',
                        'processing' => 'Đang xử lý',
                        'completed' => 'Hoàn thành',
                        'cancelled' => 'Đã hủy',
                    ]),
                SelectFilter::make('customer')
                    ->label('Khách hàng')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_delivered')
                    ->label('Giao hàng')
                    ->trueLabel('Đã giao')
                    ->falseLabel('Chưa giao'),
                TernaryFilter::make('is_paid')
                    ->label('Thanh toán')
                    ->trueLabel('Đã thanh toán')
                    ->falseLabel('Chưa thanh toán'),
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
                    ->modalContent(fn (Order $record) => view('filament.resources.orders.actions.view-order', [
                        // Nạp dữ liệu của tất cả tab một lần để việc chuyển tab không phát sinh query mới.
                        'order' => $record->loadMissing(['customer', 'items.productSku.product', 'payments', 'shipping', 'activities.causer', 'attachments.managedFile']),
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
                    // Component Livewire có nút lưu riêng nên ẩn submit mặc định của Filament action.
                    ->modalSubmitAction(false),
                Action::make('manageAttachments')
                    // Action độc lập với Edit để Order đã khóa vẫn có thể xem và bổ sung tài liệu.
                    ->label('Quản lý tệp')
                    ->tooltip('Quản lý tệp đính kèm')
                    ->iconButton()
                    ->icon(Heroicon::OutlinedPaperClip)
                    ->color('info')
                    ->modalHeading('Quản lý tệp đính kèm')
                    ->modalContent(fn (Order $record) => view('filament.resources.orders.actions.manage-attachments', [
                        'order' => $record,
                    ]))
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false),
                EditAction::make()
                    ->iconButton()
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->visible(fn (Order $record): bool => ! in_array($record->status, ['completed', 'cancelled'], true)),
                DeleteAction::make()
                    ->iconButton()
                    ->icon(Heroicon::OutlinedTrash),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
