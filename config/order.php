<?php

return [
    'statuses' => [
        'pending' => [
            'label' => 'Đơn mới',
            'color' => 'red-500',
            'icon' => 'heroicon-s-check',
            'badge_classes' => 'bg-red-500 text-white',
            'progress_class' => 'bg-red-500',
            'label_classes' => 'text-red-500',
        ],
        'processing' => [
            'label' => 'Xử lý',
            'color' => 'lime-500',
            'icon' => 'heroicon-s-check',
            'badge_classes' => 'bg-lime-500 text-white',
            'progress_class' => 'bg-lime-500',
            'label_classes' => 'text-lime-500',
        ],
        'completed' => [
            'label' => 'Hoàn thành',
            'color' => 'blue-500',
            'icon' => 'heroicon-s-printer',
            'badge_classes' => 'bg-blue-500 text-white',
            'progress_class' => 'bg-blue-500',
            'label_classes' => 'text-blue-500',
        ],
        // Mốc này chỉ dùng cho cấu hình/trạng thái lịch sử, không thêm vào thanh tiến trình sản xuất.
        'closed' => [
            'label' => 'Đã kết thúc',
            'color' => 'emerald-500',
            'icon' => 'heroicon-s-check-badge',
            'badge_classes' => 'bg-emerald-500 text-white',
            'progress_class' => 'bg-emerald-500',
            'label_classes' => 'text-emerald-500',
            'show_in_progress' => false,
        ],
        'cancelled' => [
            'label' => 'Đã hủy',
            'color' => 'gray-500',
            'icon' => 'heroicon-s-x-mark',
            'badge_classes' => 'bg-gray-500 text-white',
            'progress_class' => 'bg-gray-500',
            'label_classes' => 'text-gray-500',
            'show_in_progress' => false,
        ],
    ],
];
