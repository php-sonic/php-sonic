<?php

namespace SonicSearch;

use InvalidArgumentException;

/**
 * Base class for Sonic sessions. This class should be overloaded once per possible mode/session.
 * There, for example, are overloads for Search and Ingest.
 */
abstract class Channel
{
    /**
     * @var string
     */
    private $mode;
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
    private $receiveTimeout;
    /**
	 * @var int
	 */
    private $connectionTimeout;


    /**
     * @var resource
     */
    private $socket = null;
    /**
     * @var int
     */
    private $receiveBufferSize = 8192;


    public function __construct(string $mode, string $host, int $port, string $password, int $connectionTimeout, int $receiveTimeout)
    {
        $this->mode = $mode;
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->connectionTimeout = $connectionTimeout;
        $this->receiveTimeout = $receiveTimeout;
    }

    public function __destruct()
    {
        $this->quit();
    }

    /**
	 * Manually close the connection to Sonic.
	 */
    public function quit()
    {
        if($this->socket) {
            try{
                $this->sendMessage(new SonicMessage(['QUIT']));
            } catch(NoConnectionException $e) {}
            stream_socket_shutdown($this->socket, STREAM_SHUT_WR);
            $this->socket = null;
        }
    }

    /**
	 * Connect to the Sonic-Server and switch to the corresponding session mode.
	 * @throws NoConnectionException If connecting to the sonic instance failed.
	 * @throws AuthenticationException If the given password was wrong.
	 * @throws ProtocolException If Sonic misbehaved or announced an unsupported protocol version.
	 */
    public function connect()
    {
        if($this->socket) { { return; }
            $errno = 0; $errmsg = "";
            $this->socket = stream_socket_client('tcp://' . $this->host . ':' . $this->port, $errno, $errmsg, $this->connectionTimeout);
            if(!$this->socket) { throw new NoConnectionException("Could not connect to Sonic [$errno]: $errmsg"); }
            if($this->receiveTimeout > 0) { stream_set_timeout($this->socket, $this->receiveTimeout); }
        }
        $helloMessage = $this->readResponse();
        if($helloMessage->getVerb() != 'CONNECTED') { throw new ProtocolException("Sonic did not greet us."); }

        // Start session
        $response = $this->sendAndAwaitResponse(new SonicMessage(['START', $this->mode, $this->password]));
        if($response->getVerb() != 'STARTED') {
            $reason = $response->getArgument(0);
            if($reason == 'authentication_required') {
                throw new AuthenticationException("A password is required to access this Sonic server");
            } elseif($reason == 'authentication_failed') {
                throw new AuthenticationException("Wrong password given");
            }
            throw new ProtocolException("Sonic Mode-Change failed: ". $response->serialize());
        }
        $arguments = $response->asArgumentList(1);
        $this->receiveBufferSize = $arguments->getArgumentInt('buffer', 8192);
        if($arguments->getArgumentInt('protocol', -1) != 1) {
            throw new ProtocolException("Sonic instance announced unsupported protocol version.");
        }
    }


    /* ################## */
    /* # SEND / RECEIVE # */
    /* ################## */

    /**
	 * Send the given message and directly await and parse Sonic's response to it.
	 * @param SonicMessage message The message to send to Sonic.
	 * @return SonicMessage The parsed message Sonic sent us.
	 * @throws NoConnectionException If the connection to Sonic has been lost in the meantime.
	 */
    protected function sendAndAwaitResponse(SonicMessage $message) : SonicMessage {
        $this->sendMessage($message);
        return $this->readResponse();
    }

    /**
	 * Send the given message to Sonic.
	 * @param SonicMessage message The message to send to Sonic.
	 * @throws NoConnectionException If the connection to Sonic has been lost in the meantime.
	 */
    protected function sendMessage(SonicMessage $message)
    {
        $messageStr = $message->serialize() . "\n";
        $result = fputs($this->socket, $messageStr);
        if($result === false) { throw new NoConnectionException(""); }
        if(defined('__SONIC_CLIENT_DEBUG__') && __SONIC_CLIENT_DEBUG__) { echo('[' . $this->mode . ":SENT]: $messageStr"); }
    }

    /**
	 * Read one response line from the connection to Sonic.
	 * @return SonicMessage The parsed message Sonic sent us.
	 * @throws NoConnectionException If the connection to Sonic has been lost in the meantime.
	 */
    protected function readResponse() : SonicMessage {
        $response = stream_get_line($this->socket, $this->receiveBufferSize, "\r\n");
        if(defined('__SONIC_CLIENT_DEBUG__') && __SONIC_CLIENT_DEBUG__) { echo('[' . $this->mode . ":RECV]: $response\n"); }
        if($response === false) { throw new NoConnectionException(""); }
        return SonicMessage::fromStr($response);
    }

    /**
	 * Intelligently split the given value so that each chunk fits into the remaining buffer space.
	 * The value is split at spaces within the string.
	 * @param string The value to split into chunks (if necessary).
	 * @param int usedBuffer The amount of buffer-spaced that is already used.
	 * @return string[] Given big value string split up into chunks that each fit into the buffer.
	 * @throws InvalidArgumentException If the string contained one word that is larger than the free buffer space.
	 */
    protected function splitValue(string &$value, int $usedBuffer) : array {
        $value = SonicMessage::sanitizeValue($value);
        $result = [];
        $maxMessageLength = $this->receiveBufferSize - $usedBuffer - 3; //Space in before, and quotes = 3
        $ptr = 0;
        while($ptr < strlen($value)) {
            $remaining = strlen($value) - $ptr;
            $segmentEnd = min($remaining, $maxMessageLength);
            $lastSplitterInSegment = $ptr + $segmentEnd;
            if($remaining > $maxMessageLength) {
                $lastSplitterInSegment = strrpos($value, ' ', -(strlen($value) - $ptr - $segmentEnd));
                if($lastSplitterInSegment < $ptr) {
                    throw new InvalidArgumentException("Message contains words longer than the receiveBuffer.");
                }
            }
            $result[] = substr($value, $ptr, $lastSplitterInSegment - $ptr);
            $ptr = $lastSplitterInSegment + 1;
        }
        return $result;
    }



    /* ############ */
    /* # COMMANDS # */
    /* ############ */

    /**
	 * Ping the Sonic server and await its pong response.
	 * @throws NoConnectionException If the connection to Sonic has been lost in the meantime.
	 * @throws CommandFailedException If execution of the command failed for which-ever reason.
	 */
    public function ping()
    {
        $pingMessage = new SonicMessage(['PING']);
        $response = $this->sendAndAwaitResponse($pingMessage);
        if($response->getVerb() != 'PONG') {
            throw new CommandFailedException($pingMessage, $response);
        }
    }

};
