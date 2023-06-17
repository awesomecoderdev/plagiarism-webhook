<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\SubscriptionItem;

class Item extends SubscriptionItem
{
    use HasFactory;


    /**
     * Get the subscription that the item belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class, "subscription_id");
    }


    /**
     * Get the id.
     */
    protected function id(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => "$value",
            set: fn (string $value) => "$value",
        );
    }
}
