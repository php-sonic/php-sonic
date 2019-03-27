<?php

namespace SonicSearch;

class ControlChannel extends Channel
{
    public function __construct($address, $port, $password, $connectionTimeout, $readTimeout)
    {
        parent::__construct($address, $port, $password, $connectionTimeout, $readTimeout);
        $this->start(Mode::CONTROL);
    }

    public function consolidate()
    {
        $this->send('TRIGGER consolidate');
        $this->assertOK();
    }
}
