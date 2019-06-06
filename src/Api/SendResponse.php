<?php

namespace Atymic\SmsBroadcast\Api;

class SendResponse
{
    const RESPONSE_SPLIT_DELIMITER = ':';
    const RESPONSE_CODE_SUCCESS = 'OK';
    const RESPONSE_CODE_ERROR = 'ERROR';
    const RESPONSE_CODE_BAD = 'BAD';

    /** @var string */
    private $code;
    /** @var string|null */
    private $to = null;
    /** @var string */
    private $smsRef = null;
    /** @var string|null */
    private $error = null;

    /**
     * @param string $code
     * @param string|null $to
     * @param string|null $smsRef
     * @param string|null $error
     */
    public function __construct(string $code, ?string $to = null, ?string $smsRef = null, ?string $error = null)
    {
        $this->code = $code;
        $this->to = $to;
        $this->error = $error;
        $this->smsRef = $smsRef;
    }

    public function hasError(): bool
    {
        return !is_null($this->error);
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string|null
     */
    public function getTo(): ?string
    {
        return $this->to;
    }

    /**
     * @return string
     */
    public function getSmsRef(): string
    {
        return $this->smsRef;
    }

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    public static function fromResponse(string $response)
    {
        $split = explode(self::RESPONSE_SPLIT_DELIMITER, $response);
        $code = trim($split[0]);

        if ($code === self::RESPONSE_CODE_BAD) {
            return new self(
                $code,
                trim($split[1]),
                null,
                trim($split[2])
            );
        }

        if ($code === self::RESPONSE_CODE_ERROR) {
            return new self(
                $code,
                null,
                null,
                trim($split[1])
            );
        }

        return new self($code, trim($split[1]), trim($split[2]));
    }
}