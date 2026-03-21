# Fixed Reward Program

Use this guide when you want to award a flat credit amount to the referral link owner every time a referred user completes a qualifying action — for example, placing their first order or activating a subscription.

Unlike `ExampleProgram`, which derives the reward from the event's monetary value (e.g. 30% of order total), `FixedRewardProgram` always pays the same configured amount regardless of what triggered the event.

## How it works

`FixedRewardProgram` reads `config('referrals.fixed_reward_amount')` (default: `10`) and adds that value to the recruit user's `balance` property.

```php
// src/Programs/FixedRewardProgram.php (simplified)
public function reward(mixed $rewardObject): void
{
    $amount = config('referrals.fixed_reward_amount', static::FIXED_AMOUNT);

    $this->recruitUser->balance = $this->recruitUser->balance + $amount;
    $this->recruitUser->save();
}
```

`$rewardObject` is intentionally ignored — the payout is always the same fixed amount.

## Step 1 — Register the program

Add the program to `config/referrals.php`:

```php
'programs' => [
    'fixed-bonus' => \Pdazcom\Referrals\Programs\FixedRewardProgram::class,
],

'fixed_reward_amount' => 10,
```

## Step 2 — Create the program row in the database

The `programs` config maps names to classes. You also need a matching row in the `referral_programs` table:

```php
use Pdazcom\Referrals\Models\ReferralProgram;

ReferralProgram::create([
    'name'             => 'fixed-bonus',
    'title'            => 'Fixed Referral Bonus',
    'description'      => 'Earn $10 for every referred user who completes their first order.',
    'uri'              => '/register',
    'lifetime_minutes' => 10080, // 7 days
]);
```

## Step 3 — Wire it to the first-purchase hook

Enable the built-in `first_purchase` hook so the program triggers automatically when your application fires an order or subscription event:

```php
// config/referrals.php
'hooks' => [
    'first_purchase' => [
        'enabled'         => true,
        'event'           => \App\Events\OrderCompleted::class,
        'programs'        => ['fixed-bonus'],
        'user_accessor'   => 'user',
        'reward_accessor' => null, // FixedRewardProgram ignores rewardObject
    ],
],
```

`user_accessor` must be a public property or method on `OrderCompleted` that returns the purchasing `User` model. Setting `reward_accessor` to `null` passes the entire event as `$rewardObject`, which is fine here since `FixedRewardProgram` ignores it.

## Step 4 — Fire your event

Dispatch the event anywhere in your application:

```php
use App\Events\OrderCompleted;

OrderCompleted::dispatch($order);
```

The hook listener resolves the user from `$event->user`, then dispatches `ReferralCase` for the `fixed-bonus` program. `RewardUser` finds the recruit user via the referral relationship and calls `FixedRewardProgram::reward()`.

## Optional: prevent duplicate rewards

If you only want the recruit user to receive the bonus once per referred user (not once per order), combine this program with the duplicate reward guard:

```php
// config/referrals.php
'prevent_duplicate_rewards' => true,
```

Run the required migration first:

```bash
php artisan migrate
```

After the first reward, the `referral_relationships.rewarded_at` column is stamped and all subsequent `ReferralCase` events for the same pair are silently skipped.

## Optional: prevent self-referral

To block users from referring themselves:

```php
// config/referrals.php
'prevent_self_referral' => true,
```

## Customising the reward amount per program

Create a subclass and override `FIXED_AMOUNT`:

```php
namespace App\Referrals;

use Pdazcom\Referrals\Programs\FixedRewardProgram;

class PremiumBonusProgram extends FixedRewardProgram
{
    const FIXED_AMOUNT = 25;
}
```

Register it alongside the standard one:

```php
'programs' => [
    'fixed-bonus'   => \Pdazcom\Referrals\Programs\FixedRewardProgram::class,
    'premium-bonus' => \App\Referrals\PremiumBonusProgram::class,
],
```
