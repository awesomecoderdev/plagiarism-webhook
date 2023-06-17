<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Subscription as CashierSubscription;


class Subscription extends CashierSubscription
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'Subscription';

    /**
     * Disable the timestamps with the model.
     *
     * @var boll
     */
    // public $timestamps = false;


    /**
     * Get the model related to the subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        // return $this->belongsTo(User::class, (new $model)->getForeignKey());
    }

    /**
     * Get the subscription items related to the subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(Item::class, "id");
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
