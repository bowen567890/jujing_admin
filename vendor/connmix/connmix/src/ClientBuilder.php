<?php

namespace Connmix;

class ClientBuilder
{

    /**
     * @var string
     */
    protected $host = '';

    /**
     * @var float
     */
    protected $timeout = 10.0;

    /**
     * @return ClientBuilder
     */
    public static function create(): ClientBuilder
    {
        return new static();
    }

    /**
     * @param string $host
     * @return $this
     */
    public function setHost(string $host): ClientBuilder
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param float $timeout
     * @return $this
     */
    public function setTimeout(float $timeout): ClientBuilder
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @return Client
     */
    public function build(): Client
    {
        return new Client([
            'host' => $this->host,
            'timeout' => $this->timeout,
        ]);
    }

}
