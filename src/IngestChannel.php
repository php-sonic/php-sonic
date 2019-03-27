<?php

namespace SonicSearch;

use RuntimeException;
use InvalidArgumentException;

class IngestChannel extends Channel
{
    public function __construct($address, $port, $password, $connectionTimeout, $readTimeout)
    {
        parent::__construct($address, $port, $password, $connectionTimeout, $readTimeout);
        $this->start(Mode::INGEST);
    }

    public function push($collection, $bucket, $object, $text)
    {
        $cmd = "PUSH $collection $bucket $object \"$text\"";

        $this->send($cmd);
        $this->assertOK();
    }

    public function pop($collection, $bucket, $object, $text)
    {
        $cmd = "POP $collection $bucket $object \"$text\"";

        $this->send($cmd);
        $this->assertOK();
    }

    public function count($collection, $bucket = null, $object = null)
    {
        if ($bucket === null && $object !== null) {
            throw new InvalidArgumentException('bucket is required for counting an object');
        }

        $cmd = sprintf('COUNT %s%s%s', $collection, $bucket ? " $bucket" : '', $object ? " $object" : '');
        $this->send($cmd);
        return $this->assertResult();
    }

    public function flushc($collection)
    {
        $cmd = sprintf('FLUSHC %s', $collection);
        $this->send($cmd);
        return $this->assertResult();
    }

    public function flushb($collection, $bucket)
    {
        $cmd = sprintf('FLUSHB %s %s', $collection, $bucket);
        $this->send($cmd);
        return $this->assertResult();
    }

    public function flusho($collection, $bucket, $object)
    {
        $cmd = sprintf('FLUSHO %s %s %s', $collection, $bucket, $object);
        $this->send($cmd);
        return $this->assertResult();
    }
}
