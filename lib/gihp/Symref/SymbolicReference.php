<?php

namespace gihp\Symref;

use gihp\Defer\Deferrable;
use gihp\IO\WritableInterface;
use gihp\Ref\Head;
use gihp\Object\Commit;
/**
 * A symbolic reference
 *
 * A symbolic reference holds a pointer to a reference or to a commit
 */
class SymbolicReference implements Deferrable, WritableInterface {
    /**
     * The thing being pointed to
     * @var Head|Commit
     */
    protected $head;

    /**
     * Creates a new symref
     * @param Head|Commit $ref The reference to point to
     */
    function __construct($ref) {
        if($ref instanceof Head) {
            $this->head = $ref;
            $this->symref = true;
        }
        elseif($ref instanceof Commit) {
            $this->head = $ref;
            $this->symref = false;
        }
        else {
            throw new \LogicException('A symref can only point to Commits and Heads');
        }
    }

    /**
     * Gets the SHA of the thing being pointed to.
     *
     * In case of a commit: The SHA of the commit
     * In case of a head: The SHA of the commit the head points to
     * @return string
     */
    function getSHA1() {
        return $this->head->getSHA1();
    }

    /**
     * Is this reference a symbolic one?
     *
     * It is only symbolic when it points to a Head
     * @return bool
     */
    function isSymbolic() {
        return ($this->head instanceof Head);
    }

    /**
     * Gets the commit that is being pointed to
     *
     * In case of a commit: The commit itself
     * In case of a head: The commit the head points to.
     * @return Commit
     */
    function getCommit() {
        if($this->head instanceof Head) {
            return $this->head->getCommit();
        }
        return $this->head;
    }

    /**
     * Gets the head that is being pointed to
     *
     * In case of a commit: null
     * In case of a head: The head itself
     * @return Head|null
     */
    function getHead() {
        if($this->head instanceof Head) {
            return $this->head;
        }
        return null;
    }
    
    /**
     * Writes the symbolic reference and the object it points to to disk
     * @param \gihp\IO\IOInterface $io
     */
    function write(\gihp\IO\IOInterface $io) {
        $io->moveHead($this);
        $this->head->write($io);
    }
}
