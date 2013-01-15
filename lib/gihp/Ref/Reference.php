<?php

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
     * Reference is a tag
     */
    const TAG = 'tags';
    /**
     * Reference is a head
     */
    const HEAD = 'heads';

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
     * Updates the commit the reference points to
     * @param Internal $commit
     */
    public function setCommit(Internal $commit)
    {
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
     */
    public function __call($func, $args)
    {
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
