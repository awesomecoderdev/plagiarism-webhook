<?php

use App\Http\Controllers\ErrorController;
use App\Http\Controllers\WebhookController;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Route;

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
    return User::with("subscriptions")->get();
    // return Subscription::get();
    // return User::get();
}); // index route
Route::any("stripe/webhook", [ErrorController::class, "abort"]); // remove route
// webhook testing route
Route::post("/api/stripe/webhook",  [WebhookController::class, "handleWebhook"])->name("client.webhook.stripe"); // stripe listen --forward-to http://localhost:8000/webhook/str