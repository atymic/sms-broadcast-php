<?php

namespace Atymic\SmsBroadcast\Api;

use Atymic\SmsBroadcast\Exception\InvalidMessageException;
use Atymic\SmsBroadcast\Exception\InvalidNumberException;
use Atymic\SmsBroadcast\Exception\InvalidSenderException;
use Atymic\SmsBroadcast\Exception\SendException;
use Atymic\SmsBroadcast\Exception\SmsBroadcastException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

class Client
{
    /**
     * @see https://www.smsbroadcast.com.au/Advanced%20HTTP%20API.pdf
     *
     * @var string
     */
    const API_ENDPOINT = 'https://api.smsbroadcast.com.au/api-adv.php';

    const ACTION_BALANCE = 'balance';

    /** @var \GuzzleHttp\Client */
    private $client;
    /** @var string */
    private $username;
    /** @var string */
    private $password;
    /** @var string|null */
    private $sender;

    /**
     * @param \GuzzleHttp\Client $client
     * @param string             $username
     * @param string             $password
     * @param string|null        $sender
     */
    public function __construct(\GuzzleHttp\Client $client, string $username, string $password, ?string $sender = null)
    {
        $this->client = $client;
        $this->username = $username;
        $this->password = $password;
        $this->sender = $sender;
    }

    /**
     * @param array       $to
     * @param string      $message
     * @param string|null $sender
     * @param string|null $ref
     * @param bool        $split
     * @param int|null    $delay
     *
     * @return SendResponse[]
     * @throws InvalidMessageException
     * @throws InvalidNumberException
     * @throws InvalidSenderException
     * @throws SendException
     *
     */
    public function sendMany(
        array $to,
        string $message,
        ?string $sender = null,
        ?string $ref = null,
        bool $split = true,
        ?int $delay = null
    ): array {
        $sendRequest = new SendRequest($to, $message, $sender ?? $this->sender, $ref, $split, $delay);

        $sendRequest->validate();

        try {
            $response = $this->client->get(self::API_ENDPOINT, [
                RequestOptions::QUERY => array_merge(
                    $sendRequest->toRequest(),
                    ['username' => $this->username, 'password' => $this->password]
                ),
            ]);
        } catch (RequestException $exception) {
            throw new SendException(sprintf('Failed to send SMS: %s', (string) $exception));
        }

        return SendResponse::fromResponse((string) $response->getBody());
    }

    /**
     * @param string      $to
     * @param string      $message
     * @param string|null $sender
     * @param string|null $ref
     * @param bool        $split
     * @param int|null    $delay
     *
     * @return SendResponse
     * @throws InvalidMessageException
     * @throws InvalidNumberException
     * @throws InvalidSenderException
     * @throws SendException
     *
     */
    public function send(
        string $to,
        string $message,
        ?string $sender = null,
        ?string $ref = null,
        bool $split = true,
        ?int $delay = null
    ): SendResponse {
        $response = $this->sendMany(
            [$to],
            $message,
            $sender,
            $ref,
            $split,
            $delay
        )[0];

        if ($response->hasError()) {
            throw new SendException(sprintf(
                    'Failed to send message to `%s` with error `%s`',
                    $response->getTo(),
                    $response->getError())
            );
        }

        return $response;
    }

    /**
     * Get credit balance of the account
     *
     * @return int
     * @throws SmsBroadcastException
     */
    public function getBalance(): int
    {
        try {
            $response = $this->client->get(self::API_ENDPOINT, [
                RequestOptions::QUERY => [
                    'action' => self::ACTION_BALANCE,
                    'username' => $this->username,
                    'password' => $this->password,
                ],
            ]);
        } catch (RequestException $exception) {
            throw new SmsBroadcastException(sprintf('Failed to fetch balance: %s', (string) $exception));
        }

        return $this->parseBalanceResponse((string) $response->getBody());

    }

    /**
     * @param string $content
     *
     * @return int
     * @throws SmsBroadcastException
     */
    private function parseBalanceResponse(string $content): int
    {
        if (strpos($content, SendResponse::RESPONSE_CODE_ERROR) !== false) {
            throw new SmsBroadcastException(sprintf('Failed to fetch balance: %s', $content));
        }

        return (int) trim(explode(':', $content)[1]);
    }
}
