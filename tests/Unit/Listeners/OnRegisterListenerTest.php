<?php

namespace Pdazcom\Referrals\Tests\Unit\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Pdazcom\Referrals\Events\UserReferred;
use Pdazcom\Referrals\Http\Middleware\StoreReferralCode;
use Pdazcom\Referrals\Listeners\OnRegisterListener;
use Pdazcom\Referrals\Tests\TestCase;
use Pdazcom\Referrals\Tests\TestUser;

class OnRegisterListenerTest extends TestCase
{
    public function testDispatchesUserReferredWhenReferralsOnRequest(): void
    {
        Event::fake([UserReferred::class]);

        $user = new TestUser(['name' => 'Alice', 'email' => 'alice@example.com']);
        $referrals = [42 => now()->addHour()->timestamp];

        $request = Request::create('/register', 'POST');
        $request->merge([StoreReferralCode::REFERRALS => $referrals]);

        $listener = new OnRegisterListener($request);
        $listener->handle(new Registered($user));

        Event::assertDispatched(UserReferred::class, function (UserReferred $event) use ($referrals, $user) {
            return $event->referralIDs === array_keys($referrals)
                && $event->user === $user;
        });
    }

    public function testDoesNotDispatchWhenNoReferralsOnRequest(): void
    {
        Event::fake([UserReferred::class]);

        $user = new TestUser(['name' => 'Bob', 'email' => 'bob@example.com']);

        $request = Request::create('/register', 'POST');
        $request->merge([StoreReferralCode::REFERRALS => []]);

        $listener = new OnRegisterListener($request);
        $listener->handle(new Registered($user));

        Event::assertNotDispatched(UserReferred::class);
    }

    public function testDoesNotDispatchWhenReferralsKeyMissing(): void
    {
        Event::fake([UserReferred::class]);

        $user = new TestUser(['name' => 'Carol', 'email' => 'carol@example.com']);

        $request = Request::create('/register', 'POST');

        $listener = new OnRegisterListener($request);
        $listener->handle(new Registered($user));

        Event::assertNotDispatched(UserReferred::class);
    }

    public function testSignupHookIsOffByDefault(): void
    {
        $this->assertFalse((bool) config('referrals.hooks.signup'));
    }
}
