<?php

namespace gihp\Ref;

use gihp\Defer\Deferrable;
use gihp\Defer\Loader as DLoader;
use gihp\Defer\Object as Defer;
use gihp\Object\Commit;

use gihp\IO\IOInterface;
use gihp\IO\WritableInterface;
/**
 * The base of all references
 * @internal
 */
class Reference implements Deferrable, WritableInterface {
    /**
     * Reference is a tag
     */
    const TAG = 'tags';
    /**
     * Reference is a head
     */
    const HEAD = 'heads';

    /**
     * The commit that is referenced
     * @var Commit
     */
    protected $commit;
    /**
     * The name of the head
     * @internal the branch name
     * @var string
     */
    protected $name;
    /**
     * Creates a new head reference
     * @internal creates a new branch
     * @param string $name The name of the head reference
     * @param Commit $commit The commit the reference points to
     */
    function __construct($name, Commit $commit) {
        $this->name = $name;
        $this->commit = $commit;
    }

    /**
     * Updates the commit the reference points to
     * @param Commit $commmit
     */
    function setCommit(Commit $commit) {
        $this->commit = $commit;
    }

    /**
     * Gets the commit the reference points to
     * @return Commit
     */
    function getCommit() {
        return $this->commit;
    }

    /**
     * Gets the SHA the reference refers to
     * @return string
     */
    function getSHA1() {
        return $this->commit->getSHA1();
    }
    /**
     * Gets the name of the head reference
     * @internal the branche's name
     */
    function getName() {
        return $this->name;
    }

    /**
     * Gets the reference type as a string
     * @return string
     */
    private function getTypeAsString() {
        if($this instanceof Head) {
            return self::HEAD;
        }
        if($this instanceof Tag) {
            return self::TAG;
        }
        throw new \RuntimeException('Bad type');

    }

    /**
     * Gets the type of the ref
     * @return string
     */
    function getType() {
        return $this->getTypeAsString();
    }

    /**
     * Gets the data
     * @internal it's just the SHA
     */
    function getData() {
        return $this->commit->getSHA1();
    }

    /**
     * Gets the path
     * @internal The whole path
     */
    function getPath() {
        return $this->getTypeAsString().'/'.$this->name;
    }

    /**
     * Converts the reference to a raw data stream
     * @return string
     */
    function __toString() {
        return $this->getData();
    }

    function write(IOInterface $io) {
        $io->addRef($this);
        $this->commit->write($io);
    }

    /**
     * Imports a raw reference data stream
     * @param gihp\Defer\Loader $loader The loader class
     * @param string $data The raw data
     * @return Tag|Head One of reference's subclasses. Depending on the datatype
     */
    static function import(DLoader $loader, $data) {
        list($type, $data) = explode("/", $data, 2);
        switch($type) {
            case self::TAG:
                return Tag::import($loader, $data);
            case self::HEAD:
                return Head::import($loader, $data);
            default:
                throw new \LogicException('Bad reference type');
        }
    }
}
