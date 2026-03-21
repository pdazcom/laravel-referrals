<?php

namespace Pdazcom\Referrals\Listeners;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Pdazcom\Referrals\Events\ReferralCase;

class OnFirstPurchaseListener
{
    public function handle(object $event): void
    {
        $userAccessor = config('referrals.hooks.first_purchase.user_accessor', 'user');
        $programs = config('referrals.hooks.first_purchase.programs', []);
        $rewardAccessor = config('referrals.hooks.first_purchase.reward_accessor');

        if (empty($programs)) {
            Log::warning('OnFirstPurchaseListener: no programs configured in referrals.hooks.first_purchase.programs');
            return;
        }

        $user = $this->resolveUser($event, $userAccessor);

        if (!$user instanceof Model) {
            Log::warning('OnFirstPurchaseListener: could not resolve user from event via accessor "' . $userAccessor . '"');
            return;
        }

        $rewardObject = $rewardAccessor !== null ? $this->resolveValue($event, $rewardAccessor) : $event;

        ReferralCase::dispatch($programs, $user, $rewardObject);
    }

    private function resolveUser(object $event, string $accessor): mixed
    {
        return $this->resolveAccessor($event, $accessor);
    }

    private function resolveValue(object $event, string $accessor): mixed
    {
        return $this->resolveAccessor($event, $accessor);
    }

    private function resolveAccessor(object $event, string $accessor): mixed
    {
        $method = rtrim($accessor, '()');

        if (method_exists($event, $method)) {
            return $event->$method();
        }

        return $event->$accessor ?? null;
    }
}
