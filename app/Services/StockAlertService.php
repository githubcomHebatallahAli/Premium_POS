<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\ShipmentProduct;
use App\Notifications\StockAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockAlertService
{
    public function checkAndNotify(): void
    {
        $today = now()->toDateString();
        $oneMonthLater = now()->addMonth()->toDateString();

        // الحالات اللي نتحقق منها
        $alerts = [
            'out_of_stock' => ShipmentProduct::with(['product','variant'])
                ->where('remainingQuantity', 0)->get(),

            'low_stock' => ShipmentProduct::with(['product','variant'])
                ->where('remainingQuantity', '>', 0)
                ->where('remainingQuantity', '<', 5)->get(),

            'expired' => ShipmentProduct::with(['product','variant'])
                ->whereDate('endDate', '<', $today)->get(),

            'near_expiry' => ShipmentProduct::with(['product','variant'])
                ->whereBetween('endDate', [$today, $oneMonthLater])->get(),
        ];

        // كل الإدمنز اللي رولهم = 1
        $admins = Admin::where('role_id', 1)->get();

        foreach ($alerts as $type => $products) {
            foreach ($products as $p) {
                foreach ($admins as $admin) {
                    // مفتاح مميز للإشعار
                    $uniqueKey = [
                        'notifiable_id'   => $admin->id,
                        'notifiable_type' => get_class($admin),
                        'type'            => StockAlert::class,
                    ];

                    // هل فيه إشعار سابق لنفس المنتج ونفس النوع؟
                    $exists = DB::table('notifications')
                        ->where($uniqueKey)
                        ->whereJsonContains('data->product_id', $p->product_id)
                        ->whereJsonContains('data->variant_id', $p->product_variant_id)
                        ->whereJsonContains('data->alert_type', $type)
                        ->orderByDesc('created_at')
                        ->first();

                    $shouldSend = false;

                    if (!$exists) {
                        // مفيش إشعار قبل كده → ابعتي جديد
                        $shouldSend = true;
                    } else {
                        // لو فيه إشعار قديم، نتحقق من عمره
                        $lastSent = Carbon::parse($exists->created_at);
                        if ($lastSent->diffInDays(now()) >= 7) {
                            $shouldSend = true; // مر أسبوع → ابعتي تذكير
                        }
                    }

                    if ($shouldSend) {
                        $admin->notify(new StockAlert($p, $type));
                    }
                }
            }
        }
    }
}
