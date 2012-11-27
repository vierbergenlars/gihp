<?php

namespace gihp;
use gihp\IO\IOInterface;
use gihp\IO\WritableInterface;
use gihp\Object\Commit;
use gihp\Ref\Head;
use gihp\Metadata\Person;
/**
 * A git branch
 */
class Branch implements WritableInterface {
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
     * The IOInterface the branch uses
     * @var gihp\IO\IOInterface
     */
    private $io;
    /**
     * Creates a new branch
     * @param IOInterface $io The IO to use
     * @param string $name The name of the branch
     * @param Commit $commit The HEAD commit of the branch
     */
    function __construct(IOInterface $io, $name, Commit $commit=null) {
        $this->io = $io;
        $this->name = $name;
        if($commit)
            $this->ref = new Head($name, $commit);
    }

    /**
     * Get the name of the branch
     * @return string
     */
    function getName() {
        return $this->name;
    }

    /**
     * Get the current tree
     * @return Tree
     */
    function getTree() {
        return new Tree($this->getHeadCommit()->getTree());
    }

    /**
     * Get the HEAD commit of the branch
     * @return Commit
     */
    function getHeadCommit() {
        if(!$this->ref)
            return null;
        return $this->ref->getCommit();
    }

    /**
     * Gets the history of the branch, linearized
     * @return array An array of Commit objects
     */
    function getHistory() {
        if(!$this->getHeadCommit())
            return array();
        $head = $this->getHeadCommit();
        return self::recurseParents($head);
    }

    static private function recurseParents(Commit $commit) {
        $parents = $commit->getParents();
        $ret = array($commit);
        foreach($parents as $parent) {
            $ret = array_merge($ret, self::recurseParents($parent));
        }
        return $ret;
    }

    /**
     * Creates a new commit to the branch
     * @param string $message The commit message
     * @param Tree $tree The tree that describes the data in the commit
     * @param Person $author The author and committer of the commit
     */
    function commit($message, Tree $tree, Person $author) {
        $otree = $tree->getTree();
        $commit = new Commit($message, $otree, $author, null, $this->getHeadCommit());
        $this->advanceHead($commit);
    }

    /**
     * Advances the HEAD of the branch to another commit
     *
     * @param Commit $commit The commit to advance the head to
     */
    private function advanceHead(Commit $commit) {
        $current = $this->getHeadCommit();

        if(!$current) {
            $this->ref = new Head($this->name, $commit);
            return;
        }
        $this->ref->setCommit($commit);
   }

    /**
     * Writes the branch to IO
     * @param IOInterface $io Optionally a different IOInterface to write to
     */
    function write(IOInterface $io=null) {
        if($io === null) $io = $this->io;
        if(!$this->ref)
            throw new \LogicException('Branch cannot be written if no head reference exists');
        try {
            $io->removeRef($this->ref);
        }
        catch(\RuntimeException $e) {
            //Nothing to worry about, the ref just does not exist.
        }
        $this->ref->write($io);
    }

    /**
     * Loads a branch from IO
     * @param IOInterface $io The IO to load the branch from
     * @param string $name The name of the branch
     */
    static function load(IOInterface $io, $name) {
        $ref = $io->readRef('heads/'.$name);
        $commit = $ref->getCommit();

        return new self($io, $name, $commit);
    }
}
