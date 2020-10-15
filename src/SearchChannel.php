<?php

namespace SonicSearch;

/**
 * Sonic session implementation for Sonic's search mode.
 */
class SearchChannel extends Channel
{
    public function __construct(string $host, int $port, string $password, int $connectionTimeout, int $receiveTimeout)
    {
        parent::__construct('search', $host, $port, $password, $connectionTimeout, $receiveTimeout);
    }

    /**
     * Query Sonic for the given search terms.
     * @param $collection string Collection within Sonic to query for the given search-term.
     * @param $bucket string Bucket within the collection to query for the given search-term.
     * @param $terms string A search string containing multiple words to query the given bucket for.
     * @param $limit int Optional limit to the amount of returned search results.
     * @param $offset int Optional offset in the pagination of search-results introduced by the limit.
     * @return array
     * @throws NoConnectionException If the connection to Sonic has been lost in the meantime.
     * @throws CommandFailedException If execution of the command failed for which-ever reason.
     */
    public function query(string $collection, string $bucket, string $terms, int $limit = null, int $offset = null): array
    {
        $terms = SonicMessage::sanitizeValue($terms);
        $queryMessage = new SonicMessage(['QUERY', $collection, $bucket,
            SonicMessage::quoted($terms)
        ]);
        if ($limit != null) {
            $queryMessage->setArgumentKeyVal($queryMessage->argumentCnt(), 'LIMIT', $limit);
        }
        if ($offset != null) {
            $queryMessage->setArgumentKeyVal($queryMessage->argumentCnt(), 'OFFSET', $offset);
        }
        $response = $this->sendAndAwaitResponse($queryMessage);
        if ($response->getVerb() !== 'PENDING') {
            throw new CommandFailedException($queryMessage, $response);
        }
        $searchResult = $this->readResponse();
        if ($searchResult->getVerb() !== 'EVENT' && $searchResult->getArgument(0) !== 'QUERY') {
            throw new CommandFailedException($queryMessage, $searchResult);
        }
        return $searchResult->asArray(2);
    }

    /**
     * Request word suggestion from sonic, for the given string.
     * @param $collection string Collection within Sonic to search for suggestions in.
     * @param $bucket string Bucket within the collection to search for suggestions in.
     * @param $word string Beginning of the word to request completions for.
     * @param $limit int Optional limit to the amount of returned suggestions.
     * @throws NoConnectionException If the connection to Sonic has been lost in the meantime.
     * @throws CommandFailedException If execution of the command failed for which-ever reason.
     */
    public function suggest(string $collection, string $bucket, string $word, int $limit = null): array
    {
        $word = SonicMessage::sanitizeValue($word);
        $suggestMessage = new SonicMessage(['SUGGEST', $collection, $bucket,
            SonicMessage::quoted($word)
        ]);
        if ($limit != null) {
            $suggestMessage->setArgumentKeyVal(3, 'LIMIT', $limit);
        }
        $response = $this->sendAndAwaitResponse($suggestMessage);
        if ($response->getVerb() !== 'PENDING') {
            throw new CommandFailedException($suggestMessage, $response);
        }
        $suggestResult = $this->readResponse();
        if ($suggestResult->getVerb() !== 'EVENT' && $suggestResult->getArgument(0) !== 'SUGGEST') {
            throw new CommandFailedException($suggestMessage, $suggestResult);
        }
        return $suggestResult->asArray(2);
    }

}
