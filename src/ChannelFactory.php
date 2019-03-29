<?php

namespace SonicSearch;

class ChannelFactory
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $password;

    /**
     * @var int
     */
    private $connectionTimeout;

    /**
     * @var string
     */
    private $receiveTimeout;

    /**
     * ChannelFactory constructor.
     * @param $host
     * @param $port
     * @param $password
     * @param $connectionTimeout
     * @param $receiveTimeout
     */
    public function __construct(string $host, int $port, string $password, int $connectionTimeout = 10, int $receiveTimeout = 0)
    {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->connectionTimeout = $connectionTimeout;
        $this->receiveTimeout = $receiveTimeout;
    }

    public function newIngestChannel(): IngestChannel
    {
        return new IngestChannel(
            $this->host,
            $this->port,
            $this->password,
            $this->connectionTimeout,
            $this->receiveTimeout
        );
    }

    public function newSearchChannel(): SearchChannel
    {
        return new SearchChannel(
            $this->host,
            $this->port,
            $this->password,
            $this->connectionTimeout,
            $this->receiveTimeout
        );
    }

    public function newControlChannel(): ControlChannel
    {
        return new ControlChannel(
            $this->host,
            $this->port,
            $this->password,
            $this->connectionTimeout,
            $this->receiveTimeout
        );
    }
}
