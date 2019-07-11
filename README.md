# SmsBroadcast PHP API Client
[![Build Status](https://travis-ci.org/atymic/sms-broadcast-php.svg?branch=master)](https://travis-ci.org/atymic/sms-broadcast-php) [![Latest Stable Version](https://poser.pugx.org/atymic/sms-broadcast/v/stable)](https://packagist.org/packages/atymic/sms-broadcast) [![License](https://poser.pugx.org/atymic/sms-broadcast/license)](https://packagist.org/packages/atymic/sms-broadcast)

This is a simple API client for [SMS Broadcast](https://www.smsbroadcast.com.au/).

You can view their API documentation [here](https://www.smsbroadcast.com.au/Advanced%20HTTP%20API.pdf).

## Install

```bash
composer require atymic/sms-broadcast
```


## Usage

# Creating the client

```php
$client = \Atymic\SmsBroadcast\Factory\ClientFactory::create(
    'username',
    'password',
    '0412345678' // Default sender, optional
);
```

# Sending a message to a single recipient
```php
try {
    $response = $client->send('0487654321', 'This is an sms message');
} catch (\Atymic\SmsBroadcast\Exception\SmsBroadcastException $e) {
    echo 'Failed to send with error: ' . $e->getMessage();
}

echo 'SMS sent, ref: ' . $response->getSmsRef();
```

# Sending a message to a multiple recipients
```php
$to = ['0487654321', '0487654322', '0487654323']
$responses = $client->sendMultiple($to, 'This is an sms message');

foreach ($responses as $response) {
    echo $response->hasError()
        ? 'Failed to send SMS: ' . $response->getError()
        : 'SMS sent, ref: ' . $response->getSmsRef();
}
```

## Tests
By default only unit tests will run. If you want to run the integration tests, copy the `phpunit.dist.xml` file to `phpunit.xml` and supply your SMS Broadcast credentials & to number in the file.

WARNING - Integration tests will send real SMS messages, so make sure not to run them in CI.

```bash
composer test
```

# Todo
- Support for incoming message webhooks
