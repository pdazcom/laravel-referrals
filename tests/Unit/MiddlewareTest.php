<?php

namespace Pdazcom\Referrals\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Pdazcom\Referrals\Http\Middleware\StoreReferralCode;
use Pdazcom\Referrals\Models\ReferralProgram;
use Pdazcom\Referrals\Tests\TestCase;
use Mockery as m;
use Pdazcom\Referrals\Tests\WithLoadMigrations;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class MiddlewareTest extends TestCase
{
    use WithLoadMigrations;

    public function testSetCookie()
    {
        /** @var ReferralProgram $program */
        $program = ReferralProgram::create([
            'name' => 'test',
            'title' => 'Test',
            'description' => 'Test description',
            'uri' => 'test',
        ]);

        $refLink = $program->links()->create([
            'user_id' => 1,
        ]);

        $this->assertEquals(0, $refLink->clicks);

        $request = Request::create($program->uri, parameters: [
            'ref' => $refLink->code,
        ]);

        $middleware = new StoreReferralCode();
        $response = $middleware->handle($request, function ($request) {
            return response('');
        });

        // is this redirect?
        $this->assertEquals(302, $response->getStatusCode());

        // is cookie was set
        $this->assertCount(1, $response->headers->getCookies());
        $cookie = $response->headers->getCookies()[0];

        // checking name and value of cookie
        $this->assertEquals('ref', $cookie->getName());

        // parse value as json and check it
        $refCookieLinks = json_decode($cookie->getValue(), true);
        $this->assertArrayHasKey($refLink->id, $refCookieLinks);
        $this->assertEquals($cookie->getExpiresTime(), $refCookieLinks[$refLink->id]);

        // check if click was incremented
        $refLink->refresh();
        $this->assertEquals(1, $refLink->clicks);


        // then test multiply referrals
        /** @var ReferralProgram $program2 */
        $program2 = ReferralProgram::create([
            'name' => 'test2',
            'title' => 'Test2',
            'description' => 'Test description of program 2',
            'uri' => 'test2',
            'lifetime_minutes' => 14 * 24 * 60 // set longer lifetime
        ]);

        $refLink2 = $program2->links()->create([
            'user_id' => 1, // same user
        ]);

        $this->assertEquals(0, $refLink2->clicks);

        $request = Request::create($program2->uri, parameters: [
            'ref' => $refLink2->code,
        ], cookies: ['ref' => $cookie->getValue()]);

        $middleware = new StoreReferralCode();
        $response = $middleware->handle($request, function ($request) {
            return response('');
        });

        // is this redirect?
        $this->assertEquals(302, $response->getStatusCode());

        // is cookie was set
        $this->assertCount(1, $response->headers->getCookies());
        $cookie = $response->headers->getCookies()[0];

        // checking name and value of cookie
        $this->assertEquals('ref', $cookie->getName());

        // parse value as json and check it
        $refCookieLinks = json_decode($cookie->getValue(), true);
        $this->assertCount(2, $refCookieLinks);
        $this->assertArrayHasKey($refLink->id, $refCookieLinks);
        $this->assertArrayHasKey($refLink2->id, $refCookieLinks);
        $this->assertEquals($cookie->getExpiresTime(), $refCookieLinks[$refLink2->id]);
        $this->assertTrue($refCookieLinks[$refLink2->id] > $refCookieLinks[$refLink->id]);

        // check if click was incremented
        $refLink->refresh();
        $refLink2->refresh();
        $this->assertEquals(1, $refLink->clicks);
        $this->assertEquals(1, $refLink2->clicks);

        $this->assertEquals($refCookieLinks, $request->get("_referrals"));
    }

    public function testUnknownReferralCode()
    {
        $program = ReferralProgram::create([
            'name' => 'test',
            'title' => 'Test',
            'description' => 'Test description',
            'uri' => 'test',
        ]);

        $program->links()->create([
            'user_id' => 1,
        ]);

        $request = Request::create($program->uri, parameters: [
            'ref' => 'unknown',
        ]);

        Log::partialMock();
        Log::shouldReceive('warning')->once();

        $middleware = new StoreReferralCode();
        $response = $middleware->handle($request, function () {
            return response('');
        });

        // is this redirect?
        $this->assertEquals(200, $response->getStatusCode());

        // is cookie was set
        $this->assertCount(0, $response->headers->getCookies());
    }
}
