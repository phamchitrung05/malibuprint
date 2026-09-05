<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_code')->label('Mã đơn')->searchable()->sortable(),
                TextColumn::make('customer.name')->label('Khách hàng')->searchable(),
                TextColumn::make('order_date')->label('Ngày đặt')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('status')->label('Trạng thái')->badge(),
                TextColumn::make('total_amount')->label('Tổng tiền')->money('VND'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'pending' => 'Chờ xử lý',
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
                Filter::make('order_date')
                    ->label('Ngày đặt')
                    ->form([
                        DatePicker::make('from')->label('Từ ngày'),
                        DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('order_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('order_date', '<=', $date),
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
                        'order' => $record,
                    ])),
                Action::make('updateStatus')
                    ->label('Cập nhật trạng thái')
                    ->tooltip('Cập nhật trạng thái đơn hàng')
                    ->iconButton()
                    ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                    ->color('warning')
                    ->modalHeading('Cập nhật trạng thái đơn hàng')
                    ->modalDescription(fn (Order $record): HtmlString => new HtmlString(sprintf(
                        '<div class="space-y-1 text-left"><p class="text-base font-bold text-gray-950 dark:text-white">Đơn hàng #%s</p><p class="text-sm text-gray-500 dark:text-gray-400">Khách hàng: %s <span class="px-1">•</span> Ngày tạo: %s</p></div>',
                        e($record->order_code),
                        e($record->customer?->name ?? 'Không xác định'),
                        e($record->order_date?->format('d/m/Y H:i') ?? '--/--'),
                    )))
                    ->modalWidth('2xl')
                    ->modalSubmitActionLabel('Cập nhật trạng thái')
                    ->modalCancelActionLabel('Hủy')
                    ->fillForm(fn (Order $record): array => [
                        'stages' => self::completedStages($record),
                        'status_note' => $record->note,
                    ])
                    ->schema([
                        Placeholder::make('status_guidance')
                            ->hiddenLabel()
                            ->content(new HtmlString('<div class="rounded-lg bg-primary-50 px-4 py-3 text-sm text-primary-700 ring-1 ring-primary-600/20 dark:bg-primary-400/10 dark:text-primary-300 dark:ring-primary-400/20">Chọn các giai đoạn đã hoàn thành. Chỉ đánh dấu những giai đoạn đã thực hiện.</div>')),
                        CheckboxList::make('stages')
                            ->label('Cập nhật trạng thái')
                            ->options([
                                'pending' => 'Mới tạo',
                                'processing' => 'Xử lý',
                                'completed' => 'Hoàn thành',
                                'paid' => 'Thanh toán',
                                'delivered' => 'Giao hàng',
                            ])
                            ->descriptions([
                                'pending' => 'Đã tạo đơn hàng, chờ xử lý',
                                'processing' => 'Đang in, đang sản xuất',
                                'completed' => 'Đã sản xuất xong',
                                'paid' => 'Đã thanh toán đơn hàng',
                                'delivered' => 'Đang giao hoặc đã giao hàng',
                            ])
                            ->columns(1)
                            ->required(),
                        Textarea::make('status_note')
                            ->label('Ghi chú')
                            ->placeholder('Nhập ghi chú cập nhật trạng thái...')
                            ->rows(4)
                            ->maxLength(500),
                    ])
                    ->action(function (Order $record, array $data): void {
                        $stages = $data['stages'] ?? [];
                        $status = match (true) {
                            in_array('completed', $stages, true) => 'completed',
                            in_array('processing', $stages, true) => 'processing',
                            default => 'pending',
                        };

                        $record->update([
                            'status' => $status,
                            'is_paid' => in_array('paid', $stages, true),
                            'is_delivered' => in_array('delivered', $stages, true),
                            'note' => $data['status_note'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Đã cập nhật trạng thái đơn hàng')
                            ->success()
                            ->send();
                    }),
                EditAction::make()
                    ->iconButton()
                    ->icon(Heroicon::OutlinedPencilSquare),
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

    /** @return array<string> */
    private static function completedStages(Order $order): array
    {
        $stages = match ($order->status) {
            'completed' => ['pending', 'processing', 'completed'],
            'processing' => ['pending', 'processing'],
            default => ['pending'],
        };

        if ($order->is_paid) {
            $stages[] = 'paid';
        }

        if ($order->is_delivered) {
            $stages[] = 'delivered';
        }

        return $stages;
    }
}
