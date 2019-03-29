<?php

namespace SonicSearch;

/**
 * Sonic session implementation for Sonic's control mode.
 */
class ControlChannel extends Channel
{
    public function __construct(string $host, int $port, string $password, int $connectionTimeout, int $receiveTimeout)
    {
        parent::__construct('control', $host, $port, $password, $connectionTimeout, $receiveTimeout);
    }

    /**
	 * Trigger the given action.
	 * @param action string The action to triger.
	 * @throws NoConnectionException If the connection to Sonic has been lost in the meantime.
	 * @throws CommandFailedException If execution of the command failed for which-ever reason.
	 */
    public function trigger(string $action)
    {
        $triggerMessage = new SonicMessage(['TRIGGER', $action]);
        $response = $this->sendAndAwaitResponse($triggerMessage);
        if($response->getVerb() != 'OK') {
            throw new CommandFailedException($suggestMessage, $suggestResult);
        }
    }

    /**
	 * Trigger a consolidation.
	 * @throws NoConnectionException If the connection to Sonic has been lost in the meantime.
	 * @throws CommandFailedException If execution of the command failed for which-ever reason.
	 */
    public function consolidate()
    {
        $this->trigger('consolidate');
    }

}
