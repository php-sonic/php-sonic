<?php

namespace SonicSearch;

/**
 * Sonic session implementation for Sonic's ingest mode.
 */
class IngestChannel extends Channel
{
    public function __construct(string $host, int $port, string $password, int $connectionTimeout, int $receiveTimeout)
    {
        parent::__construct('ingest', $host, $port, $password, $connectionTimeout, $receiveTimeout);
    }

    /**
     * Push the given terms into Sonic's index at the given "path" (collection, bucket, object).
     * @param collection string Collection within Sonic to push the given terms into.
     * @param bucket string Optional bucket within the collection to push the given terms into.
     * @param object string Optional object within the given bucket to push the given terms into.
     * @throws NoConnectionException If the connection to Sonic has been lost in the meantime.
     * @throws CommandFailedException If execution of the command failed for which-ever reason.
     * @throws InvalidArgumentException If the given set of terms could not fit into Sonics receive buffer.
     */
    public function push(string $collection, string $bucket, string $object, string $terms)
    {
        $pushMessageTemplate = new SonicMessage(['PUSH', $collection, $bucket, $object]);
        $valueSplits = $this->splitValue($terms, $pushMessageTemplate->length());
        foreach ($valueSplits as $valueChunk) {
            $pushChunkMessage = $pushMessageTemplate;
            $pushChunkMessage->setArgument(3, SonicMessage::quoted($valueChunk));
            $response = $this->sendAndAwaitResponse($pushChunkMessage);
            if ($response->getVerb() != 'OK') {
                throw new CommandFailedException($pushChunkMessage, $response);
            }
        }
    }

    /**
     * Pop-Search the given terms from Sonic's index with the given "path" (collection, bucket, object).
     * @param collection string Collection within Sonic to pop-search the given terms from.
     * @param bucket string Optional bucket within the collection to pop-search the given terms from.
     * @param object string Optional object within the given bucket to pop-search the given terms from.
     * @throws NoConnectionException If the connection to Sonic has been lost in the meantime.
     * @throws CommandFailedException If execution of the command failed for which-ever reason.
     * @throws InvalidArgumentException If the given set of terms could not fit into Sonics receive buffer.
     */
    public function pop(string $collection, string $bucket, string $object, string $terms): int
    {
        $result = 0;
        $popMessageTemplate = new SonicMessage(['POP', $collection, $bucket, $object]);
        $valueSplits = $this->splitValue($terms, $popMessageTemplate->length());
        foreach ($valueSplits as $valueChunk) {
            $popChunkMessage = $popMessageTemplate;
            $popChunkMessage->setArgument(3, SonicMessage::quoted($valueChunk));
            $response = $this->sendMessage($popChunkMessage);
            if ($response->getVerb() != 'RESULT') {
                throw new CommandFailedException($popChunkMessage, $response);
            }
            $result += $response->getArgumentInt(0);
        }
        return $result;
    }

    /**
     * Count the amount of terms within the given "path" (collection, [bucket, [object]]) in Sonic's index.
     * @param collection string Collection within Sonic to count terms in.
     * @param bucket string Optional bucket within the collection to count terms in.
     * @param object string Optional object within the given bucket to count terms in.
     * @throws NoConnectionException If the connection to Sonic has been lost in the meantime.
     * @throws CommandFailedException If execution of the command failed for which-ever reason.
     */
    public function count(string $collection, string $bucket = null, string $object = null): int
    {
        $countMessage = new SonicMessage(['COUNT', $collection]);
        if ($bucket != null) {
            $countMessage->setArgument(1, $bucket);
            if ($object != null) {
                $countMessage->setArgument(2, $object);
            }
        }
        $response = $this->sendAndAwaitResponse($countMessage);
        if ($response->getVerb() != 'RESULT') {
            throw new CommandFailedException($countMessage, $response);
        }
        return $response->getArgumentInt(0);
    }

    /**
     * Flush the given "path" (collection, [bucket, [object]]) in Sonic's index.
     * @param collection string Collection within Sonic to flush.
     * @param bucket string Optional bucket within the collection to flush.
     * @param object string Optional object within the given bucket to flush.
     * @throws NoConnectionException If the connection to Sonic has been lost in the meantime.
     * @throws CommandFailedException If execution of the command failed for which-ever reason.
     */
    public function flush(string $collection, string $bucket = null, string $object = null): int
    {
        $flushOp = 'FLUSHC';
        $flushMessage = new SonicMessage(['', $collection]);
        if ($bucket != null) {
            $flushMessage->setArgument(1, $bucket);
            if ($object == null) {
                $flushOp = 'FLUSHB';
            } else {
                $flushOp = 'FLUSHO';
                $flushMessage->setArgument(2, $object);
            }
        }
        $flushMessage->setVerb($flushOp);
        $response = $this->sendAndAwaitResponse($flushMessage);
        if ($response->getVerb() != 'RESULT') {
            throw new CommandFailedException($flushMessage, $response);
        }
        return $response->getArgumentInt(0);
    }
}
