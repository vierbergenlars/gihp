<?php

namespace gihp\Ref;

use gihp\Defer\Deferrable;
use gihp\Defer\Loader as DLoader;
use gihp\Defer\Object as Defer;
use gihp\Object\Internal;
use gihp\Object\Commit;
use gihp\Object\AnnotatedTag;

use gihp\IO\IOInterface;
use gihp\IO\WritableInterface;
/**
 * The base of all references
 */
class Reference implements Deferrable, WritableInterface
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
     * @internal the branche's name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the reference type as a string
     * @return string
     */
    private function getTypeAsString()
    {
        if ($this instanceof Head) {
            return self::HEAD;
        }
        if ($this instanceof Tag) {
            return self::TAG;
        }
        throw new \RuntimeException('Bad type');

    }

    /**
     * Gets the type of the ref
     * @return string
     */
    public function getType()
    {
        return $this->getTypeAsString();
    }

    /**
     * Gets the path
     * @internal The whole path
     */
    public function getPath()
    {
        return $this->getTypeAsString().'/'.$this->name;
    }

    /**
     * Converts the reference to a raw data stream
     * @return string
     * @internal
     */
    public function __toString()
    {
        return $this->commit->getSHA1();
    }

    public function write(IOInterface $io)
    {
        $io->addRef($this);
        $this->commit->write($io);
    }

    /**
     * Imports a raw reference data stream
     * @param  gihp\Defer\Loader $loader The loader class
     * @param  string            $data   The raw data
     * @return Tag|Head          One of reference's subclasses. Depending on the datatype
     * @internal
     */
    public static function import(DLoader $loader, $data)
    {
        list($type, $data) = explode("/", $data, 2);
        switch ($type) {
            case self::TAG:
                return Tag::import($loader, $data);
            case self::HEAD:
                return Head::import($loader, $data);
            default:
                throw new \LogicException('Bad reference type');
        }
    }
}
