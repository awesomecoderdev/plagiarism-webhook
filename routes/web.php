<?php

use App\Http\Controllers\ErrorController;
use App\Http\Controllers\WebhookController;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [ErrorController::class, "abort"]); // index route
Route::get('/users', function () {
    // $user = User::with("subscriptions")->first();
    $user = User::first();
    // $user->subscriptions->contains("userId", $user->id);
    $subscription = $user->subscriptions()->create([
        // 'id' => md5(time()) . Str::random(10),
        'name' => "Ibrahim",
        'stripe_id' => Str::random(10),
        'stripe_status' => "paid",
        'stripe_price' => "sdfsfsdf",
        'quantity' => 1,
        'trial_ends_at' => time(),
        'ends_at' => null,
    ]);
    // $items = [];
    // foreach ($user->subscriptions as $key => $subscription) {
    //     $items[$subscription->id][] =  $subscription->items()->create([
    //         "subscription_id" => $subscription->id,
    //         'stripe_id' => "item_" . Str::random(10),
    //         'stripe_product' =>  "product_" . Str::random(10),
    //         'stripe_price' => "price_" . Str::random(10),
    //         'quantity' =>  1,
    //     ]);;
    // }

    return [
        "user" => $user,
        // "items" => $items,
        "subscriptions" =>  $user->subscriptions,
        "contains" => $user->subscriptions->contains("userId", "clj07mh0v0000hkwg14781lm7")
    ];
    // return Subscription::get();
    // return User::get();
}); // index route
Route::any("stripe/webhook", [ErrorController::class, "abort"]); // remove route
// webhook testing route
Route::post("api/stripe/webhook",  [WebhookController::class, "handleWebhook"])->name("webhook"); // stripe listen --forward-to http://localhost:8000/webhook/str

// Route::get('payment/{id}', 'PaymentController@show')->name('payment');
// Route::post('webhook', 'WebhookController@handleWebhook')->name('webhook');
