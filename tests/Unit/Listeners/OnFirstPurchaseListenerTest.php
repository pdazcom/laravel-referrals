<?php

namespace Pdazcom\Referrals\Tests\Unit\Listeners;

use Illuminate\Support\Facades\Event;
use Pdazcom\Referrals\Events\ReferralCase;
use Pdazcom\Referrals\Listeners\OnFirstPurchaseListener;
use Pdazcom\Referrals\Tests\TestCase;
use Pdazcom\Referrals\Tests\TestUser;

class OnFirstPurchaseListenerTest extends TestCase
{
    public function testDispatchesReferralCaseWithUserPropertyAccessor(): void
    {
        Event::fake([ReferralCase::class]);

        $user = new TestUser(['name' => 'Alice', 'email' => 'alice@example.com']);
        $user->id = 1;

        $purchaseEvent = new class ($user) {
            public function __construct(public TestUser $user) {}
        };

        $this->app['config']->set('referrals.hooks.first_purchase.programs', ['welcome-bonus']);
        $this->app['config']->set('referrals.hooks.first_purchase.user_accessor', 'user');
        $this->app['config']->set('referrals.hooks.first_purchase.reward_accessor', null);

        $listener = new OnFirstPurchaseListener();
        $listener->handle($purchaseEvent);

        Event::assertDispatched(ReferralCase::class, function (ReferralCase $event) use ($user, $purchaseEvent) {
            return $event->user === $user
                && $event->programName === ['welcome-bonus']
                && $event->rewardObject === $purchaseEvent;
        });
    }

    public function testDispatchesReferralCaseWithRewardAccessor(): void
    {
        Event::fake([ReferralCase::class]);

        $user = new TestUser(['name' => 'Bob', 'email' => 'bob@example.com']);
        $user->id = 2;
        $order = (object) ['total' => 150];

        $purchaseEvent = new class ($user, $order) {
            public function __construct(
                public TestUser $user,
                public object $order,
            ) {}
        };

        $this->app['config']->set('referrals.hooks.first_purchase.programs', ['first-purchase']);
        $this->app['config']->set('referrals.hooks.first_purchase.user_accessor', 'user');
        $this->app['config']->set('referrals.hooks.first_purchase.reward_accessor', 'order');

        $listener = new OnFirstPurchaseListener();
        $listener->handle($purchaseEvent);

        Event::assertDispatched(ReferralCase::class, function (ReferralCase $event) use ($user, $order) {
            return $event->user === $user
                && $event->rewardObject === $order;
        });
    }

    public function testDoesNotDispatchWhenNoProgramsConfigured(): void
    {
        Event::fake([ReferralCase::class]);

        $user = new TestUser(['name' => 'Carol', 'email' => 'carol@example.com']);
        $user->id = 3;

        $purchaseEvent = new class ($user) {
            public function __construct(public TestUser $user) {}
        };

        $this->app['config']->set('referrals.hooks.first_purchase.programs', []);

        $listener = new OnFirstPurchaseListener();
        $listener->handle($purchaseEvent);

        Event::assertNotDispatched(ReferralCase::class);
    }

    public function testDoesNotDispatchWhenUserAccessorReturnsNonModel(): void
    {
        Event::fake([ReferralCase::class]);

        $purchaseEvent = new class {
            public string $user = 'not-a-model';
        };

        $this->app['config']->set('referrals.hooks.first_purchase.programs', ['welcome-bonus']);
        $this->app['config']->set('referrals.hooks.first_purchase.user_accessor', 'user');

        $listener = new OnFirstPurchaseListener();
        $listener->handle($purchaseEvent);

        Event::assertNotDispatched(ReferralCase::class);
    }

    public function testDispatchesMultipleProgramNames(): void
    {
        Event::fake([ReferralCase::class]);

        $user = new TestUser(['name' => 'Dave', 'email' => 'dave@example.com']);
        $user->id = 4;

        $purchaseEvent = new class ($user) {
            public function __construct(public TestUser $user) {}
        };

        $programs = ['program-a', 'program-b'];
        $this->app['config']->set('referrals.hooks.first_purchase.programs', $programs);
        $this->app['config']->set('referrals.hooks.first_purchase.user_accessor', 'user');

        $listener = new OnFirstPurchaseListener();
        $listener->handle($purchaseEvent);

        Event::assertDispatched(ReferralCase::class, function (ReferralCase $event) use ($programs) {
            return $event->programName === $programs;
        });
    }
}
