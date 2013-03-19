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

namespace gihp\Symref;

use gihp\Defer\Deferrable;
use gihp\IO\WritableInterface;
use gihp\Ref\Head;
use gihp\Object\Commit;
/**
 * A symbolic reference
 *
 * A symbolic reference holds a pointer to a reference or to a commit
 */
class SymbolicReference implements Deferrable, WritableInterface
{
    /**
     * The thing being pointed to
     * @var Head|Commit
     */
    protected $head;

    /**
     * Creates a new symref
     * @param Head|Commit $ref The reference to point to
     */
    public function __construct($ref)
    {
        if ($ref instanceof Head) {
            $this->head = $ref;
            $this->symref = true;
        } elseif ($ref instanceof Commit) {
            $this->head = $ref;
            $this->symref = false;
        } else {
            throw new \LogicException('A symref can only point to Commits and Heads');
        }
    }

    /**
     * Gets the SHA of the thing being pointed to.
     *
     * In case of a commit: The SHA of the commit
     * In case of a head: The SHA of the commit the head points to
     * @return string
     */
    public function getSHA1()
    {
        return $this->head->getSHA1();
    }

    /**
     * Is this reference a symbolic one?
     *
     * It is only symbolic when it points to a Head
     * @return bool
     */
    public function isSymbolic()
    {
        return ($this->head instanceof Head);
    }

    /**
     * Gets the commit that is being pointed to
     *
     * In case of a commit: The commit itself
     * In case of a head: The commit the head points to.
     * @return Commit
     */
    public function getCommit()
    {
        if ($this->head instanceof Head) {
            return $this->head->getCommit();
        }

        return $this->head;
    }

    /**
     * Gets the head that is being pointed to
     *
     * In case of a commit: null
     * In case of a head: The head itself
     * @return Head|null
     */
    public function getHead()
    {
        if ($this->head instanceof Head) {
            return $this->head;
        }

        return null;
    }

    /**
     * Writes the symbolic reference and the object it points to to disk
     * @param \gihp\IO\IOInterface $io
     */
    public function write(\gihp\IO\IOInterface $io)
    {
        $io->moveHead($this);
        $this->head->write($io);
    }
}
