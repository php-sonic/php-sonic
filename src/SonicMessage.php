<?php

namespace SonicSearch;

/**
 * Wrapper for constructing, serializing and deserializing of messages to and from Sonic.
 */
class SonicMessage
{
    private $segments = null;

    public function __construct(array $segments)
    {
        $this->segments = $segments;
    }

    public static function fromStr(string $message): SonicMessage
    {
        return new SonicMessage(explode(' ', $message));
    }

    public function serialize(): string
    {
        return implode(' ', $this->segments);
    }

    public function getVerb(): string
    {
        return $this->segments[0];
    }

    public function setVerb(string $verb)
    {
        $this->segments[0] = $verb;
    }

    public function getArgument(int $idx): string
    {
        return $this->segments[$idx + 1];
    }

    public function getArgumentInt(int $idx): int
    {
        return intval($this->getArgument($idx));
    }

    public function setArgument(int $idx, string $value)
    {
        $this->segments[$idx + 1] = $value;
    }

    public function setArgumentInt(int $idx, int $value)
    {
        $this->setArgument($idx, (string)$value);
    }

    public function setArgumentKeyVal(int $idx, string $key, string $value)
    {
        $this->setArgument($idx, "$key($value)");
    }

    public function asArgumentList(int $startIdx): SonicArgumentList
    {
        return SonicArgumentList::fromMessage($this->segments, $startIdx + 1);
    }

    public function asArray(int $startIdx): array
    {
        return array_slice($this->segments, $startIdx + 1);
    }

    public static function sanitizeValue(string &$value): string
    {
        return preg_replace('/[\r\n\t"]/', ' ', $value);
    }

    public static function quoted(string &$value): string
    {
        return '"' . $value . '"';
    }

    public function length(): int
    {
        $result = 0;
        foreach ($this->segments as $segment) {
            $result += strlen($segment);
        }
        return $result + count($this->segments) - 1;
    }

    public function argumentCnt(): int
    {
        return count($this->segments) - 1;
    }
}
