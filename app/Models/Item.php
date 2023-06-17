<?php

namespace App\Models;

use Illuminate\Support\Str;
use Laravel\Cashier\SubscriptionItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Item extends SubscriptionItem
{
    use HasFactory;

    /**
     * The model's default attributes.
     *
     * @var array
     */
    protected $attributes = [
        'id' => null,
    ];

    /**
     * The model's default attributes.
     *
     */
    function __construct()
    {
        if ($this->attributes['id'] == null) {
            $this->attributes['id'] = "item_" . md5(time() . Str::random(10));
        }
    }


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'Item';

    /**
     * Disable the timestamps with the model.
     *
     * @var boll
     */
    // public $timestamps = false;



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
        $subscription_id = "item_" . md5(time() . Str::random(10));
        return Attribute::make(
            get: fn ($value) => is_null($value) ? $subscription_id : "$value",
            set: fn ($value) => is_null($value) ? $subscription_id : "$value",
        );
    }
}
