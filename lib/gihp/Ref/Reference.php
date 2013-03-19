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

namespace gihp\Ref;

use gihp\Defer\Deferrable;
use gihp\Object\Internal;
use gihp\Object\Commit;
use gihp\Object\AnnotatedTag;

use gihp\IO\IOInterface;
use gihp\IO\WritableInterface;
/**
 * The base of all references
 */
abstract class Reference implements Deferrable, WritableInterface
{
    /**
     * The commit or annotated tag that is referenced
     * @var Internal
     * @internal
     */
    protected $commit;
    /**
     * The name of the head (the branch name)
     * @internal
     * @var string
     */
    protected $name;
    /**
     * Creates a new reference
     * @internal creates a new branch or tag
     * @param string   $name   The name of the head reference
     * @param Internal $commit The commit or annotated tag the reference points to
     */
    public function __construct($name, Internal $commit)
    {
        $this->name = $name;
        $this->commit = $commit;
    }

    /**
     * Gets the commit the reference points to
     * @return Commit
     */
    public function getCommit()
    {
        if($this->commit instanceof Commit)

            return $this->commit;
        elseif($this->commit instanceof AnnotatedTag)
            return $this->commit->getObject();
    }

    /**
     * Gets the object the reference points to
     * @return Commit|AnnotatedTag
     */
    public function getObject()
    {
        return $this->commit;
    }

    /**
     * Call magic!
     * Functions are called on the object the reference refers to automatically
     * @deprecated 0.11.0
     */
    public function __call($func, $args)
    {
        trigger_error('gihp\\Ref\\Reference::'+$func+'() is deprecated.'
                .' It uses __call() magic method.'
                .'Use gihp\\Ref\\Reference::getObject()->'+$func+'() instead.'
                , E_USER_DEPRECATED);

        return call_user_func_array(array($this->commit, $func), $args);
    }

    /**
     * Gets the name of the head reference
     * @internal the branch/tag name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Writes the reference and the object it refers to to disk
     * @param \gihp\IO\IOInterface $io
     */
    public function write(IOInterface $io)
    {
        $io->addRef($this);
        $this->commit->write($io);
    }
}
