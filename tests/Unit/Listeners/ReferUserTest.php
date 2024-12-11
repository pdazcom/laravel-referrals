<?php

namespace Pdazcom\Referrals\Tests\Unit\Listeners;

use Pdazcom\Referrals\Events\UserReferred;
use Pdazcom\Referrals\Listeners\ReferUser;
use Pdazcom\Referrals\Models\ReferralProgram;
use Pdazcom\Referrals\Tests\TestCase;
use Pdazcom\Referrals\Tests\WithLoadMigrations;

class ReferUserTest extends TestCase
{
    use WithLoadMigrations;

    public function testCreatingRelationship()
    {
        $recruitUser = $this->user();
        $referrerUser = $this->user();

        $program = ReferralProgram::create([
            'name' => 'test',
            'title' => 'Test',
            'description' => 'Test description',
            'uri' => 'test',
        ]);

        $refLink = $program->links()->create([
            'user_id' => $recruitUser->id,
        ]);

        $event = new UserReferred([$refLink->id => now()->timestamp], $referrerUser);
        (new ReferUser())->handle($event);

        $relationships = $refLink->relationships;

        $this->assertCount(1, $relationships);
        $this->assertEquals($referrerUser->id, $relationships->first()->user_id);

        (new ReferUser())->handle($event);

        $this->assertEquals(1, $refLink->relationships()->count());
        $this->assertEquals($referrerUser->id, $relationships->first()->user_id);
    }

    public function testCreatingMultiplyRelationship()
    {
        $recruitUser = $this->user();
        $recruitUser2 = $this->user();
        $referrerUser = $this->user();


        $program = ReferralProgram::create([
            'name' => 'test',
            'title' => 'Test',
            'description' => 'Test description',
            'uri' => 'test',
        ]);

        $refLink = $program->links()->create([
            'user_id' => $recruitUser->id,
        ]);

        $refLink2 = $program->links()->create([
            'user_id' => $recruitUser2->id,
        ]);

        $event = new UserReferred([$refLink->id => now()->timestamp], $referrerUser);
        (new ReferUser())->handle($event);

        $relationships = $refLink->relationships;

        $this->assertCount(1, $relationships);
        $this->assertEquals($referrerUser->id, $relationships->first()->user_id);

        $event = new UserReferred([$refLink2->id => now()->timestamp], $referrerUser);
        (new ReferUser())->handle($event);

        $relationships2 = $refLink2->relationships;

        $this->assertCount(1, $relationships2);
        $this->assertEquals($referrerUser->id, $relationships2->first()->user_id);
    }
}
