# Simple Referrals system for Laravel

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Total Downloads][ico-downloads]][link-downloads]

A simple system of referrals with the ability to assign different programs for different users.

This package was created based on the [lesson](https://blog.damirmiladinov.com/laravel/building-laravel-referral-system.html#.Wc4eA6xJaHo) 
author is Damir Miladinov, with some minor changes, for which I express my gratitude to him.

- [Installation](#installation)
- [Usage](#usage)
- [Bonus](#bonus-content )

## Installation
### Add dependency
Via Composer

``` bash
$ composer require pdazcom/laravel-referrals
```

Then in config/app.php add service-provider and facade alias:

```
'providers' => [
    ...
    Pdazcom\Referrals\Providers\ReferralsServiceProvider::class,
    ...
];
```

### Configuration
First of all you need to run:
```
php artisan vendor:publish --tag=referrals-config
```
to make `referrals.php` file in your `config` folder.

### Migrations
>**OPTIONAL:** If you want to make changes to the migration files, you also need to run:
>```
>php artisan vendor:publish --tag=referrals-migrations
>```
> Then change new migrations.

Run `php artisan migrate` to make tables in database.

### Middleware
Add middleware to your `web` group in `Http/Kernel.php`:

```
'web' => [
    ...
    \Pdazcom\Referrals\Http\Middleware\StoreReferralCode::class,
],
```

Add `Pdazcom\Referrals\Traits\ReferralsMember` trait to your `Users` model:

```
    class User extends Authenticatable {
        use ReferralsMember;
        ...
    }
```
## Usage
### Add new referrer event
Then in `Http/Controllers/Auth/RegisterController.php` add event dispatcher:

```
...
use Pdazcom\Referrals\Events\UserReferred;

...
// overwrite registered function
public function registered(Request $request)
{
    // dispatch user referred event here
    UserReferred::dispatch(request()->cookie('ref'), $user);
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
    'example' => App\ReferralPrograms\ExampleProgram.php
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
    public function reward($rewardObject)
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
[ico-travis]: https://img.shields.io/travis/pdazcom/laravel-referrals/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/pdazcom/laravel-referrals.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/pdazcom/laravel-referrals.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/pdazcom/laravel-referrals.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/pdazcom/laravel-referrals
[link-travis]: https://travis-ci.org/pdazcom/laravel-referrals
[link-scrutinizer]: https://scrutinizer-ci.com/g/pdazcom/laravel-referrals/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/pdazcom/laravel-referrals
[link-downloads]: https://packagist.org/packages/pdazcom/laravel-referrals
[link-author]: https://github.com/pdazcom
[link-contributors]: ../../contributors
