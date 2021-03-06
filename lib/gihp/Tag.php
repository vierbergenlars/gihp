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

namespace gihp;

use gihp\IO\IOInterface;
use gihp\IO\WritableInterface;
use gihp\Object\Internal;
use gihp\Ref\Tag as RTag;
use gihp\Object\AnnotatedTag;
use gihp\Metadata\Person;

/**
 * A git tag
 */
class Tag implements WritableInterface
{
    /**
     * The backing tag object
     * @var RTag
     */
    protected $tag;

    /**
     * Creates a new tag
     * @param string    $name    The name of the tag
     * @param Internal  $commit  The commit the tag points to
     * @param string    $message Tag message (creates an annotated tag)
     * @param Person    $tagger  The person who created the tag (required for annotated tags)
     * @param \DateTime $date    The time of tagging (defaults to now, optional for annotated tags)
     */
    public function __construct($name, Internal $commit, $message = null, Person $tagger = null, \DateTime $date = null)
    {
        if ($message !== null) { // Creates an annotated tag
            $date = ($date === null?new \DateTime: $date);
            $commit = new AnnotatedTag($name, $message, $tagger, $date, $commit);
        }
        $this->tag = new RTag($name, $commit);
    }

    /**
     * Get the name of the tag
     * @return string
     */
    public function getName()
    {
        return $this->tag->getName();
    }

    /**
     * Gets the tag message
     *
     * @return string Tag message if available, else commit message
     */
    public function getMessage()
    {
        return $this->tag->getObject()->getMessage();
    }

    /**
     * Gets the tag author
     * @return Person Tag author if available, else commit author
     */
    public function getAuthor()
    {
        return $this->tag->getObject()->getAuthor();
    }

    /**
     * Gets the tag date
     * @return \DateTime Tag date if available, else commit date
     */
    public function getDate()
    {
        return ($this->isAnnotated()?$this->tag->getObject()->getDate():
                    $this->tag->getObject()->getAuthorTime());
    }

    /**
     * Gets the commit the tag points to
     * @return Internal
     */
    public function getCommit()
    {
        return $this->tag->getCommit();
    }

    /**
     * Gets the plumbing tag object this class wraps
     * @return RTag
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Checks whether the tag is an annotated tag or a normal tag
     * @return boolean
     */
    public function isAnnotated()
    {
        return ($this->tag->getObject() instanceof AnnotatedTag);
    }

    /**
     * Call magic!
     * Functions that do exist in the linked \gihp\Ref\Tag object are called automatically
     * @deprectated 0.11.0
     */
    public function __call($func, $args)
    {
        trigger_error('gihp\\Tag::'+$func+'() is deprecated.'
        .' It uses __call() magic method.'
        .'Use gihp\\Tag::getTag()->'+$func+'() instead.'
        , E_USER_DEPRECATED);

        return call_user_func_array(array($this->ref, $func), $args);
    }

    /**
     * Writes the tag to IO
     * @param IOInterface $io An IOInterface to write to
     */
    public function write(IOInterface $io)
    {
        $this->ref->write($io);
    }

}
