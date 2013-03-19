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

namespace gihp\Object;

use gihp\IO\IOInterface;
use gihp\IO\WritableInterface;
use gihp\Metadata\Person;
use gihp\Ref\Tag;

/**
 * An annotated tag
 *
 */
class AnnotatedTag extends Internal implements WritableInterface
{
    /**
     * Tag name
     * @var string
     */
    protected $name;
    /**
     * Associated tag message
     * @var string
     */
    protected $message;
    /**
     * The person who tagged it
     * @var Person
     */
    protected $tagger;
    /**
     * The time of tagging
     * @var \DateTime
     */
    protected $date;
    /**
     * The object that was tagged
     * @var Internal
     */
    protected $object;

    /**
     * Creates a new annotated tag
     * @param string    $name    The name of the tag
     * @param string    $message The message to associate with the tag
     * @param Person    $tagger  The person who tagged the object
     * @param \DateTime $date    The time of tagging
     * @param Internal  $object  The object being tagged, usually a {@link Commit}
     */
    public function __construct($name, $message, Person $tagger, \DateTime $date, Internal $object)
    {
        $this->name = $name;
        $this->message = $message;
        $this->tagger = $tagger;
        $this->date = $date;
        $this->object = $object;
    }

    /**
     * Gets the tag name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the tag message
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Gets the person who tagged it
     * @return Person
     */
    public function getAuthor()
    {
        return $this->tagger;
    }

    /**
     * Gets the time of tagging
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Gets the object that is being tagged, usually a {@link Commit}
     * @return Internal
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Writes the annotated tag to IO
     * @param IOInterface $io The IO to write to
     * @internal
     */
    public function write(IOInterface $io)
    {
        $io->addObject($this);
        $this->object->write($io);
    }
}
