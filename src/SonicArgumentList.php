<?php

namespace SonicSearch;

/**
 * Small helper to parse argument lists from a Sonic response.
 */
class SonicArgumentList
{
    private $arguments;

    public function __construct(array &$arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * Parse the given message segment array, starting at a given index as argument list.
     * @param message string[] Array of message segments to parse as argument list.
     * @param startIdx int Index at which to start parsing within the given message segment list.
     * @return SonicArgumentList Parsed argument list.
     */
    public static function fromMessage(array &$message, int $startIdx): SonicArgumentList
    {
        $result = [];
        for ($i = $startIdx; $i < count($message); ++$i) {
            $argParts = explode('(', trim($message[$i], ')'));
            assert(count($argParts) == 2);
            $result[$argParts[0]] = $argParts[1];
        }
        return new SonicArgumentList($result);
    }

    public function getArgument(string $key, string $default = null): string
    {
        if (!array_key_exists($key, $this->arguments)) {
            return $default;
        }
        return $this->arguments[$key];
    }

    public function getArgumentInt(string $key, int $default = -1): int
    {
        if (!array_key_exists($key, $this->arguments)) {
            return $default;
        }
        return intval($this->arguments[$key]);
    }

}
