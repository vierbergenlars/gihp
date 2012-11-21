<?php

namespace gihp;
use gihp\IO\IOInterface;
use gihp\IO\WritableInterface;
use gihp\Object\Commit;
use gihp\Ref\Head;

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
     * The IOInterface the branch uses
     * @var gihp\IO\IOInterface
     */
    private $io;
    /**
     * New added objects that still need to be written
     * @var array
     */
    private $objects_to_write = array();
    /**
     * Creates a new branch
     * @param IOInterface $io     The IO to use
     * @param string      $name   The name of the branch
     * @param Commit      $commit The HEAD commit of the branch
     */
    public function __construct(IOInterface $io, $name, Commit $commit)
    {
        $this->io = $io;
        $this->ref = new Head($name, $commit);
    }

    /**
     * Get the name of the branch
     * @return string
     */
    public function getName()
    {
        return $this->ref->getName();
    }

    /**
     * Get the HEAD commit of the branch
     * @return Commit
     */
    public function getHeadCommit()
    {
        return $this->ref->getCommit();
    }

    /**
     * Gets the history of the branch, linearized
     * @return array An array of Commit objects
     */
    public function getHistory()
    {
        $head = $this->getHeadCommit();
        $commits = array($head);
        while ($head = $head->getParent()) {
            $commits[] = $head;
        }

        return $commits;
    }

    /**
     * Advances the HEAD of the branch to another commit
     *
     * The new commit must be a child of the previous HEAD commit
     * @param Commit $commit The commit to advance the head to
     */
    private function advanceHead(Commit $commit)
    {
        $current = $this->getHeadCommit();

        while ($parent = $commit->getParent()) {
            if ($parent->getSHA1() == $current->SHA1()) {
                $this->ref->setCommit($commit);

                return;
            }
            $this->objects_to_write[] = $parent;
        }
        throw new \RuntimeException('Cannot advance the head to a commit that is not a child of the head commit');
    }

    /**
     * Writes the branch to IO
     * @param IOInterface $io Optionally a different IOInterface to write to
     */
    public function write(IOInterface $io=null)
    {
        if($io === null) $io = $this->io;
        $io->removeRef($this->ref);
        $this->ref->write($io);
        foreach ($this->objects_to_write as $commit) {
            $commit->write($io);
        }

    }

    /**
     * Loads a branch from IO
     * @param IOInterface $io   The IO to load the branch from
     * @param string      $name The name of the branch
     */
    public static function load(IOInterface $io, $name)
    {
        $ref = $io->readRef('heads/'.$name);
        $commit = $ref->getCommit();

        return new self($io, $name, $commit);
    }
}
