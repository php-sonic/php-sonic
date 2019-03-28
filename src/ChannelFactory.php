<?php

namespace SonicSearch;

class ChannelFactory {

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
     * @var string
     */
	private $mode;
    /**
     * @var int
     */
	private $receiveTimeout;
	/**
	 * @var int
	 */
	private $connectionTimeout;

	
    /**
     * ChannelFactory constructor.
     * @param $host
     * @param $port
     * @param $password
     * @param $connectionTimeout
     * @param $readTimeout
     */
    public function __construct(string $host, int $port, string $password, int $connectionTimeout = 10, int $readTimeout = 0) {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->connectionTimeout = $connectionTimeout;
        $this->readTimeout = $readTimeout;
    }

    public function newIngestChannel() : IngestChannel {
        return new IngestChannel(
            $this->host,
            $this->port,
            $this->password,
            $this->connectionTimeout,
            $this->readTimeout
        );
    }

    public function newSearchChannel() : SearchChannel {
        return new SearchChannel(
            $this->host,
            $this->port,
            $this->password,
            $this->connectionTimeout,
            $this->readTimeout
        );
    }

    public function newControlChannel() : ControlChannel {
        return new ControlChannel(
            $this->host,
            $this->port,
            $this->password,
            $this->connectionTimeout,
            $this->readTimeout
        );
    }
}
