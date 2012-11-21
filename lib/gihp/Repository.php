<?php

namespace gihp;

use gihp\IO\WritableInterface;
use gihp\IO\IOInterface;
use gihp\Ref\Head;
use gihp\Ref\Tag as RTag;

/**
 * Represents a full git repository
 */
class Repository
{
    /**
     * The IO Interface
     * @var IOInterface
     */
    private $io;
    /**
     * The branch names
     * @var array
     */
    protected $branches;
    /**
     * The tag names
     * @var array
     */
    protected $tags;

    /**
     * Creates a new repository
     * @param IOInterface $io The IO interface to use for all operations
     */
    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    /**
     * Parses the refs from IO into tags and branches
     */
    private function parseRefs()
    {
        $refs = $this->io->readRefs();
        foreach ($refs as $file) {
            if (substr($file, 0, 5) == 'tags/') {
                $name = substr($file, 5);
                $this->tags[$name] = $this->getTag($name);
            } elseif (substr($file, 0, 6) == 'heads/') {
                $name = substr($file, 6);
                $this->branches[$name] = $this->getBranch($name);
            }
        }
    }

    /**
     * Gets all branches, indexed by their name
     * @var array
     */
    public function getBranches()
    {
        $this->parseRefs();

        return $this->branches;
    }

    /**
     * Gets all tags, indexed by their name
     * @var array
     */
    public function getTags()
    {
        $this->parseRefs();

        return $this->tags;
    }

    /**
     * Gets a specific branch
     * @param  string $name The name of the branch
     * @return Branch
     */
    public function getBranch($name)
    {
        return Branch::load($this->io, $name);
    }

    /**
     * Gets a specific tag
     * @param  string $name The tagname
     * @return Tag
     */
    public function getTag($name)
    {
        return Tag::load($this->io, $name);
    }

    /**
     * Adds a new tag
     * @param Tag $tag The tag to add
     */
    public function addTag(Tag $tag)
    {
        $tag->write($this->io);
    }

    /**
     * Removes a tag
     * @param Tag $tag The tag to remove
     */
    public function removeTag(Tag $tag)
    {
        $name = $tag->getName();
        $ref = new RTag($name, $tag->getCommit());
        $this->io->removeRef($ref);
    }

    /**
     * Adds a new branch
     * @param Branch $branch The branch to add
     */
    public function addBranch(Branch $branch)
    {
        $branch->write($this->io);
    }

    /**
     * Removes a branch
     * @param Branch $branch The branch to remove
     */
    public function removeBranch(Branch $branch)
    {
        $name = $branch->getName();
        $ref = new Head($name, $branch->getHeadCommit());
        $this->io->removeRef($ref);
    }

    /**
     * Writes any writable object to IO
     * @param WritableInterface The object to write to IO
     */
    public function write(WritableInterface $write)
    {
        $write->write($this->io);
    }
}
