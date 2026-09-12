<?php

/*
|--------------------------------------------------------------------------
| StatusApp
|--------------------------------------------------------------------------
|
| Đây là nguồn cấu hình tập trung cho toàn bộ trạng thái nghiệp vụ. Database
| chỉ lưu mã ổn định; nhãn, màu sắc và luồng chuyển trạng thái được định nghĩa
| tại đây để Form, Table, Service và Blade không tự khai báo khác nhau.
|
*/

return [
    'order' => [
        'status' => [
            'default' => 'pending',
            'values' => [
                'pending' => [
                    'label' => 'Mới tạo',
                    'color' => 'warning',
                    'badge_classes' => 'bg-red-500 text-white',
                    'progress_class' => 'bg-red-500',
                    'label_classes' => 'text-red-500',
                ],
                'processing' => [
                    'label' => 'Đang xử lý',
                    'color' => 'info',
                    'badge_classes' => 'bg-lime-500 text-white',
                    'progress_class' => 'bg-lime-500',
                    'label_classes' => 'text-lime-500',
                ],
                'completed' => [
                    'label' => 'Hoàn thành',
                    'color' => 'success',
                    'badge_classes' => 'bg-blue-500 text-white',
                    'progress_class' => 'bg-blue-500',
                    'label_classes' => 'text-blue-500',
                ],
                'cancelled' => [
                    'label' => 'Đã hủy',
                    'color' => 'danger',
                    'badge_classes' => 'bg-gray-500 text-white',
                    'progress_class' => 'bg-gray-500',
                    'label_classes' => 'text-gray-500',
                    'show_in_progress' => false,
                ],
            ],
            'transitions' => [
                'pending' => ['processing', 'cancelled'],
                'processing' => ['completed', 'cancelled'],
                'completed' => [],
                'cancelled' => [],
            ],
        ],
        'fulfillment_mode' => [
            'default' => 'single',
            'values' => [
                'single' => ['label' => 'Giao hàng một lần'],
                'customer_stock' => ['label' => 'Lưu kho và xuất nhiều đợt'],
            ],
        ],
        'fulfillment_status' => [
            'default' => 'pending',
            'values' => [
                'pending' => ['label' => 'Chưa sẵn sàng giao', 'color' => 'warning'],
                'ready' => ['label' => 'Lưu kho, sẵn sàng giao', 'color' => 'info'],
                'partially_released' => ['label' => 'Đã xuất một phần', 'color' => 'warning'],
                'fully_released' => ['label' => 'Đã xuất hết', 'color' => 'success'],
            ],
        ],
        'shipping_method' => [
            'default' => 'standard',
            'values' => [
                'standard' => ['label' => 'Giao hàng thông thường'],
                'best_express' => ['label' => 'Best Express'],
            ],
        ],
        'payment_summary' => [
            'values' => [
                0 => ['label' => 'Chưa thanh toán', 'color' => 'warning'],
                1 => ['label' => 'Đã thanh toán', 'color' => 'success'],
            ],
        ],
        'delivery_summary' => [
            'values' => [
                0 => ['label' => 'Chưa giao', 'color' => 'warning'],
                1 => ['label' => 'Đã giao', 'color' => 'success'],
            ],
        ],
        // Kết thúc Order là trạng thái suy ra từ closed_at, không phải orders.status.
        'closure' => [
            'values' => [
                'open' => ['label' => 'Đang mở', 'color' => 'warning'],
                'closed' => ['label' => 'Đã kết thúc', 'color' => 'success'],
            ],
        ],
    ],
    'payment' => [
        'status' => [
            'default' => 'completed',
            'values' => [
                'pending' => ['label' => 'Chờ thanh toán', 'color' => 'warning'],
                'completed' => ['label' => 'Đã thanh toán', 'color' => 'success'],
                'cancelled' => ['label' => 'Đã hủy', 'color' => 'danger'],
            ],
        ],
    ],
    'shipping' => [
        'status' => [
            'default' => 'pending',
            'values' => [
                'pending' => ['label' => 'Chờ giao', 'color' => 'warning'],
                'shipping' => ['label' => 'Đang giao', 'color' => 'info'],
                'delivered' => ['label' => 'Đã giao', 'color' => 'success'],
            ],
        ],
    ],
    'product_sku' => [
        'low_stock_threshold' => 10,
        'status' => [
            'default' => 'active',
            'values' => [
                'active' => ['label' => 'Đang bán', 'color' => 'success'],
                'inactive' => ['label' => 'Ngừng bán', 'color' => 'gray'],
            ],
        ],
        'inventory_level' => [
            'values' => [
                'in_stock' => ['label' => 'Còn hàng', 'color' => 'success'],
                'low_stock' => ['label' => 'Sắp hết', 'color' => 'warning'],
                'out_of_stock' => ['label' => 'Hết hàng', 'color' => 'danger'],
            ],
        ],
    ],
    'managed_file' => [
        'status' => [
            'default' => 'pending',
            'values' => [
                'pending' => ['label' => 'Chờ upload', 'color' => 'warning'],
                'uploading' => ['label' => 'Đang upload', 'color' => 'info'],
                'ready' => ['label' => 'Đã tải lên Drive', 'color' => 'success'],
                'failed' => ['label' => 'Upload thất bại', 'color' => 'danger'],
                'deleted' => ['label' => 'Đã xóa', 'color' => 'gray'],
            ],
        ],
    ],
    'inventory_allocation' => [
        'status' => [
            'default' => 'allocated',
            'values' => [
                'allocated' => ['label' => 'Đã cấp cho Order', 'color' => 'warning'],
                'consumed' => ['label' => 'Đã đưa vào sản xuất', 'color' => 'success'],
                'released' => ['label' => 'Đã hoàn tồn', 'color' => 'gray'],
            ],
        ],
    ],
    'inventory_movement' => [
        'type' => [
            'values' => [
                'opening_balance' => ['label' => 'Số dư đầu kỳ'],
                'order_allocated' => ['label' => 'Cấp tồn cho Order'],
                'order_quantity_increased' => ['label' => 'Tăng số lượng Order'],
                'order_quantity_decreased' => ['label' => 'Giảm số lượng Order'],
                'order_cancelled_restore' => ['label' => 'Hoàn tồn do hủy Order'],
                'manual_increase' => ['label' => 'Điều chỉnh tăng'],
                'manual_decrease' => ['label' => 'Điều chỉnh giảm'],
            ],
        ],
    ],
    'customer_stock' => [
        'lot_status' => [
            'values' => [
                'open' => ['label' => 'Đang lưu kho', 'color' => 'warning'],
                'closed' => ['label' => 'Đã tất toán kho', 'color' => 'success'],
            ],
        ],
        'item_status' => [
            'values' => [
                'unreleased' => ['label' => 'Chưa xuất', 'color' => 'warning'],
                'partially_released' => ['label' => 'Đã xuất một phần', 'color' => 'info'],
                'fully_released' => ['label' => 'Đã xuất hết', 'color' => 'success'],
            ],
        ],
        'release_payment_status' => [
            'values' => [
                'unconfirmed' => ['label' => 'Chưa thu', 'color' => 'warning'],
                'confirmed' => ['label' => 'Đã xác nhận', 'color' => 'success'],
            ],
        ],
    ],
    'activation' => [
        'product' => [
            'values' => [
                0 => ['label' => 'Ngừng kinh doanh', 'color' => 'gray'],
                1 => ['label' => 'Đang kinh doanh', 'color' => 'success'],
            ],
        ],
        'customer' => [
            'values' => [
                0 => ['label' => 'Ngừng hoạt động', 'color' => 'gray'],
                1 => ['label' => 'Đang hoạt động', 'color' => 'success'],
            ],
        ],
    ],
];
