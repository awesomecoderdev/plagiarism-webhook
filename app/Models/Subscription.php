<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Cashier\Subscription as CashierSubscription;


class Subscription extends CashierSubscription
{
    use HasFactory, HasUuids;

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
    protected function __construct()
    {
        if ($this->attributes['id'] == null) {
            $this->attributes['id'] = "sub_" . md5(time() . Str::random(10));
        }
    }

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
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // protected $fillable = [
    //     // "id",
    //     "userId",
    //     "name",
    //     "stripe_id",
    //     "stripe_status",
    //     "stripe_price",
    //     "quantity",
    //     "trial_ends_at",
    //     "ends_at",
    // ];

    /**
     * Get the model related to the subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        return $this->belongsTo(User::class, "userID");
    }

    /**
     * Get the subscription items related to the subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(Item::class);
    }


    /**
     * Get the id.
     */
    protected function id(): Attribute
    {
        $subscription_id = "sub_" . md5(time() . Str::random(10));
        return Attribute::make(
            get: fn ($value) => is_null($value) ? $subscription_id : "$value",
            set: fn ($value) => is_null($value) ? $subscription_id : "$value",
        );
    }
}
