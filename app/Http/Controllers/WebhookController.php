<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookHandled;
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;
use Laravel\Cashier\Payment;
use Laravel\Cashier\Subscription;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;
use Stripe\Customer as StripeCustomer;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    /**
     * Create a new WebhookController instance.
     *
     * @return void
     */
    public function __construct()
    {
        if (config('cashier.webhook.secret')) {
            $this->middleware(VerifyWebhookSignature::class);
        }
    }

    /**
     * Handle a Stripe webhook call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleWebhook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $method = 'handle' . Str::studly(str_replace('.', '_', $payload['type']));
        // Log::channel("stripe")->info("Webhook received" . json_encode($payload, JSON_PRETTY_PRINT));
        // Log::channel("stripe")->info("Webhook received.\n");

        WebhookReceived::dispatch($payload);

        if (method_exists($this, $method)) {
            $this->setMaxNetworkRetries();

            $response = $this->{$method}($payload);

            WebhookHandled::dispatch($payload);

            return $response;
        }

        return $this->missingMethod($payload);
    }

    /**
     * Handle customer subscription created.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSubscriptionCreated(array $payload)
    {
        $user = $this->getUserByStripeId($payload['data']['object']['customer']);
        Log::channel("stripe")->info("Subscription create event.");

        if ($user) {
            $data = $payload['data']['object'];
            Log::channel("stripe")->info("Subscription created for $user->name.");

            if (!$user->subscriptions->contains('stripe_id', $data['id'])) {
                if (isset($data['trial_end'])) {
                    $trialEndsAt = Carbon::createFromTimestamp($data['trial_end']);
                } else {
                    $trialEndsAt = null;
                }

                $firstItem = $data['items']['data'][0];
                $isSinglePrice = count($data['items']['data']) === 1;


                try {
                    $subscription_id = "sub_" . md5(time() . Str::random(10));
                    $subscription = $user->subscriptions()->create([
                        'id' => $subscription_id,
                        'name' => $data['metadata']['name'] ?? $this->newSubscriptionName($payload),
                        'stripe_id' => $data['id'],
                        'stripe_status' => $data['status'],
                        'stripe_price' => $isSinglePrice ? $firstItem['price']['id'] : null,
                        'quantity' => $isSinglePrice && isset($firstItem['quantity']) ? $firstItem['quantity'] : null,
                        'trial_ends_at' => $trialEndsAt,
                        'ends_at' => null,
                    ]);
                    Log::channel("stripe")->info("Subscription created id:$subscription->id.\n");

                    foreach ($data['items']['data'] as $item) {
                        try {
                            $item_id = "item_" . md5(time() . Str::random(10));
                            $subscription->items()->create([
                                'id' => $item_id,
                                'subscription_id' => $subscription->id,
                                'stripe_id' => $item['id'],
                                'stripe_product' => $item['price']['product'],
                                'stripe_price' => $item['price']['id'],
                                'quantity' => $item['quantity'] ?? 1,
                            ]);
                            Log::channel("stripe")->info("Subscription Item created id: $item_id.\n");
                        } catch (\Exception $e) {
                            Log::channel("stripe")->info("Subscription Item creating error {$e->getMessage()} .\n");
                        }
                    }
                } catch (\Exception $e) {
                    Log::channel("stripe")->info("Subscription creating error {$e->getMessage()} .\n");
                }
            }
        }

        return $this->successMethod();
    }

    /**
     * Determines the name that should be used when new subscriptions are created from the Stripe dashboard.
     *
     * @param  array  $payload
     * @return string
     */
    protected function newSubscriptionName(array $payload)
    {
        return 'default';
    }

    /**
     * Handle customer subscription updated.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        Log::channel("stripe")->info("Subscription update request.");

        if ($user = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            $data = $payload['data']['object'];

            try {
                $subscription = $user->subscriptions()->firstOrNew(['stripe_id' => $data['id']]);

                Log::channel("stripe")->info("Subscription:$subscription->id updated for $user->name.\n");

                if (
                    isset($data['status']) &&
                    $data['status'] === StripeSubscription::STATUS_INCOMPLETE_EXPIRED
                ) {
                    $subscription->items()->delete();
                    $subscription->delete();
                    return;
                }

                $subscription->name = $subscription->name ?? $data['metadata']['name'] ?? $this->newSubscriptionName($payload);

                $firstItem = $data['items']['data'][0];
                $isSinglePrice = count($data['items']['data']) === 1;

                // Price...
                $subscription->stripe_price = $isSinglePrice ? $firstItem['price']['id'] : null;

                // Quantity...
                $subscription->quantity = $isSinglePrice && isset($firstItem['quantity']) ? $firstItem['quantity'] : 1;

                // Trial ending date...
                if (isset($data['trial_end'])) {
                    $trialEnd = Carbon::createFromTimestamp($data['trial_end']);

                    if (!$subscription->trial_ends_at || $subscription->trial_ends_at->ne($trialEnd)) {
                        $subscription->trial_ends_at = $trialEnd;
                    }
                }

                // Cancellation date...
                if (isset($data['cancel_at_period_end'])) {
                    if ($data['cancel_at_period_end']) {
                        $subscription->ends_at = $subscription->onTrial()
                            ? $subscription->trial_ends_at
                            : Carbon::createFromTimestamp($data['current_period_end']);
                    } elseif (isset($data['cancel_at'])) {
                        $subscription->ends_at = Carbon::createFromTimestamp($data['cancel_at']);
                    } else {
                        $subscription->ends_at = null;
                    }
                }

                // Status...
                if (isset($data['status'])) {
                    $subscription->stripe_status = $data['status'];
                }

                $subscription->save();

                // Update subscription items...
                if (isset($data['items'])) {
                    $prices = [];

                    foreach ($data['items']['data'] as $item) {
                        $prices[] = $item['price']['id'];
                        // $item_id = "item_" . md5(time() . Str::random(10));

                        $subscription->items()->updateOrCreate([
                            'stripe_id' => $item['id'],
                            'subscription_id' => $subscription->id,
                        ], [
                            // 'id' => $item_id,
                            'stripe_product' => $item['price']['product'],
                            'stripe_price' => $item['price']['id'],
                            'quantity' => $item['quantity'] ?? null,
                        ]);
                    }

                    // Delete items that aren't attached to the subscription anymore...
                    $subscription->items()->whereNotIn('stripe_price', $prices)->delete();
                }
            } catch (\Exception $e) {
                Log::channel("stripe")->info("Subscription updating error {$e->getMessage()} .\n");
            }
        }

        return $this->successMethod();
    }

    /**
     * Handle a canceled customer from a Stripe subscription.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        Log::channel("stripe")->info("Subscription deleted.\n");

        if ($user = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            Log::channel("stripe")->info("Subscription deleted for $user->name.\n");

            $user->subscriptions->filter(function ($subscription) use ($payload) {
                return $subscription->stripe_id === $payload['data']['object']['id'];
            })->each(function ($subscription) {
                $subscription->markAsCanceled();
            });
        }

        return $this->successMethod();
    }

    /**
     * Handle customer updated.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerUpdated(array $payload)
    {
        // if ($user = $this->getUserByStripeId($payload['data']['object']['id'])) {
        //     $user->updateDefaultPaymentMethodFromStripe();
        // }

        return $this->successMethod();
    }

    /**
     * Handle deleted customer.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerDeleted(array $payload)
    {
        if ($user = $this->getUserByStripeId($payload['data']['object']['id'])) {
            $user->subscriptions->each(function (Subscription $subscription) {
                $subscription->skipTrial()->markAsCanceled();
            });

            // $user->forceFill([
            //     'stripe_id' => null,
            //     'trial_ends_at' => null,
            //     'pm_type' => null,
            //     'pm_last_four' => null,
            // ])->save();
        }

        return $this->successMethod();
    }

    /**
     * Handle payment action required for invoice.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleInvoicePaymentActionRequired(array $payload)
    {
        if (is_null($notification = config('cashier.payment_notification'))) {
            return $this->successMethod();
        }

        if ($user = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            if (in_array(Notifiable::class, class_uses_recursive($user))) {
                $payment = new Payment($user->stripe()->paymentIntents->retrieve(
                    $payload['data']['object']['payment_intent']
                ));

                $user->notify(new $notification($payment));
            }
        }

        return $this->successMethod();
    }

    /**
     * Get the customer instance by Stripe ID.
     *
     * @param  string|null  $stripeId
     * @return \Laravel\Cashier\Billable|null
     */
    protected function getUserByStripeId($stripeId)
    {
        $stripeId = $stripeId instanceof StripeCustomer ? $stripeId->id : $stripeId;
        $customer = $stripeId ?  User::where('stripeId', $stripeId)->first() : null;
        return $customer;
    }

    /**
     * Handle successful calls on the controller.
     *
     * @param  array  $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function successMethod($parameters = [])
    {
        return new Response('Webhook Handled', 200);
    }

    /**
     * Handle calls to missing methods on the controller.
     *
     * @param  array  $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function missingMethod($parameters = [])
    {
        return new Response;
    }

    /**
     * Set the number of automatic retries due to an object lock timeout from Stripe.
     *
     * @param  int  $retries
     * @return void
     */
    protected function setMaxNetworkRetries($retries = 3)
    {
        Stripe::setMaxNetworkRetries($retries);
    }
}
