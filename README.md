# SmsBroadcast PHP API Client

This is a simple API client for [SMS Broadcast](https://www.smsbroadcast.com.au/).
You can view their API documentation [here](https://www.smsbroadcast.com.au/Advanced%20HTTP%20API.pdf).

## Install

```bash
composer require atymic/sms-broadcast
```

## Usage

```php
$client = \Atymic\SmsBroadcast\Factory\ClientFactory::create(
    'username',
    'password',
    '0412345678' // Default sender, optional
);

try {
    $response = $client->send('0487654321', 'This is an sms message');
} catch (\Atymic\SmsBroadcast\Exception\SmsBroadcastException $e) {
    echo 'Failed to send with error: ' . $e->getMessage();
}

echo 'SMS sent, ref: ' . $response->getSmsRef();

```

## Todo

- Tests
- Support for incoming message webhooks