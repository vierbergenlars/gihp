<?php

namespace gihp;
use gihp\IO\IOInterface;
use gihp\IO\WritableInterface;
use gihp\Object\Commit;
use gihp\Ref\Head;
use gihp\Metadata\Person;
use gihp\Object\Tree;
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
     * The IOInterface the branch uses
     * @var gihp\IO\IOInterface
     */
    private $io;
    /**
     * Creates a new branch
     * @param IOInterface $io     The IO to use
     * @param string      $name   The name of the branch
     * @param Commit      $commit The HEAD commit of the branch
     */
    public function __construct(IOInterface $io, $name, Commit $commit=null)
    {
        $this->io = $io;
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
     * Get the HEAD commit of the branch
     * @return Commit
     */
    public function getHeadCommit()
    {
        if(!$this->ref)

            return null;
        return $this->ref->getCommit();
    }

    /**
     * Gets the history of the branch, linearized
     * @return array An array of Commit objects
     */
    public function getHistory()
    {
        if(!$this->getHeadCommit())

            return array();
        $head = $this->getHeadCommit();
        $commits = array($head);
        while ($head = $head->getParent()) {
            $commits[] = $head;
        }

        return $commits;
    }

    /**
     * Creates a new commit to the branch
     * @param string $message The commit message
     * @param Tree   $tree    The tree that describes the data in the commit
     * @param Person $author  The author and committer of the commit
     */
    public function commit($message, Tree $tree, Person $author)
    {
        $commit = new Commit($message, $tree, $author, null, $this->getHeadCommit());
        $this->advanceHead($commit);
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

        if (!$current) {
            $this->ref = new Head($this->name, $commit);

            return;
        }

        while ($parent = $commit->getParent()) {
            if ($parent->getSHA1() == $current->getSHA1()) {
                $this->ref->setCommit($commit);

                return;
            }
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
        if(!$this->ref)
            throw new \LogicException('Branch cannot be written if no head reference exists');
        try {
            $io->removeRef($this->ref);
        } catch (\RuntimeException $e) {
            //Nothing to worry about, the ref just does not exist.
        }
        $this->ref->write($io);
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
