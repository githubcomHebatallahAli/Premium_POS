<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StockAlert extends Notification
{
    use Queueable;

    public function __construct(public $product, public $type) {}

    public function via($notifiable)
    {
        return ['database']; // نخزنها في قاعدة البيانات
    }

    public function toDatabase($notifiable)
    {
        return [
            'product_id'    => $this->product->product_id,
            'product_name'  => $this->product->product->name,
            'variant_id'    => $this->product->product_variant_id,
            'variant_name'  => $this->product->variant?->size 
                                ?? $this->product->variant?->color 
                                ?? null,
            'remaining'     => $this->product->remainingQuantity,
            'expiry_date'   => $this->product->endDate,
            'alert_type'    => $this->type,
            'message'       => $this->getMessage(),
        ];
    }

    private function getMessage()
    {
        $variant = $this->product->variant?->size 
                ?? $this->product->variant?->color 
                ?? '';
        $name = $this->product->product->name . ($variant ? " - $variant" : '');

        return match ($this->type) {
            'out_of_stock' => "المنتج $name خلص من المخزون.",
            'low_stock'    => "المنتج $name قرب يخلص (المتبقي {$this->product->remainingQuantity}).",
            'expired'      => "المنتج $name انتهت صلاحيته.",
            'near_expiry'  => "المنتج $name قربت صلاحيته تنتهي.",
            default        => "تنبيه مخزون للمنتج $name.",
        };
    }
}
