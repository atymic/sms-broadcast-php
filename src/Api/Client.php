<?php

namespace Atymic\SmsBroadcast\Api;

use Atymic\SmsBroadcast\Exception\InvalidMessageException;
use Atymic\SmsBroadcast\Exception\InvalidNumberException;
use Atymic\SmsBroadcast\Exception\InvalidSenderException;
use Atymic\SmsBroadcast\Exception\SendException;
use GuzzleHttp\Exception\RequestException;
use function GuzzleHttp\Psr7\str;
use GuzzleHttp\RequestOptions;

class Client
{
    const API_ENDPOINT = 'https://api.smsbroadcast.com.au/api-adv.php';

    /**
     * SMS Broadcast supports only australian mobile phone numbers
     * @var string
     */
    const VALID_NUMBER_REGEX = '/^(?:614|04|4)[\d]{8}$/';

    /**
     * @see https://www.smsbroadcast.com.au/Advanced%20HTTP%20API.pdf
     * @var string
     */
    const VALID_SENDER_REGEX = '/^\S{1,11}$/';

    const MESSAGE_MAX_LENGTH_STANDARD = 160;
    const MESSAGE_MAX_SPLIT = 5;
    const MESSAGE_MAX_LENGTH_SPLIT = 765;

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
     * @param string $username
     * @param string $password
     * @param string|null $sender
     */
    public function __construct(\GuzzleHttp\Client $client, string $username, string $password, ?string $sender = null)
    {
        $this->client = $client;
        $this->username = $username;
        $this->password = $password;
        $this->sender = $sender;
    }

    public function send(
        string $to,
        string $message,
        ?string $sender = null,
        ?string $ref = null,
        bool $split = true,
        ?int $delay = null
    ): SendResponse {
        $request = [
            'username' => $this->username,
            'password' => $this->password,
            'to' => $to,
            'from' => $sender ?? $this->sender,
            'message' => $message,
            'ref' => $ref,
        ];

        if ($split) {
            $request['maxsplit'] = self::MESSAGE_MAX_SPLIT;
        }

        if ($delay) {
            $request['delay'] = $delay;
        }

        $this->validateSendRequest($request);

        try {
            $response = $this->client->get(self::API_ENDPOINT, [
                RequestOptions::QUERY => $request,
            ]);
        } catch (RequestException $exception) {
            throw new SendException(sprintf('Failed to send SMS: %s', (string)$exception));
        }

        $sendResponse = SendResponse::fromResponse((string)$response->getBody());

        if ($sendResponse->hasError()) {
            throw new SendException(sprintf(
                    'Failed to send message to `%s` with error %s',
                    $sendResponse->getTo(),
                    $sendResponse->getError())
            );
        }

        return $sendResponse;
    }

    private function validateSendRequest(array $request)
    {
        if (!preg_match(self::VALID_SENDER_REGEX, $request['from'])) {
            throw new InvalidSenderException('Message sender %s is invalid', $request['from']);
        }

        if (!preg_match(self::VALID_NUMBER_REGEX, $request['to'])) {
            throw new InvalidNumberException('Message to number %s is invalid', $request['to']);
        }

        $maxLength = $request['maxsplit'] ? self::MESSAGE_MAX_LENGTH_SPLIT : self::MESSAGE_MAX_LENGTH_STANDARD;

        if (strlen($request['message']) > $maxLength) {
            throw new InvalidMessageException('Message is over maximum length of %s', $maxLength);
        }
    }
}