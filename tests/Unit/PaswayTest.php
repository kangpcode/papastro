<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v3.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Papastro\Pasway\Pasway;
use Papastro\Pasway\Channel;

class PaswayTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('PASWAY_APP_KEY=test-app-key');
        putenv('PASWAY_APP_SECRET=test-app-secret-12345');
    }

    public function test_channel_factory_methods(): void
    {
        $public = Pasway::channel('chat');
        $this->assertInstanceOf(Channel::class, $public);
        $this->assertSame('chat', $public->getName());

        $private = Pasway::private('orders.1');
        $this->assertSame('private-orders.1', $private->getName());

        $presence = Pasway::presence('room.42');
        $this->assertSame('presence-room.42', $presence->getName());
    }

    public function test_private_channel_authentication_signature(): void
    {
        $socketId = '12345.67890';
        $channel = 'private-orders.99';

        $authPayload = Pasway::auth($channel, $socketId);
        $this->assertArrayHasKey('auth', $authPayload);

        $expectedSig = hash_hmac('sha256', "{$socketId}:{$channel}", 'test-app-secret-12345');
        $this->assertSame("test-app-key:{$expectedSig}", $authPayload['auth']);

        // Verify valid auth
        $this->assertTrue(Pasway::verifyAuth($channel, $socketId, $authPayload['auth']));

        // Verify tampered auth fails
        $tampered = "test-app-key:tampered-signature";
        $this->assertFalse(Pasway::verifyAuth($channel, $socketId, $tampered));
    }

    public function test_presence_channel_authentication_with_user_data(): void
    {
        $socketId = '99999.11111';
        $channel = 'presence-team.1';
        $userData = [
            'user_id' => 'user_42',
            'user_info' => ['name' => 'Dhafa', 'role' => 'Lead'],
        ];

        $authPayload = Pasway::auth($channel, $socketId, $userData);
        $this->assertArrayHasKey('auth', $authPayload);
        $this->assertArrayHasKey('channel_data', $authPayload);

        $this->assertTrue(Pasway::verifyAuth(
            $channel,
            $socketId,
            $authPayload['auth'],
            $authPayload['channel_data']
        ));

        // Tampering with channel_data must invalidate auth
        $tamperedData = json_encode(['user_id' => 'imposter']);
        $this->assertFalse(Pasway::verifyAuth(
            $channel,
            $socketId,
            $authPayload['auth'],
            $tamperedData
        ));
    }

    public function test_broadcast_and_sse_events_buffer(): void
    {
        $testPayload = ['message' => 'Hello World', 'count' => 10];
        $timeBefore = microtime(true) - 0.01;

        // Broadcasting writes to SSE cache file
        Pasway::broadcast(['news', 'alerts'], 'NoticeCreated', $testPayload);

        $events = Pasway::getSseEvents(['news'], $timeBefore);
        $this->assertNotEmpty($events);

        $lastEvent = end($events);
        $this->assertSame('NoticeCreated', $lastEvent['event']);
        $this->assertSame($testPayload, $lastEvent['data']);
        $this->assertContains('news', $lastEvent['channels']);
    }
}
