<?php

namespace PickUrl;

use Goutte\Client as GoutteClient;
use PickUrl\Config as PickUrlConfig;

class Picker extends PickUrlConfig
{
    protected $client;
    protected $method;

    public function __construct()
    {
    }

    public function client($cookies = null)
    {
        if (empty($this->client)) {
            $this->client = new GoutteClient();
        }
        if (!empty($cookies)) {
            $this->client->getCookieJar()->updateFromSetCookie($cookies);
        }

        return $this->client;
    }

    public function reset($cookies = null)
    {
        $this->client = new GoutteClient();
        if (!empty($cookies)) {
            $this->client->getCookieJar()->updateFromSetCookie($cookies);
        }
    }

    public function setHeader($key, $value)
    {
        $this->client()->setHeader($key, $value);
    }

    public function setUserAgent($value)
    {
        $this->setHeader('User-Agent', $value);
    }

    public function setMethod($method = false)
    {
        if (!empty($method)) {
            $this->method = $method;
        }

        return $this;
    }

    public function getMethod()
    {
        if (empty($this->method)) {
            $this->method = self::REQUEST_GET;
        }

        return $this->method;
    }
}
