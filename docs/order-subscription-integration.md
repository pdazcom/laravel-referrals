# Order and Subscription Completion Integration

Use this guide when you want to reward the referral link owner automatically as soon as a referred user completes their first order or activates a subscription. It walks through the full journey from attribution (the referral cookie) to conversion (the reward payout).

## How attribution meets conversion

```
User visits  →  StoreReferralCode  →  cookie set
     ↓
User registers  →  UserReferred  →  ReferUser  →  ReferralRelationship created
     ↓
User places order  →  Your event  →  OnFirstPurchaseListener  →  ReferralCase
     ↓
RewardUser  →  FixedRewardProgram (or custom program)  →  balance credited
```

Every step is event-driven and opt-in. The package does not assume your User model structure, your order model, or your event name.

## Step 1 — Register the middleware

Add `StoreReferralCode` to your registration and landing-page routes so the package can capture the `?ref=` query parameter and store it as a cookie:

```php
// routes/web.php or bootstrap/app.php (Laravel 11+)
Route::middleware(['web', \Pdazcom\Referrals\Http\Middleware\StoreReferralCode::class])
    ->group(function () {
        Route::get('/register', [RegisterController::class, 'show']);
        Route::post('/register', [RegisterController::class, 'store']);
    });
```

## Step 2 — Set up the referral program

Seed or migrate the `referral_programs` table with a named program:

```php
use Pdazcom\Referrals\Models\ReferralProgram;

ReferralProgram::firstOrCreate(
    ['name' => 'order-bonus'],
    [
        'title'            => 'Order Referral Bonus',
        'description'      => 'Earn a fixed bonus when a referred user completes their first order.',
        'uri'              => '/register',
        'lifetime_minutes' => 10080, // 7 days
    ]
);
```

Register the program class in `config/referrals.php`:

```php
'programs' => [
    'order-bonus' => \Pdazcom\Referrals\Programs\FixedRewardProgram::class,
],

'fixed_reward_amount' => 10,
```

## Step 3 — Create referral links for your users

Each user who wants to refer others needs a `ReferralLink`:

```php
use Pdazcom\Referrals\Models\ReferralLink;
use Pdazcom\Referrals\Models\ReferralProgram;

$program = ReferralProgram::where('name', 'order-bonus')->firstOrFail();

$link = ReferralLink::firstOrCreate([
    'user_id'             => $user->id,
    'referral_program_id' => $program->id,
]);

// Share with the user
return [
    'share_url'  => $link->referral_link,  // e.g. /register?ref=INVITE2024
    'share_code' => $link->referral_code,  // e.g. INVITE2024
];
```

## Step 4 — Enable the signup hook

When the referred user registers, the signup hook fires `UserReferred` automatically, which creates the `ReferralRelationship`. Enable it in `config/referrals.php`:

```php
'hooks' => [
    'signup' => true,
    // ...
],
```

This requires `StoreReferralCode` to be active on the registration route (Step 1) so the referral cookie data is present on the request at registration time.

Alternatively, call `registerWithCode()` directly if the user enters a code manually:

```php
// In your registration controller or action
$user->registerWithCode($request->input('referral_code'));
```

## Step 5 — Define your order/subscription completed event

Create an event that carries the purchasing user. The package only needs access to the `User` model via a public property or method:

```php
// app/Events/OrderCompleted.php
namespace App\Events;

use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

class OrderCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly User  $user,
        public readonly Order $order,
    ) {}
}
```

For subscriptions, the shape is identical — swap `Order` for your subscription model.

## Step 6 — Enable the first-purchase hook

Wire the hook to your event in `config/referrals.php`:

```php
'hooks' => [
    'signup' => true,

    'first_purchase' => [
        'enabled'         => true,
        'event'           => \App\Events\OrderCompleted::class,
        'programs'        => ['order-bonus'],
        'user_accessor'   => 'user',
        'reward_accessor' => null,
    ],
],
```

`user_accessor` matches the public property on `OrderCompleted` that returns the `User`. `reward_accessor` is `null` because `FixedRewardProgram` ignores the reward object.

## Step 7 — Dispatch the event in your order logic

Fire the event after the order or subscription is confirmed:

```php
// In your order service or controller
use App\Events\OrderCompleted;

DB::transaction(function () use ($user, $order) {
    $order->markAsPaid();
    OrderCompleted::dispatch($user, $order);
});
```

That is all. `OnFirstPurchaseListener` receives the event, resolves `$user` via `user_accessor`, and dispatches `ReferralCase` for the `order-bonus` program. `RewardUser` looks up the referral relationship, finds the recruit user, and calls `FixedRewardProgram::reward()` — crediting their balance.

## Optional: reward only once per referred user

If a referred user places multiple orders and you only want to pay the referral bonus on the first one, enable the duplicate reward guard:

```php
'prevent_duplicate_rewards' => true,
```

Run the migration:

```bash
php artisan migrate
```

After the first payout the `referral_relationships.rewarded_at` column is stamped and all subsequent `ReferralCase` events for the same pair are silently skipped.

## Optional: prevent self-referral

Block a user from using their own link at registration:

```php
'prevent_self_referral' => true,
```

## Full config reference for this integration

```php
// config/referrals.php
return [
    'programs' => [
        'order-bonus' => \Pdazcom\Referrals\Programs\FixedRewardProgram::class,
    ],

    'fixed_reward_amount'      => 10,
    'prevent_duplicate_rewards' => true,
    'prevent_self_referral'     => true,

    'hooks' => [
        'signup' => true,

        'first_purchase' => [
            'enabled'         => true,
            'event'           => \App\Events\OrderCompleted::class,
            'programs'        => ['order-bonus'],
            'user_accessor'   => 'user',
            'reward_accessor' => null,
        ],
    ],
];
```

## Using a percentage-based reward instead

Swap `FixedRewardProgram` for a custom program that reads the order total from `$rewardObject`. Set `reward_accessor` to the property that holds the value:

```php
// config/referrals.php
'programs' => [
    'order-bonus' => \App\Referrals\OrderPercentageProgram::class,
],

'hooks' => [
    'first_purchase' => [
        'enabled'         => true,
        'event'           => \App\Events\OrderCompleted::class,
        'programs'        => ['order-bonus'],
        'user_accessor'   => 'user',
        'reward_accessor' => 'order',   // passes $event->order as $rewardObject
    ],
],
```

```php
// app/Referrals/OrderPercentageProgram.php
namespace App\Referrals;

use App\Models\Order;
use Pdazcom\Referrals\Programs\AbstractProgram;

class OrderPercentageProgram extends AbstractProgram
{
    const COMMISSION_PERCENT = 5;

    public function reward(mixed $rewardObject): void
    {
        /** @var Order $order */
        $order = $rewardObject;

        $commission = $order->total_cents * (self::COMMISSION_PERCENT / 100);

        $this->recruitUser->balance += $commission;
        $this->recruitUser->save();
    }
}
```
