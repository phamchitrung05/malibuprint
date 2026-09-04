<?php

namespace App\Services;

use App\Models\OrderSequence;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class OrderCodeService
{
    /**
     * Tăng sequence của ngày đã chọn và trả về mã ORD-DDMMYY-XXX.
     * Phương thức này phải được gọi bên trong transaction của thao tác tạo Order.
     */
    public function generate(CarbonInterface $date): string
    {
        $sequenceDate = $date->toDateString();

        // Insert-or-ignore tạo dòng ngày mới an toàn khi hai người cùng tạo đơn lần đầu trong ngày.
        DB::table('order_sequences')->insertOrIgnore([
            'sequence_date' => $sequenceDate,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Khóa dòng sequence trong transaction để mỗi người nhận một số khác nhau.
        $sequence = OrderSequence::query()
            ->whereDate('sequence_date', $sequenceDate)
            ->lockForUpdate()
            ->firstOrFail();

        $sequence->increment('last_number');

        return sprintf('ORD-%s-%03d', $date->format('dmy'), $sequence->fresh()->last_number);
    }
}
