<?php

namespace gihp\Diff\Line;

/**
 * A standard diff line
 */
class Line
{
    const NONE = 0x0;
    const BASE = 0x1;
    const HEAD = 0x2;
    const BOTH = 0x3;
    private $line;
    protected $prefix = ' ';
    /**
     * Creates a new diff line
     * @param string $line  The contents of the line
     * @param int    $place The place where the line originates from
     */
    public function __construct($line, $place = self::NONE)
    {
        $this->line = $line;
        $this->place = $place;
    }

    /**
     * Converts the diff line to an internal represenation for the merger
     * @internal
     * @return array
     */
    public function getArray()
    {
        return array($this->prefix, $this->line);
    }

    /**
     * Gets the location the line originates from
     * @return int
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * Gets the contents of the diff line
     * @return string
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * Creates a string representation of the diff line
     * @return string
     */
    public function __toString()
    {
        return $this->prefix.$this->line;
    }
}
