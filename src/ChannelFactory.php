<?php

namespace SonicSearch;

class ChannelFactory
{
    private $address;

    private $port;

    private $password;

    private $connectionTimeout;

    private $readTimeout;

    /**
     * ChannelFactory constructor.
     * @param $address
     * @param $port
     * @param $password
     * @param $connectionTimeout
     * @param $readTimeout
     */
    public function __construct($address, $port, $password, $connectionTimeout, $readTimeout)
    {
        $this->address = $address;
        $this->port = $port;
        $this->password = $password;
        $this->connectionTimeout = $connectionTimeout;
        $this->readTimeout = $readTimeout;
    }

    public function newIngestChannel()
    {
        return new IngestChannel(
            $this->address,
            $this->port,
            $this->password,
            $this->connectionTimeout,
            $this->readTimeout
        );
    }

    public function newSearchChannel()
    {
        return new SearchChannel(
            $this->address,
            $this->port,
            $this->password,
            $this->connectionTimeout,
            $this->readTimeout
        );
    }

    public function newControlChannel()
    {
        return new ControlChannel(
            $this->address,
            $this->port,
            $this->password,
            $this->connectionTimeout,
            $this->readTimeout
        );
    }
}
