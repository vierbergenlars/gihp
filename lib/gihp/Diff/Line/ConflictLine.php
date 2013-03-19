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
 * A line that was conflicted in the merge
 */
class ConflictLine extends Line
{
    private $base;
    private $head;
    /**
     * Creates a new conflicted line
     * @param string $base        The line as present in the base branch
     * @param string $head        The line as present in the head branch
     * @param type   $branch_name The name of the head branch
     */
    public function __construct($base, $head, $branch_name = '')
    {
        $this->base = $base;
        $this->head = $head;
        $line = '<<<<<<< HEAD'."\n";
        $line.= $base."\n";
        $line.= '======='."\n";
        $line.= $head."\n";
        $line.= '>>>>>>> '.$branch_name;
        parent::__construct($line, parent::BOTH);
    }

    /**
     * The line as present in the base branch
     * @return string
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * The line as present in the head branch
     * @return string
     */
    public function getHead()
    {
        return $this->head;
    }

    /**
     * Converts the conflicted line to an internal representation
     * @internal
     * @return array
     */
    public function getArray()
    {
        return array('conflict', $this->base, $this->head);
    }
}
