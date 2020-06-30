<?php
namespace Pdazcom\Referrals\Tests\Unit\Models;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Pdazcom\Referrals\Models\ReferralProgram;
use Pdazcom\Referrals\Tests\TestCase;
use Pdazcom\Referrals\Tests\WithLoadMigrations;

class ReferralLinkTest extends TestCase
{
    use DatabaseTransactions;
    use DatabaseMigrations;
    use WithLoadMigrations;

    public function setUp()
    {
        parent::setup();
        $this->loadMigrations();
        $this->app['config']->set('auth.providers.users.model', User::class);
    }

    public function testReferredUsers()
    {
        $referralUser = User::create();

        $recruitUser1 = User::create();

        User::create();

        $recruitUser2 = User::create();


        $program = ReferralProgram::create([
            'name' => 'example',
            'title' => 'Test',
            'description' => 'Test description',
            'uri' => 'test',
        ]);

        /** @var \Pdazcom\Referrals\Models\ReferralLink $refLink */
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
