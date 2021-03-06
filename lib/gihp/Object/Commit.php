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

/**
 * Represents a commit
 */
class Commit extends Internal implements WritableInterface
{
    /**
     * Commit message
     * @var string
     */
    protected $message;
    /**
     * Root tree
     * @var Tree
     */
    protected $tree;
    /**
     * Commit author
     * @var Person
     */
    protected $author;
    /**
     * Time the commit was authored
     * @var \DateTime
     */
    protected $author_time;
    /**
     * Committer
     * @var Person
     */
    protected $committer;
    /**
     * Time the commit was made
     * @var \DateTime
     */
    protected $commit_time;
    /**
     * Parent commits, if any
     * @var array
     */
    protected $parents = array();
    /**
     * Creates a new commit object
     * @param string    $message   Commit message
     * @param Tree      $root_tree The root tree describing the state of the working tree
     * @param Person    $author    The commit author
     * @param \DateTime $date      The time the commit was made. If not set, assume now
     * @param Commit    $parent    The parent commit. If not set, this is the first commit
     */
    public function __construct($message, Tree $root_tree, Person $author, \DateTime $date=null, Commit $parent=null)
    {
        $this->message = $message;
        $this->tree = $root_tree;
        $this->author = $this->committer = $author;
        if($date === null) $date = new \DateTime;
        $this->author_time = $this->commit_time = $date;
        if($parent)
            $this->{'parents'}[] = $parent;
    }

    /**
     * Sets the committer.
     *
     * It is assumed the author also is the committer and the time the commit was made is the commit time.
     * If this is incorrect, set the committer and the commit time.
     *
     * @param Person    $committer The actual committer
     * @param \DateTime $date      The date the commit was made. If null, don't change the commit date.
     */
    public function setCommitter(Person $committer, \DateTime $date=null)
    {
        $this->committer = $committer;
        if($date !== null)
            $this->commit_time = $date;
    }

    /**
     * Gets the commit message
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get the commit's root tree
     * @return Tree
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * Get the commit author
     * @return Person
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Gets the time the commit was authored
     * @return \DateTime
     */
    public function getAuthorTime()
    {
        return $this->author_time;
    }

    /**
     * Gets the committer
     * @return Person
     */
    public function getCommitter()
    {
        return $this->committer;
    }

    /**
     * Gets the commit time
     * @return \DateTime
     */
    public function getCommitTime()
    {
        return $this->commit_time;
    }

    /**
     * Gets the parent commit
     * @return array
     */
    public function getParents()
    {
        return $this->{'parents'};
    }

    /**
     * Writes the commit and its dependencies to IO
     * @internal
     */
    public function write(IOInterface $io)
    {
        $io->addObject($this);
        foreach ($this->parents as $parent) {
            $parent->write($io);
        }
        $this->tree->write($io);
    }
}
