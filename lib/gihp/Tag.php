<?php

namespace gihp;

use gihp\IO\IOInterface;
use gihp\IO\WritableInterface;
use gihp\Object\Commit;
use gihp\Ref\Tag as RTag;

/**
 * A git tag
 */
class Tag implements WritableInterface {
    /**
     * The IO interface
     * @var IOInterface
     */
    private $io;
    /**
     * The tagname
     * @var string
     */
    protected $name;
    /**
     * The reference that contains the tag
     * @var RTag
     */
    protected $ref;
    function __construct(IOInterface $io, $name, Commit $commit=null) {
        $this->io = $io;
        $this->name = $name;
        if($commit)
            $this->ref = new RTag($name, $commit);
    }

    /**
     * Get the name of the tag
     * @return string
     */
    function getName() {
        return $this->name;
    }

    /**
     * Get the commit the tag points to
     * @return Commit
     */
    function getCommit() {
        if(!$this->ref)
            return null;
        return $this->ref->getCommit();
    }

    /**
     * Writes the tag to IO
     * @param IOInterface $io Optionally a different IOInterface to write to
     */
    function write(IOInterface $io=null) {
        if($io === null) $io = $this->io;
        if(!$this->ref)
            throw new \LogicException('Tag cannot be written if it does not point to a commit');
        $this->ref->write($io);
    }

    /**
     * Loads a tag from IO
     * @param IOInterface $io The IO to load the tag from
     * @param string $name The name of the tag
     */
    static function load(IOInterface $io, $name) {
        $ref = $io->readRef('tags/'.$name);
        $commit = $ref->getCommit();

        return new self($io, $name, $commit);
    }

}
