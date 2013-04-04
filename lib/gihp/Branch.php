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
use gihp\Object\Commit;
use gihp\Ref\Head;
use gihp\Metadata\Person;
/**
 * A git branch
 */
class Branch implements WritableInterface
{
    /**
     * The reference the branch points to
     * @var gihp\Ref\Reference
     */
    protected $ref;
    /**
     * The name of the branch
     * @var string
     */
    protected $name;

    /**
     * Creates a new branch
     * @param string $name   The name of the branch
     * @param Commit $commit The HEAD commit of the branch
     */
    public function __construct($name, Commit $commit=null)
    {
        $this->name = $name;
        if($commit)
            $this->ref = new Head($name, $commit);
    }

    /**
     * Get the name of the branch
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the current tree
     * @return Tree|null
     */
    public function getTree()
    {
        if(!$this->getHeadCommit())

            return null;
        return new Tree($this->getHeadCommit()->getTree());
    }
    
    /**
     * Gets the underlying plumbing reference of the branch
     * @return Head|null
     */
    public function getHead()
    {
        return $this->ref;
    }

    /**
     * Get the HEAD commit of the branch
     * @return Commit|null
     */
    public function getHeadCommit()
    {
        if(!$this->ref)

            return null;
        return $this->ref->getCommit();
    }

    /**
     * Gets the history of the branch, linearized
     * @return array An array of {@link Commit} objects
     */
    public function getHistory()
    {
        if(!$this->getHeadCommit())

            return array();
        $head = $this->getHeadCommit();

        return self::recurseParents($head);
    }

    private static function recurseParents(Commit $commit)
    {
        $parents = $commit->getParents();
        $ret = array($commit);
        foreach ($parents as $parent) {
            $ret = array_merge($ret, self::recurseParents($parent));
        }

        return $ret;
    }

    /**
     * Creates a new commit to the branch
     * @param  string $message The commit message
     * @param  Tree   $tree    The tree that describes the data in the commit
     * @param  Person $author  The author and committer of the commit
     * @return Commit The commit that has just been made
     */
    public function commit($message, Tree $tree, Person $author)
    {
        $otree = $tree->getTree();
        $commit = new Commit($message, $otree, $author, null, $this->getHeadCommit());
        $this->advanceHead($commit);

        return $commit;
    }

    /**
     * Advances the HEAD of the branch to another commit
     *
     * @param Commit $commit The commit to advance the head to
     */
    public function advanceHead(Commit $commit)
    {
        $current = $this->getHeadCommit();

        if (!$current) {
            $this->ref = new Head($this->name, $commit);

            return;
        }
        $this->ref->setCommit($commit);
   }

    /**
     * Writes the branch to IO
     * @param IOInterface $io An IOInterface to write to
     */
    public function write(IOInterface $io)
    {
        if(!$this->ref)
            throw new \LogicException('Branch cannot be written if no head reference exists');
        try {
            $io->removeRef($this->ref);
        } catch (\RuntimeException $e) {
            //Nothing to worry about, the ref just does not exist.
        }
        $this->ref->write($io);
    }

}
