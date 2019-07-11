<?php

namespace Atymic\SmsBroadcast\Factory;

use Atymic\SmsBroadcast\Api\Client;
use GuzzleHttp\RequestOptions;

abstract class ClientFactory
{
    public static function create(string $username, string $password, ?string $sender = null): Client
    {
        return new Client(
            new \GuzzleHttp\Client([
                RequestOptions::TIMEOUT         => 5,
                RequestOptions::CONNECT_TIMEOUT => 5,
            ]),
            $username,
            $password,
            $sender
        );
    }
}
