<?php

declare(strict_types=1);

namespace Atymic\SmsBroadcast\Tests\Integration\Api;

use Atymic\SmsBroadcast\Api\Client;
use Atymic\SmsBroadcast\Api\SendResponse;
use Atymic\SmsBroadcast\Factory\ClientFactory;
use PHPUnit\Framework\TestCase;

class ClientIntegrationTest extends TestCase
{
    /** @var Client */
    private $client;
    /** @var string */
    private $toNumber;

    public function setUp() :void
    {
        if (empty($_SERVER['SMS_BROADCAST_USERNAME']) || empty($_SERVER['SMS_BROADCAST_PASSWORD']) || empty($_SERVER['INTEGRATION_TO_NUMBER'])) {
            $this->markTestSkipped('SMS Broadcast credentials missing');
        }

        $this->client = ClientFactory::create($_SERVER['SMS_BROADCAST_USERNAME'], $_SERVER['SMS_BROADCAST_PASSWORD'], '0412345678');
        $this->toNumber = $_SERVER['INTEGRATION_TO_NUMBER'];
    }

    public function testSend()
    {
        $res = $this->client->send($this->toNumber, 'test message!', 'iamsender');

        $this->assertInstanceOf(SendResponse::class, $res);
        $this->assertFalse($res->hasError());
        $this->assertIsString($res->getSmsRef());
    }

    public function testSendMultiple()
    {
        $res = $this->client->sendMany([$this->toNumber, $this->toNumber], 'test message!', 'iamsender');

        $this->assertContainsOnlyInstancesOf(SendResponse::class, $res);
        $this->assertCount(2, $res);
    }
}
