# Simple Referrals system for Laravel

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Tests][ico-tests]][link-tests]
[![Total Downloads][ico-downloads]][link-downloads]

A simple system of referrals with the ability to assign different programs for different users.

This package was created based on the [lesson](https://blog.damirmiladinov.com/laravel/building-laravel-referral-system.html#.Wc4eA6xJaHo) 
author is Damir Miladinov, with some minor changes, for which I express my gratitude to him.

- [Installation](#installation)
- [Documentation Map](#documentation-map)
- [Configuration Reference](#configuration-reference)
- [Quickstart](#quickstart)
- [How It Works](#how-it-works)
- [Sharing and Entry Flows](#sharing-and-entry-flows)
- [Reward Hooks](#reward-hooks)
- [Manual Integration Flow](#manual-integration-flow)
- [Bonus](#bonus-content)

## Installation
These steps are verified against a fresh Laravel 11, Laravel 12, and Laravel 13 application.

### 1. Install the package

```bash
composer require pdazcom/laravel-referrals
```

Laravel registers the service provider automatically through package discovery.

### 2. Publish the config file

```bash
php artisan vendor:publish --tag=referrals-config
```

This creates `config/referrals.php`, where you register your referral program classes.

If you prefer the package command, you can run:

```bash
php artisan referrals:install --config
```

### 3. Run the migrations

```bash
php artisan migrate
```

The package loads its migrations automatically for the default setup, so you do not need to publish them unless you want to customize the migration files before running them.

If you need to customize the migrations, publish them first:

```bash
php artisan vendor:publish --tag=referrals-migrations
```

Or use the package command:

```bash
php artisan referrals:install --migrations
```

### 4. Register the middleware

In Laravel 11 and 12, append the middleware to the `web` stack in `bootstrap/app.php`:

```php
use Illuminate\Foundation\Configuration\Middleware;

->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [
        \Pdazcom\Referrals\Http\Middleware\StoreReferralCode::class,
    ]);
})
```

This middleware stores referral links in cookies so they can be attached when the user signs up.

It accepts both legacy UUID links and human-friendly referral codes in the same `?ref=` query parameter. For the recommended sharing patterns, see [Sharing and Entry Flows](#sharing-and-entry-flows).

### 5. Add the trait to your user model

Add `Pdazcom\Referrals\Traits\ReferralsMember` to `app/Models/User.php`:

```php
use Pdazcom\Referrals\Traits\ReferralsMember;

class User extends Authenticatable
{
    use HasFactory, Notifiable, ReferralsMember;
}
```

### Upgrade notes for older docs

- If you are upgrading from older README instructions, do not edit `app/Http/Kernel.php` in Laravel 11 or 12. Middleware registration moved to `bootstrap/app.php`.
- You only need to publish the package migrations if you want to edit them before running `php artisan migrate`.

> #### Note
> Starting from v2.0, several referral programs can be applied to the same user.
> They are stored in cookies as a JSON object, and the request instance exposes them
> in the `_referrals` property:
>
> ```
> [
>    'ref_id_1' => 'expires_timestamp',
>    'ref_id_2' => 'expires_timestamp',
>    ...
>    'ref_id_n' => 'expires_timestamp'
> ]
> ```
>
> `ref_id_n` is the referral link ID, and `expires_timestamp` is the cookie
> expiration timestamp. Expired links are deleted automatically.

Next: continue with the [quickstart](#quickstart) to create your first referral program and verify the reward flow.

## Documentation Map

Use the shortest guide that matches the task you are working on:

| Document | Use it when you need to |
| --- | --- |
| [README.md](README.md) | Install the package, understand the core flow, or verify a first integration |
| [docs/README.md](docs/README.md) | Browse the docs by topic instead of searching the repo manually |
| [docs/sharing-and-entry-flows.md](docs/sharing-and-entry-flows.md) | Decide between share links, human-friendly codes, and manual code entry |
| [docs/order-subscription-integration.md](docs/order-subscription-integration.md) | Reward referrers from order or subscription completion events |
| [docs/fixed-reward-program.md](docs/fixed-reward-program.md) | Use or adapt the built-in fixed reward program |
| [CONTRIBUTING.md](CONTRIBUTING.md) | Set up a local checkout, run tests, and open a pull request |
| [docs/releases](docs/releases) | Review package changes by version |
| [docs/research](docs/research) | Read exploratory notes and design directions that are not source-of-truth setup docs |

## Configuration Reference

The package configuration file is `config/referrals.php`:

```php
return [
    'programs' => [
        'example' => \Pdazcom\Referrals\Programs\ExampleProgram::class,
    ],
    'fixed_reward_amount' => 10,
    'cookie_name' => 'ref',
    'code_generator' => \Pdazcom\Referrals\Generators\RandomStringCodeGenerator::class,
    'code_length' => 8,
    'code_generation_max_attempts' => 10,
    'prevent_duplicate_rewards' => false,
    'prevent_self_referral' => false,
    'hooks' => [
        'signup' => false,
        'first_purchase' => [
            'enabled'         => false,
            'event'           => null,
            'programs'        => [],
            'user_accessor'   => 'user',
            'reward_accessor' => null,
        ],
    ],
];
```

### Keys

| Key | Default | Required | Behavior |
| --- | --- | --- | --- |
| `programs` | `['example' => \Pdazcom\Referrals\Programs\ExampleProgram::class]` | Yes (for reward execution) | Maps `referral_programs.name` to a reward handler class. `RewardUser` resolves handler classes via `config('referrals.programs.<program_name>')`. Missing mappings are skipped with a warning log. |
| `fixed_reward_amount` | `10` | No | Default flat reward credited by `Pdazcom\Referrals\Programs\FixedRewardProgram` when you do not override its `FIXED_AMOUNT` constant in a subclass. |
| `cookie_name` | `'ref'` | No | Controls the query parameter read by `StoreReferralCode` and the cookie name used to persist active referral link IDs and expiry timestamps. |
| `code_generator` | `\Pdazcom\Referrals\Generators\RandomStringCodeGenerator::class` | No | Container binding used to generate human-friendly `referral_code` values for new `ReferralLink` records. |
| `code_length` | `8` | No | Length passed to the default random string code generator. |
| `code_generation_max_attempts` | `10` | No | Maximum retries when generating a unique `referral_code` before throwing a `ReferralCodeGenerationException`. |
| `prevent_duplicate_rewards` | `false` | No | When enabled, stamps `referral_relationships.rewarded_at` after the first payout and skips later payouts for the same relationship. |
| `prevent_self_referral` | `false` | No | When enabled, `ReferUser` skips relationships where the referred user is also the owner of the referral link. |
| `hooks.signup` | `false` | No | When `true`, automatically dispatches `UserReferred` on `Illuminate\Auth\Events\Registered`. Requires `StoreReferralCode` on the registration route. |
| `hooks.first_purchase` | (see above) | No | When `enabled` is `true` and `event` is set, automatically dispatches `ReferralCase` when the configured event fires. See [Reward Hooks](#reward-hooks) for full options. |

### `programs`

Use `programs` to register each referral program name with the class that should run when `ReferralCase` is dispatched.

- The array key must match the `name` value stored in `referral_programs`.
- The class should implement package program behavior (typically by extending `Pdazcom\Referrals\Programs\AbstractProgram`).
- If no mapping exists for a program name, no reward class is executed for that event.

Example (single program):

```php
'programs' => [
    'welcome-bonus' => \App\ReferralPrograms\WelcomeBonusProgram::class,
],
```

Example (multiple programs):

```php
'programs' => [
    'welcome-bonus' => \App\ReferralPrograms\WelcomeBonusProgram::class,
    'first-purchase' => \App\ReferralPrograms\FirstPurchaseProgram::class,
],
```

### `cookie_name`

`cookie_name` defines the referral parameter your links use and the cookie key the middleware writes to.

- With the default value (`ref`), links look like: `/register?ref=ABC123`.
- If you set `cookie_name` to `referral`, links look like: `/register?referral=ABC123`.
- Existing links must use the same query parameter name as your configured `cookie_name`.

### `code_generator`, `code_length`, and `code_generation_max_attempts`

Every new `ReferralLink` gets two codes:

- `code`: the legacy UUID used by `$link->link`
- `referral_code`: the human-friendly code used by `$link->referral_link`

The default generator is `Pdazcom\Referrals\Generators\RandomStringCodeGenerator`, which:

- generates uppercase alphanumeric codes
- avoids visually ambiguous characters such as `0`, `O`, `I`, and `1`
- uses `code_length` to determine the generated code size
- retries until the code is unique across both the legacy `code` column and the new `referral_code` column

If you want custom code generation rules, bind a class that implements `Pdazcom\Referrals\Contracts\ReferralCodeGeneratorInterface`:

```php
'code_generator' => \App\Referrals\NumericCodeGenerator::class,
```

Use `code_generation_max_attempts` to cap the retry loop if your code space is small or highly constrained.

### `prevent_duplicate_rewards`

Enable this guard when a referred user should only produce one payout for a given referral relationship:

```php
'prevent_duplicate_rewards' => true,
```

Behavior:

- `RewardUser` locks the matching `referral_relationships` row inside a transaction
- the first successful payout stamps `rewarded_at`
- later `ReferralCase` events for the same relationship are skipped

Run the package migrations before enabling this guard so the `rewarded_at` column exists.

### `prevent_self_referral`

Enable this guard if users must not attribute themselves with their own referral links:

```php
'prevent_self_referral' => true,
```

Behavior:

- `ReferUser` compares the referred user ID with the referral link owner ID
- when they match, the relationship is skipped and a log entry is written
- when disabled, self-referrals behave the same as any other referral

## Quickstart

This quickstart gives you a verified path from install to the first successful referral relationship and reward dispatch in a fresh Laravel 11 or 12 app.

### 1. Install the package

```bash
composer require pdazcom/laravel-referrals
php artisan vendor:publish --tag=referrals-config
php artisan migrate
```

You do not need to publish the package migrations for the default setup. The package loads them automatically.

Checkpoint: `php artisan about` should show a `Laravel Referrals` section.

### 2. Register the middleware and trait

In Laravel 11 and 12, append the middleware in `bootstrap/app.php`:

```php
use Illuminate\Foundation\Configuration\Middleware;

->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [
        \Pdazcom\Referrals\Http\Middleware\StoreReferralCode::class,
    ]);
})
```

Then add the trait to your `app/Models/User.php` model:

```php
use Pdazcom\Referrals\Traits\ReferralsMember;

class User extends Authenticatable
{
    use HasFactory, Notifiable, ReferralsMember;
}
```

### 3. Add a reward handler

Create `app/ReferralPrograms/QuickstartProgram.php`:

```php
<?php

namespace App\ReferralPrograms;

use Illuminate\Support\Facades\Log;
use Pdazcom\Referrals\Programs\AbstractProgram;

class QuickstartProgram extends AbstractProgram
{
    public function reward(mixed $rewardObject): void
    {
        Log::info('Quickstart reward triggered', [
            'program' => $this->program->name,
            'recruit_user_id' => $this->recruitUser->id,
            'referral_user_id' => $this->referralUser->id,
            'reward' => $rewardObject,
        ]);
    }
}
```

Register it in `config/referrals.php`:

```php
'programs' => [
    'quickstart' => \App\ReferralPrograms\QuickstartProgram::class,
],
```

### 4. Create a referrer, program, and referral link

```bash
php artisan tinker --execute='use App\Models\User; use Pdazcom\Referrals\Models\ReferralLink; use Pdazcom\Referrals\Models\ReferralProgram; $referrer = User::firstOrCreate(["email" => "referrer@example.com"], ["name" => "Referrer", "password" => "secret123"]); $program = ReferralProgram::firstOrCreate(["name" => "quickstart"], ["title" => "Quickstart Program", "description" => "Quickstart verification", "uri" => "/register", "lifetime_minutes" => 60]); $link = ReferralLink::firstOrCreate(["user_id" => $referrer->id, "referral_program_id" => $program->id]); echo json_encode(["referrer_id" => $referrer->id, "program_id" => $program->id, "link_id" => $link->id, "code" => $link->code, "url" => $link->link], JSON_PRETTY_PRINT);'
```

Checkpoint: the output includes a `link_id`, `code`, and `url`.

### 5. Dispatch the referral event for a new user

```bash
php artisan tinker --execute='use App\Models\User; use Pdazcom\Referrals\Events\UserReferred; use Pdazcom\Referrals\Models\ReferralLink; use Pdazcom\Referrals\Models\ReferralRelationship; $link = ReferralLink::firstOrFail(); $email = "referred+" . now()->timestamp . "@example.com"; $user = User::create(["name" => "Referred User", "email" => $email, "password" => "secret123"]); UserReferred::dispatch([$link->id => now()->addHour()->timestamp], $user); $relationship = ReferralRelationship::where("user_id", $user->id)->first(); echo json_encode(["referred_user_id" => $user->id, "relationship_exists" => (bool) $relationship, "relationship_link_id" => $relationship?->referral_link_id], JSON_PRETTY_PRINT);'
```

Checkpoint: `relationship_exists` is `true`.

### 6. Dispatch the reward event

```bash
php artisan tinker --execute='use App\Models\User; use Pdazcom\Referrals\Events\ReferralCase; $user = User::latest("id")->firstOrFail(); ReferralCase::dispatch("quickstart", $user, ["order_total" => 1500]); echo json_encode(["rewarded_user_id" => $user->id], JSON_PRETTY_PRINT);'
tail -n 5 storage/logs/laravel.log
```

Checkpoint: the log contains `Quickstart reward triggered`.

At this point the package is installed, the referral relationship is stored, and the reward handler is running. To wire this into your real registration flow, dispatch `UserReferred::dispatch($request->input(StoreReferralCode::REFERRALS), $user)` after signup as shown below.

If you want to support code sharing in chat, SMS, or native mobile flows, continue with [Sharing and Entry Flows](#sharing-and-entry-flows).

## How It Works

The package is event-driven. This is the shortest path through the main objects:

```text
ReferralLink shared or entered
        |
        v
StoreReferralCode middleware captures ?ref=... and stores active referral IDs in a cookie
        |
        v
UserReferred event is dispatched after signup or via registerWithCode()
        |
        v
ReferUser creates ReferralRelationship rows
        |
        v
ReferralCase event is dispatched when a qualifying conversion happens
        |
        v
RewardUser resolves the program class and calls reward()
```

Core models:

- `ReferralProgram`: defines the program name, target URI, and attribution lifetime
- `ReferralLink`: belongs to a user and program, stores both the legacy UUID code and human-friendly `referral_code`
- `ReferralRelationship`: records that a user was referred by a specific referral link and whether they have already been rewarded

Integration choices:

- use hooks when your app already fires `Registered` and a purchase or subscription event
- dispatch `UserReferred` and `ReferralCase` manually when you need explicit control
- use `registerWithCode()` when referral attribution happens through a typed code instead of a clicked link

## Sharing and Entry Flows

Use this section to choose the referral flow that matches your product surface. The package now supports both shareable links and code-only attribution without breaking existing link-based integrations.

For a deeper guide with examples and verification steps, see [docs/sharing-and-entry-flows.md](docs/sharing-and-entry-flows.md).

### Choose the right flow

| Flow | Best for | What you share | What the user does |
| --- | --- | --- | --- |
| `referral_link` | Chat, email, SMS, landing pages | Human-friendly link such as `/register?ref=INVITE2024` | Opens the link and signs up normally |
| `referral_code` | Support flows, native mobile apps, offline campaigns | Short code such as `INVITE2024` | Types or pastes the code into your app |
| `link` | Backward-compatible integrations | UUID link such as `/register?ref=550e8400-e29b-41d4-a716-446655440000` | Opens the legacy link |

### Share a human-friendly link

Use `referral_link` when you want a readable URL for public sharing:

```php
$link = ReferralLink::create([
    'user_id' => $user->id,
    'referral_program_id' => $program->id,
]);

$shareUrl = $link->referral_link;
$shareCode = $link->referral_code;
```

This is the recommended default for web, email, SMS, and messaging apps because the same code can also be shown separately for manual entry.

### Accept manual code entry

Use `registerWithCode()` when the referred user enters a code directly instead of visiting a link. The method accepts either the human-friendly `referral_code` or the legacy UUID `code`.

```php
use Illuminate\Http\Request;

public function store(Request $request)
{
    $data = $request->validate([
        'name' => ['required', 'string'],
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
        'referral_code' => ['nullable', 'string'],
    ]);

    $user = User::create($data);

    if (!empty($data['referral_code'])) {
        $user->registerWithCode($data['referral_code']);
    }

    return redirect('/dashboard');
}
```

`registerWithCode()` returns `true` when the code resolves to a referral link and `false` when the code is unknown, so you can decide whether to show validation feedback or continue without attribution.

### Keep legacy links if you already use them

The original `link` attribute still returns a URL with the UUID-based `code`:

```php
$legacyUrl = $link->link;
```

This keeps older integrations working. New user-facing sharing surfaces should prefer `referral_link` and `referral_code`.

### Verify attribution

1. Create a referral link and note both `$link->referral_link` and `$link->referral_code`.
2. Visit the share URL and confirm the middleware redirects to a clean URL and stores the referral cookie.
3. Complete signup and confirm a `referral_relationships` row exists for the new user.
4. Repeat the same attribution using `$user->registerWithCode($link->referral_code)` and confirm you get the same relationship result.

## Reward Hooks

Reward hooks let you trigger referral events automatically in response to standard application events, without adding manual event dispatch to every controller or service. All hooks are **opt-in** and disabled by default.

### Signup hook

The signup hook listens to `Illuminate\Auth\Events\Registered` and automatically dispatches `UserReferred` for any referral link stored in the current request by the `StoreReferralCode` middleware.

**Requirements:**
- `StoreReferralCode` must be active on your registration route.
- Your registration flow must fire `Illuminate\Auth\Events\Registered` (Laravel's built-in `RegisteredController` and Fortify/Breeze do this automatically).

Enable in `config/referrals.php`:

```php
'hooks' => [
    'signup' => true,
],
```

When enabled, you no longer need to manually dispatch `UserReferred` in your registration controller. The hook handles it automatically as long as the referral cookie is present on the request.

### First-purchase hook

The first-purchase hook listens to a **configurable application event** and dispatches `ReferralCase` for the configured programs. This is useful when you want to reward the referrer when a referred user makes their first purchase.

Enable and configure in `config/referrals.php`:

```php
'hooks' => [
    'first_purchase' => [
        'enabled'         => true,
        'event'           => \App\Events\OrderCreated::class,
        'programs'        => ['welcome-bonus', 'first-purchase'],
        'user_accessor'   => 'user',
        'reward_accessor' => 'order',
    ],
],
```

**Options:**

| Key | Default | Description |
| --- | --- | --- |
| `enabled` | `false` | Set to `true` to activate the hook. |
| `event` | `null` | Fully-qualified class name of the event to listen for. Must be set when `enabled` is `true`. |
| `programs` | `[]` | Array of referral program names to reward. Must match `name` values in `referral_programs` table. |
| `user_accessor` | `'user'` | Property or zero-argument method name on the event that returns the referred Eloquent user model. |
| `reward_accessor` | `null` | Property or zero-argument method name on the event to use as the `$rewardObject` passed to `ReferralCase`. When `null`, the event object itself is passed. |

**Example event:**

```php
namespace App\Events;

class OrderCreated
{
    public function __construct(
        public \App\Models\User $user,
        public \App\Models\Order $order,
    ) {}
}
```

With the config above, `ReferralCase::dispatch(['welcome-bonus', 'first-purchase'], $event->user, $event->order)` is dispatched automatically whenever `OrderCreated` fires.

> **Note:** The hook dispatches `ReferralCase` every time the configured event fires. If you want to reward only on the first purchase, add a guard inside your program's `reward()` method (for example, check whether a reward has already been recorded for this user).

### Backward compatibility

Enabling hooks does not change any existing behavior. Existing manual dispatches of `UserReferred` and `ReferralCase` continue to work. You can keep manual dispatches alongside hooks without double-rewarding as long as you are not dispatching the same event twice for the same user action.

## Manual Integration Flow

Use this section when you do not want to enable hooks and prefer to dispatch package events yourself.

### 1. Dispatch `UserReferred` after signup

```php
use Illuminate\Http\Request;
use Pdazcom\Referrals\Events\UserReferred;
use Pdazcom\Referrals\Http\Middleware\StoreReferralCode;

public function registered(Request $request, $user): void
{
    UserReferred::dispatch(
        $request->input(StoreReferralCode::REFERRALS, []),
        $user,
    );
}
```

This consumes the referral IDs that `StoreReferralCode` placed on the request and creates `ReferralRelationship` records through the `ReferUser` listener.

If you collect a typed referral code instead of relying on the middleware cookie, call:

```php
$user->registerWithCode($request->string('referral_code')->toString());
```

### 2. Create the referral program record

```bash
php artisan tinker
```

```php
Pdazcom\Referrals\Models\ReferralProgram::create([
    'name' => 'example',
    'title' => 'Example Program',
    'description' => 'Example percentage-based reward program.',
    'uri' => '/register',
    'lifetime_minutes' => 60,
]);
```

Then map the program name to your reward handler in `config/referrals.php`:

```php
'programs' => [
    'example' => \App\ReferralPrograms\ExampleProgram::class,
],
```

### 3. Implement the reward class

```php
<?php

namespace App\ReferralPrograms;

use Pdazcom\Referrals\Programs\AbstractProgram;

class ExampleProgram extends AbstractProgram
{
    private const ROYALTY_PERCENT = 30;

    public function reward(mixed $rewardObject): void
    {
        $this->recruitUser->balance += $rewardObject * (self::ROYALTY_PERCENT / 100);
        $this->recruitUser->save();
    }
}
```

### 4. Create a referral link for the recruiting user

```php
Pdazcom\Referrals\Models\ReferralLink::create([
    'user_id' => 1,
    'referral_program_id' => 1,
]);
```

The created model exposes:

- `$link->referral_link` for human-friendly share URLs
- `$link->referral_code` for manual entry surfaces
- `$link->link` for legacy UUID-based URLs

### 5. Dispatch `ReferralCase` when the conversion happens

```php
use Pdazcom\Referrals\Events\ReferralCase;

ReferralCase::dispatch('example', $referralUser, $rewardObject);
```

`RewardUser` will:

- resolve the matching `ReferralProgram`
- find the `ReferralLink` that attributed the referred user
- load the configured reward class from `config('referrals.programs.<name>')`
- call `reward($rewardObject)` on that class

### Bonus Content

If you want to list all the users for a given Referral Link, simply use

```php
$referralLink->referredUsers()
```

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for local setup, how to run tests, and the pull request workflow.

## Security

If you discover any security related issues, please email kostya.dn@gmail.com instead of using the issue tracker.

## Credits

- [Konstantin A.][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/pdazcom/laravel-referrals.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-tests]: https://img.shields.io/github/actions/workflow/status/pdazcom/laravel-referrals/tests.yml?branch=master&label=tests&style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/pdazcom/laravel-referrals.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/pdazcom/laravel-referrals
[link-tests]: https://github.com/pdazcom/laravel-referrals/actions/workflows/tests.yml
[link-downloads]: https://packagist.org/packages/pdazcom/laravel-referrals
[link-author]: https://github.com/pdazcom
[link-contributors]: https://github.com/pdazcom/laravel-referrals/graphs/contributors
