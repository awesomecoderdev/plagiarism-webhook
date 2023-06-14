<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Billable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'User';

    /**
     * Disable the timestamps with the model.
     *
     * @var boll
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // protected $fillable = [
    //     "id",
    //     "name",
    //     "email",
    //     "avatar",
    //     "publicId",
    //     "street",
    //     "city,",
    //     "region",
    //     "zip,",
    //     "country",
    //     "websites",
    //     "usage",
    //     "usageLimit",
    //     "plan",
    //     "stripeId",
    //     "billingCycleStart",
    //     "settings",
    // ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    // protected $hidden = [
    //     'password',
    //     'remember_token',
    // ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    // protected $casts = [
    //     'email_verified_at' => 'datetime',
    //     'password' => 'hashed',
    // ];

    /**
     * Get all of the subscriptions for the Stripe model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, "userId")->orderBy('created_at', 'desc');
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
