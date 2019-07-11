<?php
declare(strict_types=1);

namespace Atymic\SmsBroadcast\Tests\Unit\Api;

use Atymic\SmsBroadcast\Api\Client;
use Atymic\SmsBroadcast\Api\SendResponse;
use Atymic\SmsBroadcast\Exception\InvalidMessageException;
use Atymic\SmsBroadcast\Exception\InvalidNumberException;
use Atymic\SmsBroadcast\Exception\InvalidSenderException;
use Atymic\SmsBroadcast\Exception\SendException;
use BlastCloud\Guzzler\UsesGuzzler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    use UsesGuzzler;

    /** @var Client */
    private $client;

    public function setUp(): void
    {
        parent::setUp();

        $http = $this->guzzler->getClient();
        $this->client = new Client($http, 'user', 'password', '0412345678');

    }

    /**
     * @dataProvider dataSendValidationInvalid
     */
    public function testSendValidationInvalid(array $args, string $expectedException, ?string $expectedMessage = null)
    {
        $this->expectException($expectedException);

        if ($expectedMessage) {
            $this->expectExceptionMessage($expectedMessage);
        }

        $this->client->send(...$args);
    }

    public function dataSendValidationInvalid()
    {
        return [
            'empty to' => [
                'args' => [
                    '',
                    'test message',
                ],
                'exception' => InvalidNumberException::class,
                'message' => 'Message to number `` is invalid',
            ],
            'invalid to number' => [
                'args' => [
                    '041234567',
                    'test message',
                ],
                'exception' => InvalidNumberException::class,
                'message' => 'Message to number `041234567` is invalid',
            ],
            'empty message' => [
                'args' => [
                    '0412345678',
                    '',
                ],
                'exception' => InvalidMessageException::class,
                'message' => 'Message is empty',
            ],
            'message too long' => [
                'args' => [
                    '0412345678',
                    str_repeat('test ', 200),
                ],
                'exception' => InvalidMessageException::class,
                'message' => 'Message length `1000` of chars is over maximum length of `765` chars',
            ],
            'invalid sender' => [
                'args' => [
                    '0412345678',
                    'test message',
                    '',
                ],
                'exception' => InvalidSenderException::class,
            ],
        ];
    }

    public function testSendSuccess()
    {
        $this->guzzler->expects($this->once())
            ->get(Client::API_ENDPOINT)
            ->withQuery([
                'username' => 'user',
                'password' => 'password',
                'to' => '0412345678',
                'from' => '0412345678',
                'message' => 'test message',
                'ref' => 'ref234',
                'maxsplit' => 5,
            ], true)
            ->willRespond(new Response(200, [], 'OK: 61412345678:ref234 '));

        $this->client->send('0412345678', 'test message', null, 'ref234');
    }

    public function testSendInvalidUserPass()
    {
        $this->guzzler->expects($this->once())
            ->get(Client::API_ENDPOINT)
            ->willRespond(new Response(200, [], 'ERROR: Username or password is incorrect '));

        $this->expectException(SendException::class);
        $this->expectExceptionMessage('Failed to send message to `` with error `Username or password is incorrect`');

        $this->client->send('0412345678', 'test message', null, 'ref234');
    }

    public function testSendInvalidToNumber()
    {
        $this->guzzler->expects($this->once())
            ->get(Client::API_ENDPOINT)
            ->willRespond(new Response(200, [], 'BAD:0412345678:Invalid Number'));

        $this->expectException(SendException::class);
        $this->expectExceptionMessage('Failed to send message to `0412345678` with error `Invalid Number`');

        $this->client->send('0412345678', 'test message', null, 'ref234');
    }

    public function testSendMultiple()
    {
        $this->guzzler->expects($this->once())
            ->get(Client::API_ENDPOINT)
            ->withQuery([
                'username' => 'user',
                'password' => 'password',
                'to' => '0412345678,0413345678,0414345678',
                'from' => '0412345678',
                'message' => 'test message',
                'maxsplit' => 5,
            ], true)
            ->willRespond(new Response(200, [], "OK: 0412345678:abcd1\nOK: 0413345678:abcd2\nOK: 0414345678:abcd3\n"));

        $results = $this->client->sendMany(
            [
                '0412345678',
                '0413345678',
                '0414345678',
            ],
            'test message'
        );

        $this->assertContainsOnlyInstancesOf(SendResponse::class, $results);
        $this->assertCount(3, $results);
    }
}
