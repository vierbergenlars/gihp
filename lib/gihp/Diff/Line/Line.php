<?php
/**
 * Copyright (c) 2013 Lars Vierbergen
 * 
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

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
