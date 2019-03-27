<?php

namespace SonicSearch;

use RuntimeException;

class SearchChannel extends Channel
{
    public function __construct($address, $port, $password, $connectionTimeout, $readTimeout)
    {
        parent::__construct($address, $port, $password, $connectionTimeout, $readTimeout);
        $this->start(Mode::SEARCH);
    }

    public function query($collection, $bucket, $terms, $limit = null, $offset = null)
    {
        $cmd = "QUERY $collection $bucket \"$terms\"";

        if ($limit !== null) {
            $cmd .= " LIMIT($limit)";
        }

        if ($offset !== null) {
            $cmd .= " OFFSET($offset)";
        }

        $this->send($cmd);
        $resp1 = $this->readBuffer();
        $resp2 = $this->readBuffer();

        if (preg_match("%^PENDING ([a-zA-Z0-9]+)\r\n$%", $resp1, $matches1) === false) {
            throw new RuntimeException("unexpected response: $resp1");
        }

        if (preg_match("%^EVENT QUERY {$matches1[1]} (.+)?\r\n$%", $resp2, $matches2) === false) {
            throw new RuntimeException("unexpected response: $resp2");
        }

        return count($matches2) === 2 ? explode(' ', $matches2[1]) : [];
    }

    public function suggest($collection, $bucket, $word, $limit = null)
    {
        $cmd = "SUGGEST $collection $bucket \"$word\"";

        if ($limit !== null) {
            $cmd .= " LIMIT($limit)";
        }

        $this->send($cmd);
        $resp1 = $this->readBuffer();
        $resp2 = $this->readBuffer();

        if (!preg_match("%^PENDING ([a-zA-Z0-9]+)\r\n$%", $resp1, $matches1)) {
            throw new RuntimeException("unexpected response: $resp1");
        }

        if (!preg_match("%^EVENT SUGGEST {$matches1[1]} (.+)?\r\n$%", $resp2, $matches2)) {
            throw new RuntimeException("unexpected response: $resp2");
        }

        return count($matches2) === 2 ? explode(' ', $matches2[1]) : [];
    }
}
