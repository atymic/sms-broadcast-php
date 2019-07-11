<?php

declare(strict_types=1);

namespace Atymic\SmsBroadcast\Tests\Unit\Factory;

use Atymic\SmsBroadcast\Api\Client;
use Atymic\SmsBroadcast\Factory\ClientFactory;
use PHPUnit\Framework\TestCase;

class ClientFactoryTest extends TestCase
{
    public function testCreate()
    {
        $client = ClientFactory::create('a', 'b');

        $this->assertInstanceOf(Client::class, $client);
    }
}
