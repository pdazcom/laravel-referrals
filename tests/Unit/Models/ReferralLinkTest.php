<?php
namespace Pdazcom\Referrals\Tests\Unit\Models;

use Pdazcom\Referrals\Models\ReferralLink;
use Pdazcom\Referrals\Models\ReferralProgram;
use Pdazcom\Referrals\Tests\TestCase;
use Pdazcom\Referrals\Tests\WithLoadMigrations;

class ReferralLinkTest extends TestCase
{
    use WithLoadMigrations;

    public function testReferredUsers()
    {
        $referralUser = $this->user();

        $recruitUser1 = $this->user();

        $this->user();

        $recruitUser2 = $this->user();


        $program = ReferralProgram::create([
            'name' => 'example',
            'title' => 'Test',
            'description' => 'Test description',
            'uri' => 'test',
        ]);

        /** @var ReferralLink $refLink */
        $refLink = $program->links()->create([
            'user_id' => $referralUser->id,
        ]);

        $refLink->relationships()->create([
            'user_id' => $recruitUser1->id,
        ]);

        $refLink->relationships()->create([
            'user_id' => $recruitUser2->id,
        ]);

        $this->assertCount(2, $refLink->referredUsers());
        $user_ids = $refLink->referredUsers()->map(function($user) {
            return $user->id;
        });
        $this->assertEquals(2, $user_ids[0]);
        $this->assertEquals(4, $user_ids[1]);
    }
}
