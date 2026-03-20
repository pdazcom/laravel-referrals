# Simple Referrals system for Laravel

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Tests][ico-tests]][link-tests]
[![Total Downloads][ico-downloads]][link-downloads]

A simple system of referrals with the ability to assign different programs for different users.

This package was created based on the [lesson](https://blog.damirmiladinov.com/laravel/building-laravel-referral-system.html#.Wc4eA6xJaHo) 
author is Damir Miladinov, with some minor changes, for which I express my gratitude to him.

- [Installation](#installation)
- [Quickstart](#quickstart)
- [Usage](#usage)
- [Bonus](#bonus-content)

## Installation
These steps are verified against a fresh Laravel 11 and Laravel 12 application.

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
## Usage
### Add new referrer event
Then in `Http/Controllers/Auth/RegisterController.php` add event dispatcher:

```
...
use Pdazcom\Referrals\Events\UserReferred;
use Pdazcom\Referrals\Http\Middleware\StoreReferralCode;

...
// overwrite registered function
public function registered(Request $request, $user)
{
    // dispatch user referred event here
    UserReferred::dispatch($request->input(StoreReferralCode::REFERRALS), $user);
}
```

From this point all referral links would be attached new users as referrals to users owners of these links.
### Create referral program
And then you need to create a referral program in database and attach it to users by `referral_program_id` field:

```
    php artisan tinker
    
    Pdazcom\Referrals\Models\ReferralProgram::create(['name'=>'example', 'title' => 'Example Program', 'description' => 'Laravel Referrals made easy thanks to laravel-referrals package based on an article by Damir Miladinov,', 'uri' => 'register']);
```

add association to config `referrals.programs`:
```
    ...
    'example' => \App\ReferralPrograms\ExampleProgram::class,
```
and create the reward class `App\ReferralPrograms\ExampleProgram.php` for referral program:

```
<?php

namespace App\ReferralPrograms;

use Pdazcom\Referrals\Programs\AbstractProgram;

class ExampleProgram extends AbstractProgram {

    const ROYALTY_PERCENT = 30;

    /**
    *   It can be anything that will allow you to calculate the reward.   
    * 
    *   @param $rewardObject
    */
    public function reward(mixed $rewardObject): void
    {
        $this->recruitUser->balance = $this->recruitUser->balance + $rewardObject * (self::ROYALTY_PERCENT/100);
        $this->recruitUser->save();
    }

}
```
create referral link:
```
php artisan tinker

Pdazcom\Referrals\Models\ReferralLink::create(['user_id' => 1, 'referral_program_id' => 1]);
```

and finally dispatch reward event in any place of your code:

```
use Pdazcom\Referrals\Events\ReferralCase;
...

ReferralCase::dispatch('example', $referralUser, $rewardObject);
```

From this point all referrals action you need would be reward recruit users by code logic in your reward classes.

Create many programs and their reward classes. Enjoy!

### Bonus Content

If you want to list all the users for a given Referral Link, simply use

```php
$referralLink->referredUsers()
```

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
