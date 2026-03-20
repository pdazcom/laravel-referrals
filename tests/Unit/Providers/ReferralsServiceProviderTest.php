<?php

namespace Pdazcom\Referrals\Tests\Unit\Providers;

use Pdazcom\Referrals\Events\UserReferred;
use Pdazcom\Referrals\Models\ReferralProgram;
use Pdazcom\Referrals\Tests\TestCase;
use Pdazcom\Referrals\Tests\WithLoadMigrations;

class ReferralsServiceProviderTest extends TestCase
{
    use WithLoadMigrations;

    public function testPackageEventListenersAreRegistered(): void
    {
        $recruitUser = $this->user();
        $referralUser = $this->user();

        $program = ReferralProgram::create([
            'name' => 'test',
            'title' => 'Test',
            'description' => 'Test description',
            'uri' => 'test',
        ]);

        $referralLink = $program->links()->create([
            'user_id' => $recruitUser->id,
        ]);

        UserReferred::dispatch([$referralLink->id => now()->timestamp], $referralUser);

        $this->assertDatabaseHas('referral_relationships', [
            'referral_link_id' => $referralLink->id,
            'user_id' => $referralUser->id,
        ]);
    }
}
