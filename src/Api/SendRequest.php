<?php

declare(strict_types=1);

namespace Atymic\SmsBroadcast\Api;

use Atymic\SmsBroadcast\Exception\InvalidMessageException;
use Atymic\SmsBroadcast\Exception\InvalidNumberException;
use Atymic\SmsBroadcast\Exception\InvalidSenderException;

class SendRequest
{
    /**
     * SMS Broadcast supports only australian mobile phone numbers.
     *
     * @var string
     */
    const VALID_NUMBER_REGEX = '/^(?:614|04|4)[\d]{8}$/';

    /**
     * @var string
     */
    const VALID_SENDER_REGEX = '/^\S{1,11}$/';

    const MESSAGE_MAX_LENGTH_STANDARD = 160;
    const MESSAGE_MAX_SPLIT = 5;
    const MESSAGE_MAX_LENGTH_SPLIT = 765;

    /** @var array */
    private $to;
    /** @var string */
    private $message;
    /** @var string|null */
    private $sender;
    /** @var string|null */
    private $ref;
    /** @var bool */
    private $split;
    /** @var int|null */
    private $delay;

    /**
     * @param array       $to
     * @param string      $message
     * @param string|null $sender
     * @param string|null $ref
     * @param bool        $split
     * @param int|null    $delay
     */
    public function __construct(
        array $to,
        string $message,
        ?string $sender = null,
        ?string $ref = null,
        bool $split = true,
        ?int $delay = null
    ) {
        $this->to = $to;
        $this->message = $message;
        $this->sender = $sender;
        $this->ref = $ref;
        $this->split = $split;
        $this->delay = $delay;
    }

    public function validate(): void
    {
        if (!preg_match(self::VALID_SENDER_REGEX, $this->sender)) {
            throw new InvalidSenderException(sprintf('Message sender `%s` is invalid', $this->sender));
        }

        if (empty($this->to)) {
            throw new InvalidNumberException('No `to` number(s)');
        }

        array_map(function (string $toNumber) {
            if (!preg_match(self::VALID_NUMBER_REGEX, $toNumber)) {
                throw new InvalidNumberException(sprintf('Message to number `%s` is invalid', $toNumber));
            }
        }, $this->to);

        $maxLength = $this->split ? self::MESSAGE_MAX_LENGTH_SPLIT : self::MESSAGE_MAX_LENGTH_STANDARD;

        if (empty($this->message)) {
            throw new InvalidMessageException('Message is empty');
        }

        if (strlen($this->message) > $maxLength) {
            throw new InvalidMessageException(sprintf(
                'Message length `%s` of chars is over maximum length of `%s` chars',
                strlen($this->message),
                $maxLength
            ));
        }
    }

    public function toRequest(): array
    {
        $request = [
            'to'      => implode(',', $this->to),
            'from'    => $this->sender,
            'message' => $this->message,
            'ref'     => $this->ref,
        ];

        if ($this->split) {
            $request['maxsplit'] = self::MESSAGE_MAX_SPLIT;
        }

        if ($this->delay) {
            $request['delay'] = $this->delay;
        }

        return $request;
    }
}
