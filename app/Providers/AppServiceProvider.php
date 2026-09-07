<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductSku;
use App\Models\Shipping;
use App\Observers\OrderObserver;
use App\Observers\PaymentObserver;
use App\Observers\ProductSkuObserver;
use App\Observers\ShippingObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Observer bảo đảm lịch sử vẫn được ghi dù dữ liệu thay đổi từ form hay Livewire action.
        Order::observe(OrderObserver::class);
        Payment::observe(PaymentObserver::class);
        Shipping::observe(ShippingObserver::class);
        ProductSku::observe(ProductSkuObserver::class);
    }
}
